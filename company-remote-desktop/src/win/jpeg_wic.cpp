#ifdef _WIN32

#include "win/jpeg_wic.hpp"

#include "crd/log.hpp"
#include "crd/platform.hpp"

#include <objbase.h>
#include <wincodec.h>
#include <wrl/client.h>

#include <algorithm>
#include <cstring>

#pragma comment(lib, "windowscodecs.lib")
#pragma comment(lib, "ole32.lib")

using Microsoft::WRL::ComPtr;

namespace crd {
namespace {

bool ensure_com() {
    const HRESULT hr = CoInitializeEx(nullptr, COINIT_MULTITHREADED);
    return hr == S_OK || hr == S_FALSE || hr == RPC_E_CHANGED_MODE;
}

class GlobalStream {
public:
    IStream* stream = nullptr;
    HGLOBAL mem = nullptr;

    ~GlobalStream() {
        if (stream) {
            stream->Release();
        }
        // CreateStreamOnHGlobal(..., TRUE) frees HGLOBAL on Release.
    }
};

bool create_memory_stream(GlobalStream& out) {
    out.mem = GlobalAlloc(GMEM_MOVEABLE, 0);
    if (!out.mem) {
        return false;
    }
    const HRESULT hr = CreateStreamOnHGlobal(out.mem, TRUE, &out.stream);
    if (FAILED(hr)) {
        GlobalFree(out.mem);
        out.mem = nullptr;
        return false;
    }
    return true;
}

} // namespace

bool jpeg_encode_bgra(const std::uint8_t* bgra, int width, int height, int quality, std::vector<std::uint8_t>& jpeg) {
    jpeg.clear();
    if (!bgra || width <= 0 || height <= 0) {
        return false;
    }
    ensure_com();
    quality = std::clamp(quality, 1, 100);

    ComPtr<IWICImagingFactory> factory;
    HRESULT hr = CoCreateInstance(CLSID_WICImagingFactory, nullptr, CLSCTX_INPROC_SERVER, IID_PPV_ARGS(&factory));
    if (FAILED(hr)) {
        log_error("WIC factory failed: 0x%08lx", static_cast<unsigned long>(hr));
        return false;
    }

    const UINT stride = static_cast<UINT>(width * 4);
    const UINT size = stride * static_cast<UINT>(height);
    ComPtr<IWICBitmap> bitmap;
    hr = factory->CreateBitmapFromMemory(static_cast<UINT>(width), static_cast<UINT>(height),
                                         GUID_WICPixelFormat32bppBGRA, stride, size,
                                         const_cast<BYTE*>(bgra), &bitmap);
    if (FAILED(hr)) {
        return false;
    }

    GlobalStream gs;
    if (!create_memory_stream(gs)) {
        return false;
    }

    ComPtr<IWICBitmapEncoder> encoder;
    hr = factory->CreateEncoder(GUID_ContainerFormatJpeg, nullptr, &encoder);
    if (FAILED(hr)) {
        return false;
    }
    hr = encoder->Initialize(gs.stream, WICBitmapEncoderNoCache);
    if (FAILED(hr)) {
        return false;
    }

    ComPtr<IWICBitmapFrameEncode> frame;
    ComPtr<IPropertyBag2> props;
    hr = encoder->CreateNewFrame(&frame, &props);
    if (FAILED(hr)) {
        return false;
    }

    PROPBAG2 option{};
    option.pstrName = const_cast<LPOLESTR>(L"ImageQuality");
    VARIANT value;
    VariantInit(&value);
    value.vt = VT_R4;
    value.fltVal = static_cast<FLOAT>(quality) / 100.0f;
    props->Write(1, &option, &value);
    VariantClear(&value);

    hr = frame->Initialize(props.Get());
    if (FAILED(hr)) {
        return false;
    }
    hr = frame->SetSize(static_cast<UINT>(width), static_cast<UINT>(height));
    if (FAILED(hr)) {
        return false;
    }
    WICPixelFormatGUID format = GUID_WICPixelFormat24bppBGR;
    hr = frame->SetPixelFormat(&format);
    if (FAILED(hr)) {
        return false;
    }
    hr = frame->WriteSource(bitmap.Get(), nullptr);
    if (FAILED(hr)) {
        return false;
    }
    hr = frame->Commit();
    if (FAILED(hr)) {
        return false;
    }
    hr = encoder->Commit();
    if (FAILED(hr)) {
        return false;
    }

    STATSTG stat{};
    if (FAILED(gs.stream->Stat(&stat, STATFLAG_NONAME))) {
        return false;
    }
    const ULONG n = static_cast<ULONG>(stat.cbSize.QuadPart);
    LARGE_INTEGER zero{};
    gs.stream->Seek(zero, STREAM_SEEK_SET, nullptr);
    jpeg.resize(n);
    ULONG read = 0;
    hr = gs.stream->Read(jpeg.data(), n, &read);
    jpeg.resize(read);
    return SUCCEEDED(hr) && read > 0;
}

bool jpeg_decode_bgra(const std::uint8_t* jpeg, std::size_t jpeg_size, std::vector<std::uint8_t>& bgra, int& width,
                      int& height) {
    bgra.clear();
    width = 0;
    height = 0;
    if (!jpeg || jpeg_size == 0) {
        return false;
    }
    ensure_com();

    HGLOBAL mem = GlobalAlloc(GMEM_MOVEABLE, jpeg_size);
    if (!mem) {
        return false;
    }
    void* locked = GlobalLock(mem);
    if (!locked) {
        GlobalFree(mem);
        return false;
    }
    std::memcpy(locked, jpeg, jpeg_size);
    GlobalUnlock(mem);

    IStream* stream = nullptr;
    HRESULT hr = CreateStreamOnHGlobal(mem, TRUE, &stream);
    if (FAILED(hr)) {
        GlobalFree(mem);
        return false;
    }

    ComPtr<IWICImagingFactory> factory;
    hr = CoCreateInstance(CLSID_WICImagingFactory, nullptr, CLSCTX_INPROC_SERVER, IID_PPV_ARGS(&factory));
    if (FAILED(hr)) {
        stream->Release();
        return false;
    }

    ComPtr<IWICBitmapDecoder> decoder;
    hr = factory->CreateDecoderFromStream(stream, nullptr, WICDecodeMetadataCacheOnLoad, &decoder);
    stream->Release();
    if (FAILED(hr)) {
        return false;
    }

    ComPtr<IWICBitmapFrameDecode> frame;
    hr = decoder->GetFrame(0, &frame);
    if (FAILED(hr)) {
        return false;
    }

    ComPtr<IWICFormatConverter> converter;
    hr = factory->CreateFormatConverter(&converter);
    if (FAILED(hr)) {
        return false;
    }
    hr = converter->Initialize(frame.Get(), GUID_WICPixelFormat32bppBGRA, WICBitmapDitherTypeNone, nullptr, 0.0,
                               WICBitmapPaletteTypeCustom);
    if (FAILED(hr)) {
        return false;
    }

    UINT w = 0, h = 0;
    converter->GetSize(&w, &h);
    if (w == 0 || h == 0) {
        return false;
    }
    const UINT stride = w * 4;
    bgra.resize(static_cast<std::size_t>(stride) * h);
    hr = converter->CopyPixels(nullptr, stride, static_cast<UINT>(bgra.size()), bgra.data());
    if (FAILED(hr)) {
        bgra.clear();
        return false;
    }
    width = static_cast<int>(w);
    height = static_cast<int>(h);
    return true;
}

} // namespace crd

#endif
