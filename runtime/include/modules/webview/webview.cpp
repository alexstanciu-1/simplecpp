#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#endif

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

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
	GtkWidget *parent = static_cast<GtkWidget *>(window->native_handle);
	GtkWidget *native = webkit_web_view_new();
	if (native == nullptr) {
		return error_t(string_t("webview_create(): WebKitGTK failed to create a native webview"));
	}

	gtk_container_add(GTK_CONTAINER(parent), native);
	gtk_widget_show(native);

	auto target = shared<view>();
	target->window_handle = window;
	target->native_handle = native;
	return target;
#else
	return error_t(string_t("webview_create(): no native webview backend is selected in this build"));
#endif
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
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
	webkit_web_view_load_uri(WEBKIT_WEB_VIEW(target->native_handle), url.native_value().c_str());
	return bool_t(true);
#else
	return error_t(string_t("webview_load_url(): no native webview backend is selected in this build"));
#endif
}

result<bool_t> load_html(const shared_p<view> &target, const string_t &html) {
	auto checked = require_view(target, "webview_load_html()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
	webkit_web_view_load_html(WEBKIT_WEB_VIEW(target->native_handle), html.native_value().c_str(), nullptr);
	return bool_t(true);
#else
	(void) html;
	return error_t(string_t("webview_load_html(): no native webview backend is selected in this build"));
#endif
}

result<bool_t> eval(const shared_p<view> &target, const string_t &script) {
	auto checked = require_view(target, "webview_eval()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#if WEBKIT_CHECK_VERSION(2, 40, 0)
	webkit_web_view_evaluate_javascript(
		WEBKIT_WEB_VIEW(target->native_handle),
		script.native_value().c_str(),
		static_cast<gssize>(script.native_value().size()),
		nullptr,
		nullptr,
		nullptr,
		nullptr,
		nullptr
	);
#else
	webkit_web_view_run_javascript(
		WEBKIT_WEB_VIEW(target->native_handle),
		script.native_value().c_str(),
		nullptr,
		nullptr,
		nullptr
	);
#endif
	return bool_t(true);
#else
	(void) script;
	return error_t(string_t("webview_eval(): no native webview backend is selected in this build"));
#endif
}

void close(const shared_p<view> &target) {
	if (target.has_value().native_value() && target.get() != nullptr) {
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
		if (target->native_handle != nullptr) {
			gtk_widget_destroy(GTK_WIDGET(target->native_handle));
		}
#endif
		target->closed = bool_t(true);
		target->native_handle = nullptr;
		target->native_controller = nullptr;
		target->native_state = nullptr;
	}
}

} // namespace scpp::webview_runtime

#endif
