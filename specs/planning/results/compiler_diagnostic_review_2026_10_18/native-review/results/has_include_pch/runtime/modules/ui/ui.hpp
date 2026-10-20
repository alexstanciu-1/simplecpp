#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"

#include <deque>

#ifndef SCPP_HAS_UI
#define SCPP_HAS_UI 0
#endif

namespace scpp::webview_runtime {
class view;
}

namespace scpp::ui {

class event;

class app final {
public:
	bool_t exit_requested = bool_t(false);
	string_t backend = string_t("");
	std::deque<shared_p<event>> pending_events;
	void *native_handle = nullptr;
	void *native_state = nullptr;
};

class window final {
public:
	shared_p<app> app_handle = null;
	string_t title = string_t("");
	int_t<> width = int_t<>(0);
	int_t<> height = int_t<>(0);
	bool_t visible = bool_t(false);
	bool_t closed = bool_t(false);
	void *native_handle = nullptr;
	void *native_delegate = nullptr;
	void *native_state = nullptr;
};

class event final {
public:
	string_t type = string_t("");
	shared_p<window> window_handle = null;
	shared_p<webview_runtime::view> webview_handle = null;
	string_t message = string_t("");
	string_t url = string_t("");
};

#if SCPP_HAS_UI
[[nodiscard]] result<shared_p<app>> app_create();
[[nodiscard]] result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t<> &width, const int_t<> &height);
[[nodiscard]] result<bool_t> window_show(const shared_p<window> &target);
[[nodiscard]] result<bool_t> window_close(const shared_p<window> &target);
[[nodiscard]] bool_t app_poll(const shared_p<app> &owner);
[[nodiscard]] shared_p<event> app_next_event(const shared_p<app> &owner);
[[nodiscard]] string_t event_type(const shared_p<event> &target);
[[nodiscard]] shared_p<window> event_window(const shared_p<event> &target);
[[nodiscard]] shared_p<webview_runtime::view> event_webview(const shared_p<event> &target);
[[nodiscard]] string_t event_text(const shared_p<event> &target);
[[nodiscard]] string_t event_message(const shared_p<event> &target);
[[nodiscard]] string_t event_url(const shared_p<event> &target);
void app_exit(const shared_p<app> &owner);
#else
inline result<shared_p<app>> app_create() {
	return error_t(string_t("ui_app_create(): ui runtime module is not enabled in this build"));
}

inline result<shared_p<window>> window_create(const shared_p<app> &, const string_t &, const int_t<> &, const int_t<> &) {
	return error_t(string_t("ui_window_create(): ui runtime module is not enabled in this build"));
}

inline result<bool_t> window_show(const shared_p<window> &) {
	return error_t(string_t("ui_window_show(): ui runtime module is not enabled in this build"));
}

inline result<bool_t> window_close(const shared_p<window> &) {
	return error_t(string_t("ui_window_close(): ui runtime module is not enabled in this build"));
}

inline bool_t app_poll(const shared_p<app> &) {
	return bool_t(false);
}

inline shared_p<event> app_next_event(const shared_p<app> &) {
	return null;
}

inline string_t event_type(const shared_p<event> &) {
	return string_t("");
}

inline shared_p<window> event_window(const shared_p<event> &) {
	return null;
}

inline string_t event_text(const shared_p<event> &) {
	return string_t("");
}

inline shared_p<webview_runtime::view> event_webview(const shared_p<event> &) {
	return null;
}

inline string_t event_message(const shared_p<event> &) {
	return string_t("");
}

inline string_t event_url(const shared_p<event> &) {
	return string_t("");
}

inline void app_exit(const shared_p<app> &) {
}
#endif

} // namespace scpp::ui

namespace scpp {

using ui_app = ui::app;
using ui_window = ui::window;
using ui_event = ui::event;

} // namespace scpp
