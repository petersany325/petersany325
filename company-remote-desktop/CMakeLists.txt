cmake_minimum_required(VERSION 3.16)
project(company_remote_desktop VERSION 0.2.0 LANGUAGES CXX)

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
    src/crd/util.cpp
    src/crd/config.cpp
    src/crd/identity.cpp
    src/hub/store.cpp
    src/hub/server.cpp
    src/agent/runtime.cpp
)
target_include_directories(crd_protocol PUBLIC ${CRD_PUBLIC_INC})

if(WIN32)
    target_link_libraries(crd_protocol PUBLIC ws2_32 bcrypt shell32)
else()
    find_package(Threads REQUIRED)
    target_link_libraries(crd_protocol PUBLIC Threads::Threads)
endif()

add_executable(hub src/hub/main.cpp)
target_link_libraries(hub PRIVATE crd_protocol)
target_include_directories(hub PRIVATE ${CRD_PUBLIC_INC})

if(WIN32)
    set(AGENT_SOURCES
        src/agent/main.cpp
        src/agent/agent_window.cpp
        src/host/desktop_capture.cpp
        src/host/input_inject.cpp
        src/win/jpeg_wic.cpp
    )
    add_executable(agent ${AGENT_SOURCES})
    target_link_libraries(agent PRIVATE crd_protocol d3d11 dxgi windowscodecs user32 gdi32 ole32)
    target_include_directories(agent PRIVATE ${CRD_PUBLIC_INC})

    # Compatibility name used by older shortcuts.
    add_executable(host ${AGENT_SOURCES})
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
        target_link_options(hub PRIVATE -static)
        target_link_options(agent PRIVATE -static)
        target_link_options(host PRIVATE -static)
        target_link_options(viewer PRIVATE -static -municode)
    endif()
else()
    add_executable(agent src/agent/main.cpp)
    target_link_libraries(agent PRIVATE crd_protocol)
    target_include_directories(agent PRIVATE ${CRD_PUBLIC_INC})
endif()

if(CRD_BUILD_TESTS)
    enable_testing()
    add_executable(test_protocol tests/test_protocol.cpp)
    target_link_libraries(test_protocol PRIVATE crd_protocol)
    add_test(NAME protocol COMMAND test_protocol)

    add_executable(test_loopback tests/test_loopback.cpp)
    target_link_libraries(test_loopback PRIVATE crd_protocol)
    add_test(NAME loopback COMMAND test_loopback)

    add_executable(test_hub_relay tests/test_hub_relay.cpp)
    target_link_libraries(test_hub_relay PRIVATE crd_protocol)
    add_test(NAME hub_relay COMMAND test_hub_relay)
endif()
