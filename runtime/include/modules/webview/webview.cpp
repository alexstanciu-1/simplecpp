#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#include <string>

namespace scpp::webview_runtime {

namespace {

[[nodiscard]] result<shared_p<view>> require_view(const shared_p<view> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open webview"));
	}
	return target;
}

[[nodiscard]] bool valid_window(const shared_p<ui::window> &window) {
	return window.has_value().native_value()
		&& window.get() != nullptr
		&& !window->closed.native_value()
		&& window->native_handle != nullptr;
}

} // namespace

result<shared_p<view>> create(const shared_p<ui::window> &window) {
	if (!valid_window(window)) {
		return error_t(string_t("webview_create(): requires an open ui_window"));
	}

	return error_t(string_t("webview_create(): no native webview backend is selected in this build"));
}

result<bool_t> load_url(const shared_p<view> &target, const string_t &url) {
	auto checked = require_view(target, "webview_load_url()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	if (url.native_value().empty()) {
		return error_t(string_t("webview_load_url(): url must not be empty"));
	}
	target->current_url = url;
	return error_t(string_t("webview_load_url(): no native webview backend is selected in this build"));
}

result<bool_t> load_html(const shared_p<view> &target, const string_t &html) {
	auto checked = require_view(target, "webview_load_html()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	(void) html;
	return error_t(string_t("webview_load_html(): no native webview backend is selected in this build"));
}

result<bool_t> eval(const shared_p<view> &target, const string_t &script) {
	auto checked = require_view(target, "webview_eval()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	(void) script;
	return error_t(string_t("webview_eval(): no native webview backend is selected in this build"));
}

void close(const shared_p<view> &target) {
	if (target.has_value().native_value() && target.get() != nullptr) {
		target->closed = bool_t(true);
		target->native_handle = nullptr;
		target->native_controller = nullptr;
		target->native_state = nullptr;
	}
}

} // namespace scpp::webview_runtime

#endif
