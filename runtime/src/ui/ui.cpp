#include "ui_private.hpp"
#include <limits>
#include <mutex>
#include <string>

namespace scpp::detail {
static std::mutex init_mutex;
static std::thread::id owner;
bool ui_thread() {
    std::lock_guard lock(init_mutex);
    return owner == std::this_thread::get_id();
}
static bool initialize() {
    std::lock_guard lock(init_mutex);
    if (owner != std::thread::id{})
        return owner == std::this_thread::get_id();
    if (!gtk_init_check(nullptr, nullptr))
        return false;
    owner = std::this_thread::get_id();
    return true;
}
ui_window_state::~ui_window_state() {
    if (!widget)
        return;
    g_signal_handlers_disconnect_by_data(widget, this);
    if (!closed)
        gtk_widget_destroy(widget);
    g_object_unref(widget);
}
void enqueue_event(const ui_window &window, const char *type, std::shared_ptr<void> view, string message,
                   string url, int64 id, string command, string payload) {
    auto *w = ui_window_access::get(window);
    auto queue = w ? w->queue.lock() : nullptr;
    if (!queue || queue->exited)
        return;
    auto *e = new ui_event_state;
    auto handle = ui_event_access::adopt(e);
    e->window = window;
    e->view = std::move(view);
    e->type = string{type};
    e->message = std::move(message);
    e->url = std::move(url);
    e->id = id;
    e->command = std::move(command);
    e->payload = std::move(payload);
    queue->events.push_back(std::move(handle));
}
static void destroyed(GtkWidget *, gpointer data) noexcept {
    auto *s = static_cast<ui_window_state *>(data);
    s->closed = true;
    s->references.fetch_add(1, std::memory_order_relaxed);
    auto window = ui_window_access::adopt(s);
    try {
        enqueue_event(window, "window_close");
    } catch (...) {
        g_warning("Could not queue window close event");
    }
}
} // namespace scpp::detail
namespace scpp::ui {
using namespace detail;
result_ui_app ui_app_create() {
    if (!initialize())
        return result_ui_app{exception{string{"GTK display unavailable or called outside UI thread"}}};
    return result_ui_app{ui_app_access::adopt(new ui_app_state)};
}
result_ui_window ui_window_create(const ui_app &app, const string &title, int64 width, int64 height) {
    auto *a = ui_app_access::get(app);
    std::string name(title.data(), title.size());
    if (!ui_thread() || !a || a->queue->exited || width <= 0 || height <= 0 || !std::in_range<int>(width) ||
        !std::in_range<int>(height) || name.find('\0') != name.npos ||
        !g_utf8_validate(name.data(), name.size(), nullptr))
        return result_ui_window{exception{string{"invalid window parameters or app"}}};
    auto *w = new ui_window_state;
    auto handle = ui_window_access::adopt(w);
    w->queue = a->queue;
    w->widget = gtk_window_new(GTK_WINDOW_TOPLEVEL);
    g_object_ref(w->widget);
    gtk_window_set_title(GTK_WINDOW(w->widget), name.c_str());
    gtk_window_set_default_size(GTK_WINDOW(w->widget), static_cast<int>(width), static_cast<int>(height));
    g_signal_connect(w->widget, "destroy", G_CALLBACK(destroyed), w);
    return result_ui_window{std::move(handle)};
}
result_bool_t ui_window_show(const ui_window &window) {
    auto *w = ui_window_access::get(window);
    if (!ui_thread() || !w || w->closed)
        return result_bool_t{exception{string{"window is closed or on another thread"}}};
    gtk_widget_show_all(w->widget);
    return result_bool_t{true};
}
result_bool_t ui_window_close(const ui_window &window) {
    auto *w = ui_window_access::get(window);
    if (!ui_thread() || !w)
        return result_bool_t{exception{string{"invalid window"}}};
    if (!w->closed)
        gtk_widget_destroy(w->widget);
    return result_bool_t{true};
}
bool_t ui_app_poll(const ui_app &app) {
    auto *a = ui_app_access::get(app);
    if (!ui_thread() || !a || a->queue->exited)
        return false;
    g_main_context_iteration(nullptr, false);
    return !a->queue->events.empty();
}
nullable_ui_event ui_app_next_event(const ui_app &app) {
    auto *a = ui_app_access::get(app);
    if (!ui_thread() || !a || a->queue->events.empty())
        return {};
    auto event = std::move(a->queue->events.front());
    a->queue->events.pop_front();
    return nullable_ui_event{std::move(event)};
}
void ui_app_exit(const ui_app &app) {
    auto *a = ui_app_access::get(app);
    if (a && ui_thread()) {
        a->queue->exited = true;
        a->queue->events.clear();
    }
}
string ui_event_type(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s ? s->type : string{};
}
string ui_event_message(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s ? s->message : string{};
}
string ui_event_text(const ui_event &e) {
    return ui_event_message(e);
}
string ui_event_url(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s ? s->url : string{};
}
nullable_ui_window ui_event_window(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    if (!s)
        return {};
    return nullable_ui_window{s->window};
}
} // namespace scpp::ui
#include "ui_values.hpp"
