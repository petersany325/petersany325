cmake_minimum_required(VERSION 3.16)
project(company_remote_desktop VERSION 0.1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_CXX_EXTENSIONS OFF)

option(CRD_BUILD_TESTS "Build protocol and loopback tests" ON)

if(MSVC)
    add_compile_options(/W4 /permissive- /utf-8 /DNOMINMAX /DWIN32_LEAN_AND_MEAN /DUNICODE /D_UNICODE)
else()
    add_compile_options(-Wall -Wextra -Wpedantic)
endif()

set(CRD_PUBLIC_INC ${CMAKE_CURRENT_SOURCE_DIR}/src)

add_library(crd_protocol STATIC
    src/crd/log.cpp
    src/crd/sha256.cpp
    src/crd/random.cpp
    src/crd/tcp_socket.cpp
    src/crd/protocol.cpp
    src/crd/session.cpp
)
target_include_directories(crd_protocol PUBLIC ${CRD_PUBLIC_INC})

if(WIN32)
    target_link_libraries(crd_protocol PUBLIC ws2_32 bcrypt)
else()
    find_package(Threads REQUIRED)
    target_link_libraries(crd_protocol PUBLIC Threads::Threads)
endif()

# Host / viewer are real Windows apps (DXGI, WIC, SendInput, Win32).
if(WIN32)
    add_executable(host
        src/host/main.cpp
        src/host/desktop_capture.cpp
        src/host/input_inject.cpp
        src/host/host_window.cpp
        src/win/jpeg_wic.cpp
    )
    target_link_libraries(host PRIVATE crd_protocol d3d11 dxgi windowscodecs user32 gdi32 ole32)
    target_include_directories(host PRIVATE ${CRD_PUBLIC_INC})

    add_executable(viewer WIN32
        src/viewer/main.cpp
        src/viewer/viewer_window.cpp
        src/win/jpeg_wic.cpp
    )
    target_link_libraries(viewer PRIVATE crd_protocol windowscodecs user32 gdi32 ole32 comctl32 shell32)
    target_include_directories(viewer PRIVATE ${CRD_PUBLIC_INC})
    if(MINGW)
        # Standalone Windows zip: no libgcc / libstdc++ / winpthread DLLs.
        target_link_options(host PRIVATE -static)
        target_link_options(viewer PRIVATE -static -municode)
    endif()
endif()

if(CRD_BUILD_TESTS)
    enable_testing()
    add_executable(test_protocol tests/test_protocol.cpp)
    target_link_libraries(test_protocol PRIVATE crd_protocol)
    add_test(NAME protocol COMMAND test_protocol)

    add_executable(test_loopback tests/test_loopback.cpp)
    target_link_libraries(test_loopback PRIVATE crd_protocol)
    add_test(NAME loopback COMMAND test_loopback)
endif()
