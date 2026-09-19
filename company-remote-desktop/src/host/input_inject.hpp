#pragma once

#ifdef _WIN32

#include "crd/session.hpp"

namespace crd {

class WinInputSink : public IInputSink {
public:
    // Mouse events arrive in streamed-frame pixels; map them onto the real desktop.
    void set_frame_size(int width, int height);
    void on_mouse(const MouseEvent& ev) override;
    void on_key(const KeyEvent& ev) override;

private:
    int frame_w_ = 0;
    int frame_h_ = 0;
};

} // namespace crd

#endif
