cmake_minimum_required(VERSION 3.16)
project(hddsuperclone-windows VERSION 2.4.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

if(MSVC)
  add_compile_options(/W3 /utf-8 /D_CRT_SECURE_NO_WARNINGS)
  add_definitions(-DUNICODE -D_UNICODE -D_WIN32_WINNT=0x0A00)
else()
  add_compile_options(-Wall -Wextra -Wno-unused-parameter)
  if(WIN32)
    add_definitions(-D_WIN32_WINNT=0x0A00)
  endif()
endif()

set(HSC_ENGINE_SRC
  src/sector_map.cpp
  src/skip.cpp
  src/disk_io.cpp
  src/progress_log.cpp
  src/clone_engine.cpp
  src/safety.cpp
  src/usb_relay.cpp
  src/virtual_disk.cpp
  src/script_engine.cpp
  src/usb_direct.cpp
  src/file_recovery.cpp
  src/ntfs_mft.cpp
  src/carve_sigs.cpp
)

file(READ "${CMAKE_CURRENT_SOURCE_DIR}/resources/carve_signatures.txt" HSC_CARVE_TABLE)
configure_file("${CMAKE_CURRENT_SOURCE_DIR}/src/carve_sigs_table.inc.in"
               "${CMAKE_CURRENT_BINARY_DIR}/carve_sigs_table.inc" @ONLY)

add_library(hsc_engine STATIC ${HSC_ENGINE_SRC})
target_include_directories(hsc_engine PUBLIC ${CMAKE_CURRENT_SOURCE_DIR}/src ${CMAKE_CURRENT_BINARY_DIR})

if(WIN32)
  target_link_libraries(hsc_engine PUBLIC setupapi hid virtdisk winusb ole32)
  set_source_files_properties(resources/app.rc PROPERTIES
    COMPILE_FLAGS "-I${CMAKE_CURRENT_SOURCE_DIR}/resources")
endif()

set(IMGUI_DIR ${CMAKE_CURRENT_SOURCE_DIR}/third_party/imgui)
set(IMGUI_CORE
  ${IMGUI_DIR}/imgui.cpp
  ${IMGUI_DIR}/imgui_draw.cpp
  ${IMGUI_DIR}/imgui_tables.cpp
  ${IMGUI_DIR}/imgui_widgets.cpp
)

set(HSC_APP_SRC
  src/main.cpp
  src/gui.cpp
  src/cli.cpp
  ${IMGUI_CORE}
)

if(WIN32)
  list(APPEND HSC_APP_SRC
    ${IMGUI_DIR}/backends/imgui_impl_win32.cpp
    ${IMGUI_DIR}/backends/imgui_impl_dx11.cpp
    src/platform_win32.cpp
    resources/app.rc
  )
else()
  list(APPEND HSC_APP_SRC
    ${IMGUI_DIR}/backends/imgui_impl_glfw.cpp
    ${IMGUI_DIR}/backends/imgui_impl_opengl3.cpp
    src/platform_glfw.cpp
  )
endif()

add_executable(hddsuperclone-windows ${HSC_APP_SRC})
target_include_directories(hddsuperclone-windows PRIVATE
  ${CMAKE_CURRENT_SOURCE_DIR}/src
  ${IMGUI_DIR}
  ${IMGUI_DIR}/backends
)
target_compile_definitions(hddsuperclone-windows PRIVATE IMGUI_IMPL_WIN32_DISABLE_GAMEPAD)
target_link_libraries(hddsuperclone-windows PRIVATE hsc_engine)

if(WIN32)
  target_link_libraries(hddsuperclone-windows PRIVATE
    d3d11 dxgi d3dcompiler dwmapi
    gdi32 comdlg32 ole32 uuid shell32 user32
  )
  if(MSVC)
    target_link_options(hddsuperclone-windows PRIVATE /MANIFEST:NO /SUBSYSTEM:WINDOWS)
  else()
    # GUI subsystem (no leftover console window) + single static exe.
    target_link_options(hddsuperclone-windows PRIVATE -mwindows -static-libgcc -static-libstdc++)
  endif()
else()
  find_package(PkgConfig REQUIRED)
  pkg_check_modules(GLFW REQUIRED glfw3)
  target_include_directories(hddsuperclone-windows PRIVATE ${GLFW_INCLUDE_DIRS})
  target_link_libraries(hddsuperclone-windows PRIVATE ${GLFW_LIBRARIES} GL pthread dl)
endif()

add_custom_command(TARGET hddsuperclone-windows POST_BUILD
  COMMAND ${CMAKE_COMMAND} -E copy_directory
          ${CMAKE_CURRENT_SOURCE_DIR}/scripts
          $<TARGET_FILE_DIR:hddsuperclone-windows>/scripts
  COMMAND ${CMAKE_COMMAND} -E copy_if_different
          ${CMAKE_CURRENT_SOURCE_DIR}/resources/carve_signatures.txt
          $<TARGET_FILE_DIR:hddsuperclone-windows>/carve_signatures.txt
  COMMENT "Copy scripts and carve signature table next to the executable")

add_executable(hsc_engine_tests tests/engine_test.cpp)
target_link_libraries(hsc_engine_tests PRIVATE hsc_engine)
target_include_directories(hsc_engine_tests PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/src)

enable_testing()
add_test(NAME engine COMMAND hsc_engine_tests)

install(TARGETS hddsuperclone-windows RUNTIME DESTINATION bin)
install(DIRECTORY scripts DESTINATION share/hddsuperclone-windows)
install(FILES README.md LICENSE DESTINATION share/doc/hddsuperclone-windows)
