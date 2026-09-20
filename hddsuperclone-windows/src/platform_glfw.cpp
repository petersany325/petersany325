#include "platform.hpp"

#include "imgui.h"
#include "backends/imgui_impl_glfw.h"
#include "backends/imgui_impl_opengl3.h"

#include <GLFW/glfw3.h>
#include <GL/gl.h>

#ifdef __linux__
#include <cstdio>
#include <array>
#include <unistd.h>
#endif

namespace hsc {
namespace {
GLFWwindow* g_window = nullptr;
}

bool platform_init(const char* title, int width, int height) {
    if (!glfwInit()) return false;
    glfwWindowHint(GLFW_CONTEXT_VERSION_MAJOR, 3);
    glfwWindowHint(GLFW_CONTEXT_VERSION_MINOR, 3);
    glfwWindowHint(GLFW_OPENGL_PROFILE, GLFW_OPENGL_CORE_PROFILE);
    g_window = glfwCreateWindow(width, height, title, nullptr, nullptr);
    if (!g_window) {
        glfwTerminate();
        return false;
    }
    glfwMakeContextCurrent(g_window);
    glfwSwapInterval(1);
    IMGUI_CHECKVERSION();
    ImGui::CreateContext();
    ImGui::StyleColorsDark();
    ImGui_ImplGlfw_InitForOpenGL(g_window, true);
    ImGui_ImplOpenGL3_Init("#version 330");
    return true;
}

bool platform_should_close() { return glfwWindowShouldClose(g_window); }
void platform_poll() { glfwPollEvents(); }

void platform_new_frame() {
    ImGui_ImplOpenGL3_NewFrame();
    ImGui_ImplGlfw_NewFrame();
    ImGui::NewFrame();
}

void platform_render() {
    ImGui::Render();
    int w, h;
    glfwGetFramebufferSize(g_window, &w, &h);
    glViewport(0, 0, w, h);
    glClearColor(0.08f, 0.09f, 0.11f, 1.0f);
    glClear(GL_COLOR_BUFFER_BIT);
    ImGui_ImplOpenGL3_RenderDrawData(ImGui::GetDrawData());
    glfwSwapBuffers(g_window);
}

void platform_shutdown() {
    ImGui_ImplOpenGL3_Shutdown();
    ImGui_ImplGlfw_Shutdown();
    ImGui::DestroyContext();
    if (g_window) glfwDestroyWindow(g_window);
    glfwTerminate();
}

void* platform_native_window() { return g_window; }

#ifdef __linux__
static std::string zenity(const char* extra) {
    std::string cmd = std::string("zenity --file-selection ") + extra + " 2>/dev/null";
    FILE* f = popen(cmd.c_str(), "r");
    if (!f) return {};
    char buf[1024]{};
    std::string out;
    if (fgets(buf, sizeof(buf), f)) out = buf;
    pclose(f);
    if (!out.empty() && out.back() == '\n') out.pop_back();
    return out;
}
std::string native_open_file(const char* title, const char*) {
    std::string extra = std::string("--title=\"") + title + "\"";
    return zenity(extra.c_str());
}
std::string native_save_file(const char* title, const char*) {
    std::string extra = std::string("--save --confirm-overwrite --title=\"") + title + "\"";
    return zenity(extra.c_str());
}
std::string native_pick_folder(const char* title) {
    std::string extra = std::string("--directory --title=\"") + title + "\"";
    return zenity(extra.c_str());
}
#else
std::string native_open_file(const char*, const char*) { return {}; }
std::string native_save_file(const char*, const char*) { return {}; }
std::string native_pick_folder(const char*) { return {}; }
#endif

std::string application_dir() {
#ifdef __linux__
    char buf[4096]{};
    ssize_t n = readlink("/proc/self/exe", buf, sizeof(buf) - 1);
    if (n > 0) {
        std::string p(buf, static_cast<size_t>(n));
        auto sl = p.find_last_of('/');
        if (sl != std::string::npos) p.resize(sl);
        return p;
    }
#endif
    return ".";
}

}  // namespace hsc
