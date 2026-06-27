#pragma once

#include "modules/ui/ui.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"

#ifndef SCPP_HAS_WEBVIEW
#define SCPP_HAS_WEBVIEW 0
#endif

#if SCPP_HAS_WEBVIEW && defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
#include <jni.h>
#endif

namespace scpp::webview_runtime {

class view final {
public:
	shared_p<ui::window> window_handle = null;
	string_t current_url = string_t("");
	string_t app_root_path = string_t("");
	bool_t closed = bool_t(false);
	void *native_handle = nullptr;
	void *native_controller = nullptr;
	void *native_state = nullptr;
};

#if SCPP_HAS_WEBVIEW
[[nodiscard]] result<shared_p<view>> create(const shared_p<ui::window> &window);
[[nodiscard]] result<bool_t> load_url(const shared_p<view> &target, const string_t &url);
[[nodiscard]] result<bool_t> load_html(const shared_p<view> &target, const string_t &html);
[[nodiscard]] result<bool_t> load_app(const shared_p<view> &target, const string_t &folder);
[[nodiscard]] result<bool_t> eval(const shared_p<view> &target, const string_t &script);
[[nodiscard]] result<bool_t> enqueue_event(const shared_p<view> &target, const string_t &type, const string_t &message = string_t(""), const string_t &url = string_t(""));
[[nodiscard]] result<bool_t> reply_ok(const shared_p<view> &target, const int_t &id, const string_t &value_json);
[[nodiscard]] result<bool_t> reply_error(const shared_p<view> &target, const int_t &id, const string_t &code, const string_t &message);
[[nodiscard]] int_t message_id(const shared_p<ui::event> &message);
[[nodiscard]] string_t message_command(const shared_p<ui::event> &message);
[[nodiscard]] string_t message_payload_json(const shared_p<ui::event> &message);
void close(const shared_p<view> &target);
#if defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
[[nodiscard]] result<bool_t> android_attach_activity_webview(const shared_p<ui::window> &window, JavaVM *vm, jobject activity, jobject webview);
[[nodiscard]] result<bool_t> android_dispatch_bridge_message(const shared_p<view> &target, const string_t &message_json);
void android_detach_activity_webview(const shared_p<ui::window> &window);
#endif
#else
inline result<shared_p<view>> create(const shared_p<ui::window> &) {
	return error_t(string_t("webview_create(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> load_url(const shared_p<view> &, const string_t &) {
	return error_t(string_t("webview_load_url(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> load_html(const shared_p<view> &, const string_t &) {
	return error_t(string_t("webview_load_html(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> load_app(const shared_p<view> &, const string_t &) {
	return error_t(string_t("webview_load_app(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> eval(const shared_p<view> &, const string_t &) {
	return error_t(string_t("webview_eval(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> enqueue_event(const shared_p<view> &, const string_t &, const string_t & = string_t(""), const string_t & = string_t("")) {
	return error_t(string_t("webview_enqueue_event(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> reply_ok(const shared_p<view> &, const int_t &, const string_t &) {
	return error_t(string_t("webview_reply_ok(): webview runtime module is not enabled in this build"));
}

inline result<bool_t> reply_error(const shared_p<view> &, const int_t &, const string_t &, const string_t &) {
	return error_t(string_t("webview_reply_error(): webview runtime module is not enabled in this build"));
}

inline int_t message_id(const shared_p<ui::event> &) {
	return int_t(0);
}

inline string_t message_command(const shared_p<ui::event> &) {
	return string_t("");
}

inline string_t message_payload_json(const shared_p<ui::event> &) {
	return string_t("null");
}

inline void close(const shared_p<view> &) {
}
#endif

} // namespace scpp::webview_runtime

namespace scpp {

using webview = webview_runtime::view;

} // namespace scpp
