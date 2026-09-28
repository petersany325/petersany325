#pragma once

#include <string>

struct GLFWwindow;

namespace hsc {

bool platform_init(const char* title, int width, int height);
bool platform_should_close();
void platform_new_frame();
void platform_render();
void platform_shutdown();
void platform_poll();
void* platform_native_window();
std::string native_open_file(const char* title, const char* filter);
std::string native_save_file(const char* title, const char* filter);
std::string native_pick_folder(const char* title);
std::string application_dir();

}  // namespace hsc
