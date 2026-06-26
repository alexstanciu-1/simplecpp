#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#endif
#if defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
#import <AppKit/AppKit.h>
#import <WebKit/WebKit.h>
#endif
#if defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#ifndef NOMINMAX
#define NOMINMAX
#endif
#include <windows.h>
#include <unknwn.h>
#include <wrl.h>
#include <WebView2.h>
#endif

#include <memory>
#include <string>

namespace scpp::webview_runtime {

namespace {

#if defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2

struct win32_webview_state final {
	HWND parent = nullptr;
	Microsoft::WRL::ComPtr<ICoreWebView2Environment> environment;
	Microsoft::WRL::ComPtr<ICoreWebView2Controller> controller;
	Microsoft::WRL::ComPtr<ICoreWebView2> core;
	std::wstring pending_url;
	std::wstring pending_html;
	std::wstring pending_script;
	bool closed = false;
};

using win32_webview_state_ptr = std::shared_ptr<win32_webview_state>;

[[nodiscard]] std::wstring utf8_to_wide(const std::string &value) {
	if (value.empty()) {
		return std::wstring();
	}
	const int needed = MultiByteToWideChar(CP_UTF8, 0, value.c_str(), static_cast<int>(value.size()), nullptr, 0);
	if (needed <= 0) {
		return std::wstring();
	}
	std::wstring result(static_cast<std::size_t>(needed), L'\0');
	MultiByteToWideChar(CP_UTF8, 0, value.c_str(), static_cast<int>(value.size()), result.data(), needed);
	return result;
}

void resize_win32_controller(win32_webview_state *state) {
	if (state == nullptr || state->controller == nullptr || state->parent == nullptr) {
		return;
	}
	RECT bounds{};
	GetClientRect(state->parent, &bounds);
	state->controller->put_Bounds(bounds);
}

void flush_win32_pending(win32_webview_state *state) {
	if (state == nullptr || state->core == nullptr || state->closed) {
		return;
	}
	if (!state->pending_html.empty()) {
		state->core->NavigateToString(state->pending_html.c_str());
		state->pending_html.clear();
		state->pending_url.clear();
	} else if (!state->pending_url.empty()) {
		state->core->Navigate(state->pending_url.c_str());
		state->pending_url.clear();
	}
	if (!state->pending_script.empty()) {
		state->core->ExecuteScript(state->pending_script.c_str(), nullptr);
		state->pending_script.clear();
	}
}

[[nodiscard]] win32_webview_state *get_win32_state(const shared_p<view> &target) {
	auto *holder = static_cast<win32_webview_state_ptr *>(target->native_state);
	if (holder == nullptr) {
		return nullptr;
	}
	return holder->get();
}

#endif

[[nodiscard]] result<shared_p<view>> require_view(const shared_p<view> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSWindow *parent = static_cast<NSWindow *>(window->native_handle);
		NSView *content = [parent contentView];
		if (content == nil) {
			return error_t(string_t("webview_create(): AppKit window has no content view"));
		}

		WKWebView *native = [[WKWebView alloc] initWithFrame:[content bounds]];
		if (native == nil) {
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		[native setAutoresizingMask:NSViewWidthSizable | NSViewHeightSizable];
		[content addSubview:native];

		auto target = shared<view>();
		target->window_handle = window;
		target->native_handle = native;
		return target;
	}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
	HWND parent = static_cast<HWND>(window->native_handle);
	auto state = std::make_shared<win32_webview_state>();
	state->parent = parent;

	auto target = shared<view>();
	target->window_handle = window;
	target->native_handle = parent;
	target->native_state = new win32_webview_state_ptr(state);

	HRESULT hr = CreateCoreWebView2EnvironmentWithOptions(
		nullptr,
		nullptr,
		nullptr,
		Microsoft::WRL::Callback<ICoreWebView2CreateCoreWebView2EnvironmentCompletedHandler>(
			[state](HRESULT result, ICoreWebView2Environment *environment) -> HRESULT {
				if (FAILED(result) || environment == nullptr || state->closed) {
					return result;
				}
				state->environment = environment;
				return environment->CreateCoreWebView2Controller(
					state->parent,
					Microsoft::WRL::Callback<ICoreWebView2CreateCoreWebView2ControllerCompletedHandler>(
						[state](HRESULT controller_result, ICoreWebView2Controller *controller) -> HRESULT {
							if (FAILED(controller_result) || controller == nullptr || state->closed) {
								return controller_result;
							}
							state->controller = controller;
							controller->get_CoreWebView2(&state->core);
							resize_win32_controller(state.get());
							flush_win32_pending(state.get());
							return S_OK;
						}
					).Get()
				);
			}
		).Get()
	);
	if (FAILED(hr)) {
		delete static_cast<win32_webview_state_ptr *>(target->native_state);
		target->native_state = nullptr;
		return error_t(string_t("webview_create(): WebView2 failed to start environment creation"));
	}
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSString *text = [NSString stringWithUTF8String:url.native_value().c_str()];
		NSURL *native_url = [NSURL URLWithString:text];
		if (native_url == nil) {
			return error_t(string_t("webview_load_url(): invalid url"));
		}
		NSURLRequest *request = [NSURLRequest requestWithURL:native_url];
		WKWebView *native = static_cast<WKWebView *>(target->native_handle);
		[native loadRequest:request];
		return bool_t(true);
	}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
	auto *state = get_win32_state(target);
	if (state == nullptr || state->closed) {
		return error_t(string_t("webview_load_url(): WebView2 state is not available"));
	}
	state->pending_url = utf8_to_wide(url.native_value());
	state->pending_html.clear();
	flush_win32_pending(state);
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSString *text = [NSString stringWithUTF8String:html.native_value().c_str()];
		WKWebView *native = static_cast<WKWebView *>(target->native_handle);
		[native loadHTMLString:text baseURL:nil];
		return bool_t(true);
	}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
	auto *state = get_win32_state(target);
	if (state == nullptr || state->closed) {
		return error_t(string_t("webview_load_html(): WebView2 state is not available"));
	}
	state->pending_html = utf8_to_wide(html.native_value());
	flush_win32_pending(state);
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSString *text = [NSString stringWithUTF8String:script.native_value().c_str()];
		WKWebView *native = static_cast<WKWebView *>(target->native_handle);
		[native evaluateJavaScript:text completionHandler:nil];
		return bool_t(true);
	}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
	auto *state = get_win32_state(target);
	if (state == nullptr || state->closed) {
		return error_t(string_t("webview_eval(): WebView2 state is not available"));
	}
	state->pending_script = utf8_to_wide(script.native_value());
	flush_win32_pending(state);
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
		if (target->native_handle != nullptr) {
			WKWebView *native = static_cast<WKWebView *>(target->native_handle);
			[native stopLoading];
			[native removeFromSuperview];
			[native release];
		}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
		auto *holder = static_cast<win32_webview_state_ptr *>(target->native_state);
		if (holder != nullptr) {
			win32_webview_state_ptr state = *holder;
			if (state != nullptr) {
				state->closed = true;
				if (state->controller != nullptr) {
					state->controller->Close();
				}
			}
			delete holder;
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
