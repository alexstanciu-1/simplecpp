#pragma once
#include "ui_provider.hpp"
#include <gtk/gtk.h>
#include <atomic>
#include <deque>
#include <memory>
#include <thread>
#include <utility>

namespace scpp::detail {
struct ui_queue {
    std::deque<ui_event> events;
    bool exited{};
};
struct ui_app_state {
    std::atomic<size_t> references{1};
    std::shared_ptr<ui_queue> queue = std::make_shared<ui_queue>();
};
struct ui_window_state {
    std::atomic<size_t> references{1};
    std::weak_ptr<ui_queue> queue;
    GtkWidget *widget{};
    bool closed{};
    ~ui_window_state();
};
struct ui_event_state {
    std::atomic<size_t> references{1};
    ui_window window;
    std::shared_ptr<void> view;
    string type, message, url, command, payload;
    int64 id{};
};
bool ui_thread();
void enqueue_event(const ui_window &, const char *type, std::shared_ptr<void> view = {}, string message = {},
                   string url = {}, int64 id = 0, string command = {}, string payload = {});
} // namespace scpp::detail
