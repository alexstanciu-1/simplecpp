#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#endif
#if defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
#import <AppKit/AppKit.h>
#import <WebKit/WebKit.h>
#endif
#if defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
#import <UIKit/UIKit.h>
#import <WebKit/WebKit.h>
#endif
#if defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
#include <jni.h>
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

#if (defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW) || (defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW)
@interface ScppWebViewNavigationDelegate : NSObject<WKNavigationDelegate> {
	scpp::shared_p<scpp::webview_runtime::view> _target;
}
- (instancetype)initWithTarget:(scpp::shared_p<scpp::webview_runtime::view>)target;
@end

@implementation ScppWebViewNavigationDelegate
- (instancetype)initWithTarget:(scpp::shared_p<scpp::webview_runtime::view>)target
{
	self = [super init];
	if (self != nil) {
		_target = target;
	}
	return self;
}

- (NSString *)currentURLString:(WKWebView *)webView
{
	NSURL *url = webView.URL;
	if (url == nil) {
		return @"";
	}
	NSString *absolute = url.absoluteString;
	return absolute == nil ? @"" : absolute;
}

- (void)enqueueType:(const char *)type message:(NSString *)message url:(NSString *)url
{
	const char *messageText = message == nil ? "" : message.UTF8String;
	const char *urlText = url == nil ? "" : url.UTF8String;
	(void) scpp::webview_runtime::enqueue_event(
		_target,
		scpp::string_t(type),
		scpp::string_t(messageText == nullptr ? "" : messageText),
		scpp::string_t(urlText == nullptr ? "" : urlText)
	);
}

- (void)webView:(WKWebView *)webView didStartProvisionalNavigation:(WKNavigation *)navigation
{
	(void)navigation;
	[self enqueueType:"webview_navigation_started" message:@"" url:[self currentURLString:webView]];
}

- (void)webView:(WKWebView *)webView didFinishNavigation:(WKNavigation *)navigation
{
	(void)navigation;
	[self enqueueType:"webview_navigation_finished" message:@"" url:[self currentURLString:webView]];
}

- (void)webView:(WKWebView *)webView didFailNavigation:(WKNavigation *)navigation withError:(NSError *)error
{
	(void)navigation;
	[self enqueueType:"webview_load_failed" message:error.localizedDescription url:[self currentURLString:webView]];
}

- (void)webView:(WKWebView *)webView didFailProvisionalNavigation:(WKNavigation *)navigation withError:(NSError *)error
{
	(void)navigation;
	[self enqueueType:"webview_load_failed" message:error.localizedDescription url:[self currentURLString:webView]];
}
@end
#endif

namespace scpp::webview_runtime {

namespace {

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK

struct webkitgtk_callback_state final {
	shared_p<view> target;
};

void destroy_webkitgtk_callback_state(gpointer data, GClosure *) {
	delete static_cast<webkitgtk_callback_state *>(data);
}

void enqueue_webkitgtk_event(
	const shared_p<view> &target,
	const char *type,
	const string_t &message = string_t(""),
	const string_t &url = string_t("")
) {
	(void) enqueue_event(target, string_t(type), message, url);
}

[[nodiscard]] string_t webkitgtk_current_uri(WebKitWebView *native) {
	const gchar *uri = webkit_web_view_get_uri(native);
	if (uri == nullptr) {
		return string_t("");
	}
	return string_t(uri);
}

void handle_webkitgtk_load_changed(WebKitWebView *native, WebKitLoadEvent load_event, gpointer data) {
	auto *state = static_cast<webkitgtk_callback_state *>(data);
	if (state == nullptr) {
		return;
	}
	if (load_event == WEBKIT_LOAD_STARTED) {
		enqueue_webkitgtk_event(state->target, "webview_navigation_started", string_t(""), webkitgtk_current_uri(native));
		return;
	}
	if (load_event == WEBKIT_LOAD_FINISHED) {
		enqueue_webkitgtk_event(state->target, "webview_navigation_finished", string_t(""), webkitgtk_current_uri(native));
	}
}

gboolean handle_webkitgtk_load_failed(WebKitWebView *native, WebKitLoadEvent, gchar *failing_uri, GError *error, gpointer data) {
	auto *state = static_cast<webkitgtk_callback_state *>(data);
	if (state == nullptr) {
		return FALSE;
	}
	const char *message = error != nullptr && error->message != nullptr ? error->message : "";
	string_t uri = failing_uri != nullptr ? string_t(failing_uri) : webkitgtk_current_uri(native);
	enqueue_webkitgtk_event(state->target, "webview_load_failed", string_t(message), uri);
	return FALSE;
}

void handle_webkitgtk_title_changed(GObject *object, GParamSpec *, gpointer data) {
	auto *state = static_cast<webkitgtk_callback_state *>(data);
	if (state == nullptr) {
		return;
	}
	WebKitWebView *native = WEBKIT_WEB_VIEW(object);
	const gchar *title = webkit_web_view_get_title(native);
	if (title == nullptr) {
		return;
	}
	enqueue_webkitgtk_event(state->target, "webview_title_changed", string_t(title), webkitgtk_current_uri(native));
}

#endif

#if defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2

struct win32_webview_state final {
	HWND parent = nullptr;
	Microsoft::WRL::ComPtr<ICoreWebView2Environment> environment;
	Microsoft::WRL::ComPtr<ICoreWebView2Controller> controller;
	Microsoft::WRL::ComPtr<ICoreWebView2> core;
	EventRegistrationToken navigation_starting_token{};
	EventRegistrationToken navigation_completed_token{};
	EventRegistrationToken web_message_received_token{};
	std::wstring pending_url;
	std::wstring pending_html;
	std::wstring pending_script;
	bool has_navigation_starting_token = false;
	bool has_navigation_completed_token = false;
	bool has_web_message_received_token = false;
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

[[nodiscard]] string_t wide_to_utf8(const wchar_t *value) {
	if (value == nullptr || value[0] == L'\0') {
		return string_t("");
	}
	const int needed = WideCharToMultiByte(CP_UTF8, 0, value, -1, nullptr, 0, nullptr, nullptr);
	if (needed <= 1) {
		return string_t("");
	}
	std::string result(static_cast<std::size_t>(needed), '\0');
	WideCharToMultiByte(CP_UTF8, 0, value, -1, result.data(), needed, nullptr, nullptr);
	if (!result.empty() && result.back() == '\0') {
		result.pop_back();
	}
	return string_t(result);
}

[[nodiscard]] string_t take_win32_string(PWSTR value) {
	string_t result = wide_to_utf8(value);
	if (value != nullptr) {
		CoTaskMemFree(value);
	}
	return result;
}

void enqueue_win32_event(
	const shared_p<view> &target,
	const char *type,
	const string_t &message = string_t(""),
	const string_t &url = string_t("")
) {
	(void) enqueue_event(target, string_t(type), message, url);
}

[[nodiscard]] string_t win32_current_source(ICoreWebView2 *core) {
	if (core == nullptr) {
		return string_t("");
	}
	PWSTR source = nullptr;
	if (FAILED(core->get_Source(&source))) {
		return string_t("");
	}
	return take_win32_string(source);
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

void remove_win32_event_handlers(win32_webview_state *state) {
	if (state == nullptr || state->core == nullptr) {
		return;
	}
	if (state->has_navigation_starting_token) {
		state->core->remove_NavigationStarting(state->navigation_starting_token);
		state->has_navigation_starting_token = false;
	}
	if (state->has_navigation_completed_token) {
		state->core->remove_NavigationCompleted(state->navigation_completed_token);
		state->has_navigation_completed_token = false;
	}
	if (state->has_web_message_received_token) {
		state->core->remove_WebMessageReceived(state->web_message_received_token);
		state->has_web_message_received_token = false;
	}
}

void register_win32_event_handlers(const win32_webview_state_ptr &state, const shared_p<view> &target) {
	if (state == nullptr || state->core == nullptr || state->closed) {
		return;
	}

	HRESULT hr = state->core->add_NavigationStarting(
		Microsoft::WRL::Callback<ICoreWebView2NavigationStartingEventHandler>(
			[target](ICoreWebView2 *, ICoreWebView2NavigationStartingEventArgs *args) -> HRESULT {
				PWSTR uri = nullptr;
				string_t url = string_t("");
				if (args != nullptr && SUCCEEDED(args->get_Uri(&uri))) {
					url = take_win32_string(uri);
				}
				enqueue_win32_event(target, "webview_navigation_started", string_t(""), url);
				return S_OK;
			}
		).Get(),
		&state->navigation_starting_token
	);
	state->has_navigation_starting_token = SUCCEEDED(hr);

	hr = state->core->add_NavigationCompleted(
		Microsoft::WRL::Callback<ICoreWebView2NavigationCompletedEventHandler>(
			[target](ICoreWebView2 *sender, ICoreWebView2NavigationCompletedEventArgs *args) -> HRESULT {
				BOOL success = FALSE;
				if (args != nullptr) {
					(void) args->get_IsSuccess(&success);
				}
				if (success) {
					enqueue_win32_event(target, "webview_navigation_finished", string_t(""), win32_current_source(sender));
					return S_OK;
				}
				COREWEBVIEW2_WEB_ERROR_STATUS status = COREWEBVIEW2_WEB_ERROR_STATUS_UNKNOWN;
				if (args != nullptr) {
					(void) args->get_WebErrorStatus(&status);
				}
				enqueue_win32_event(
					target,
					"webview_load_failed",
					string_t(std::string("WebView2 navigation failed: ") + std::to_string(static_cast<int>(status))),
					win32_current_source(sender)
				);
				return S_OK;
			}
		).Get(),
		&state->navigation_completed_token
	);
	state->has_navigation_completed_token = SUCCEEDED(hr);

	hr = state->core->add_WebMessageReceived(
		Microsoft::WRL::Callback<ICoreWebView2WebMessageReceivedEventHandler>(
			[target](ICoreWebView2 *sender, ICoreWebView2WebMessageReceivedEventArgs *args) -> HRESULT {
				PWSTR message = nullptr;
				string_t payload = string_t("");
				if (args != nullptr && SUCCEEDED(args->TryGetWebMessageAsString(&message))) {
					payload = take_win32_string(message);
				}
				enqueue_win32_event(target, "webview_message", payload, win32_current_source(sender));
				return S_OK;
			}
		).Get(),
		&state->web_message_received_token
	);
	state->has_web_message_received_token = SUCCEEDED(hr);
}

[[nodiscard]] win32_webview_state *get_win32_state(const shared_p<view> &target) {
	auto *holder = static_cast<win32_webview_state_ptr *>(target->native_state);
	if (holder == nullptr) {
		return nullptr;
	}
	return holder->get();
}

#endif

#if defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW

struct android_activity_webview_bridge final {
	JavaVM *vm = nullptr;
	jobject activity = nullptr;
	jobject webview = nullptr;
};

struct android_webview_state final {
	JavaVM *vm = nullptr;
	jobject activity = nullptr;
	jobject webview = nullptr;
};

[[nodiscard]] JNIEnv *android_env(JavaVM *vm) {
	if (vm == nullptr) {
		return nullptr;
	}
	JNIEnv *env = nullptr;
	if (vm->GetEnv(reinterpret_cast<void **>(&env), JNI_VERSION_1_6) == JNI_OK) {
		return env;
	}
	if (vm->AttachCurrentThread(&env, nullptr) == JNI_OK) {
		return env;
	}
	return nullptr;
}

[[nodiscard]] android_activity_webview_bridge *get_android_bridge(const shared_p<ui::window> &window) {
	if (!window.has_value().native_value() || window.get() == nullptr) {
		return nullptr;
	}
	return static_cast<android_activity_webview_bridge *>(window->native_state);
}

[[nodiscard]] android_webview_state *get_android_state(const shared_p<view> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return nullptr;
	}
	return static_cast<android_webview_state *>(target->native_state);
}

[[nodiscard]] result<bool_t> android_clear_exception(JNIEnv *env, const char *function_name) {
	if (env != nullptr && env->ExceptionCheck()) {
		env->ExceptionClear();
		return error_t(string_t(std::string(function_name) + " Android WebView JNI call failed"));
	}
	return bool_t(true);
}

[[nodiscard]] result<bool_t> android_call_webview_string_method(
	android_webview_state *state,
	const char *function_name,
	const char *method_name,
	const char *method_signature,
	const string_t &value
) {
	if (state == nullptr || state->vm == nullptr || state->webview == nullptr) {
		return error_t(string_t(std::string(function_name) + " Android WebView state is not available"));
	}
	JNIEnv *env = android_env(state->vm);
	if (env == nullptr) {
		return error_t(string_t(std::string(function_name) + " Android JNI environment is not available"));
	}
	jclass webview_class = env->GetObjectClass(state->webview);
	if (webview_class == nullptr) {
		return error_t(string_t(std::string(function_name) + " Android WebView class is not available"));
	}
	jmethodID method = env->GetMethodID(webview_class, method_name, method_signature);
	env->DeleteLocalRef(webview_class);
	if (method == nullptr) {
		return error_t(string_t(std::string(function_name) + " Android WebView method is not available"));
	}
	jstring text = env->NewStringUTF(value.native_value().c_str());
	if (text == nullptr) {
		return error_t(string_t(std::string(function_name) + " Android failed to allocate a Java string"));
	}
	env->CallVoidMethod(state->webview, method, text);
	env->DeleteLocalRef(text);
	return android_clear_exception(env, function_name);
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
	auto *load_state = new webkitgtk_callback_state{target};
	g_signal_connect_data(
		G_OBJECT(native),
		"load-changed",
		G_CALLBACK(handle_webkitgtk_load_changed),
		load_state,
		destroy_webkitgtk_callback_state,
		static_cast<GConnectFlags>(0)
	);
	auto *failed_state = new webkitgtk_callback_state{target};
	g_signal_connect_data(
		G_OBJECT(native),
		"load-failed",
		G_CALLBACK(handle_webkitgtk_load_failed),
		failed_state,
		destroy_webkitgtk_callback_state,
		static_cast<GConnectFlags>(0)
	);
	auto *title_state = new webkitgtk_callback_state{target};
	g_signal_connect_data(
		G_OBJECT(native),
		"notify::title",
		G_CALLBACK(handle_webkitgtk_title_changed),
		title_state,
		destroy_webkitgtk_callback_state,
		static_cast<GConnectFlags>(0)
	);
	(void) enqueue_event(target, string_t("webview_ready"));
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
		ScppWebViewNavigationDelegate *delegate = [[ScppWebViewNavigationDelegate alloc] initWithTarget:target];
		native.navigationDelegate = delegate;
		target->native_controller = delegate;
		(void) enqueue_event(target, string_t("webview_ready"));
		return target;
	}
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
	@autoreleasepool {
		UIWindow *parent = static_cast<UIWindow *>(window->native_handle);
		UIView *content = parent.rootViewController.view;
		if (content == nil) {
			return error_t(string_t("webview_create(): UIKit window has no root content view"));
		}

		WKWebView *native = [[WKWebView alloc] initWithFrame:content.bounds];
		if (native == nil) {
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		native.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
		[content addSubview:native];

		auto target = shared<view>();
		target->window_handle = window;
		target->native_handle = native;
		ScppWebViewNavigationDelegate *delegate = [[ScppWebViewNavigationDelegate alloc] initWithTarget:target];
		native.navigationDelegate = delegate;
		target->native_controller = delegate;
		(void) enqueue_event(target, string_t("webview_ready"));
		return target;
	}
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
	auto *bridge = get_android_bridge(window);
	if (bridge == nullptr || bridge->vm == nullptr || bridge->activity == nullptr || bridge->webview == nullptr) {
		return error_t(string_t("webview_create(): Android WebView requires an attached JNI activity bridge"));
	}
	JNIEnv *env = android_env(bridge->vm);
	if (env == nullptr) {
		return error_t(string_t("webview_create(): Android JNI environment is not available"));
	}
	auto *state = new android_webview_state();
	state->vm = bridge->vm;
	state->activity = env->NewGlobalRef(bridge->activity);
	state->webview = env->NewGlobalRef(bridge->webview);
	if (state->activity == nullptr || state->webview == nullptr) {
		if (state->activity != nullptr) {
			env->DeleteGlobalRef(state->activity);
		}
		if (state->webview != nullptr) {
			env->DeleteGlobalRef(state->webview);
		}
		delete state;
		return error_t(string_t("webview_create(): Android failed to retain WebView bridge objects"));
	}

	auto target = shared<view>();
	target->window_handle = window;
	target->native_handle = state->webview;
	target->native_state = state;
	return target;
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
			[state, target](HRESULT result, ICoreWebView2Environment *environment) -> HRESULT {
				if (FAILED(result) || environment == nullptr || state->closed) {
					return result;
				}
				state->environment = environment;
				return environment->CreateCoreWebView2Controller(
					state->parent,
					Microsoft::WRL::Callback<ICoreWebView2CreateCoreWebView2ControllerCompletedHandler>(
						[state, target](HRESULT controller_result, ICoreWebView2Controller *controller) -> HRESULT {
							if (FAILED(controller_result) || controller == nullptr || state->closed) {
								return controller_result;
							}
							state->controller = controller;
							controller->get_CoreWebView2(&state->core);
							register_win32_event_handlers(state, target);
							resize_win32_controller(state.get());
							enqueue_win32_event(target, "webview_ready");
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
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
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
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
	return android_call_webview_string_method(get_android_state(target), "webview_load_url()", "loadUrl", "(Ljava/lang/String;)V", url);
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
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
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
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
	auto *state = get_android_state(target);
	if (state == nullptr || state->vm == nullptr || state->webview == nullptr) {
		return error_t(string_t("webview_load_html(): Android WebView state is not available"));
	}
	JNIEnv *env = android_env(state->vm);
	if (env == nullptr) {
		return error_t(string_t("webview_load_html(): Android JNI environment is not available"));
	}
	jclass webview_class = env->GetObjectClass(state->webview);
	if (webview_class == nullptr) {
		return error_t(string_t("webview_load_html(): Android WebView class is not available"));
	}
	jmethodID load_data = env->GetMethodID(webview_class, "loadDataWithBaseURL", "(Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;)V");
	env->DeleteLocalRef(webview_class);
	if (load_data == nullptr) {
		return error_t(string_t("webview_load_html(): Android WebView loadDataWithBaseURL method is not available"));
	}
	jstring base_url = nullptr;
	jstring data = env->NewStringUTF(html.native_value().c_str());
	jstring mime_type = env->NewStringUTF("text/html");
	jstring encoding = env->NewStringUTF("UTF-8");
	jstring history_url = nullptr;
	if (data == nullptr || mime_type == nullptr || encoding == nullptr) {
		if (data != nullptr) {
			env->DeleteLocalRef(data);
		}
		if (mime_type != nullptr) {
			env->DeleteLocalRef(mime_type);
		}
		if (encoding != nullptr) {
			env->DeleteLocalRef(encoding);
		}
		return error_t(string_t("webview_load_html(): Android failed to allocate Java strings"));
	}
	env->CallVoidMethod(state->webview, load_data, base_url, data, mime_type, encoding, history_url);
	env->DeleteLocalRef(data);
	env->DeleteLocalRef(mime_type);
	env->DeleteLocalRef(encoding);
	return android_clear_exception(env, "webview_load_html()");
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
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
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
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
	auto *state = get_android_state(target);
	if (state == nullptr || state->vm == nullptr || state->webview == nullptr) {
		return error_t(string_t("webview_eval(): Android WebView state is not available"));
	}
	JNIEnv *env = android_env(state->vm);
	if (env == nullptr) {
		return error_t(string_t("webview_eval(): Android JNI environment is not available"));
	}
	jclass webview_class = env->GetObjectClass(state->webview);
	if (webview_class == nullptr) {
		return error_t(string_t("webview_eval(): Android WebView class is not available"));
	}
	jmethodID evaluate = env->GetMethodID(webview_class, "evaluateJavascript", "(Ljava/lang/String;Landroid/webkit/ValueCallback;)V");
	env->DeleteLocalRef(webview_class);
	if (evaluate == nullptr) {
		return error_t(string_t("webview_eval(): Android WebView evaluateJavascript method is not available"));
	}
	jstring text = env->NewStringUTF(script.native_value().c_str());
	if (text == nullptr) {
		return error_t(string_t("webview_eval(): Android failed to allocate a Java string"));
	}
	env->CallVoidMethod(state->webview, evaluate, text, nullptr);
	env->DeleteLocalRef(text);
	return android_clear_exception(env, "webview_eval()");
#else
	(void) script;
	return error_t(string_t("webview_eval(): no native webview backend is selected in this build"));
#endif
}

result<bool_t> enqueue_event(const shared_p<view> &target, const string_t &type, const string_t &message, const string_t &url) {
	auto checked = require_view(target, "webview_enqueue_event()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	if (type.native_value().empty()) {
		return error_t(string_t("webview_enqueue_event(): event type must not be empty"));
	}
	if (!target->window_handle.has_value().native_value() || target->window_handle.get() == nullptr) {
		return error_t(string_t("webview_enqueue_event(): webview has no ui_window"));
	}
	auto app = target->window_handle->app_handle;
	if (!app.has_value().native_value() || app.get() == nullptr) {
		return error_t(string_t("webview_enqueue_event(): ui_window has no ui_app event queue"));
	}

	auto event_value = shared<ui::event>();
	event_value->type = type;
	event_value->window_handle = target->window_handle;
	event_value->webview_handle = target;
	event_value->message = message;
	event_value->url = url;
	app->pending_events.push_back(event_value);
	return bool_t(true);
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
			native.navigationDelegate = nil;
			[native removeFromSuperview];
			[native release];
		}
		if (target->native_controller != nullptr) {
			[static_cast<ScppWebViewNavigationDelegate *>(target->native_controller) release];
		}
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
		if (target->native_handle != nullptr) {
			WKWebView *native = static_cast<WKWebView *>(target->native_handle);
			[native stopLoading];
			native.navigationDelegate = nil;
			[native removeFromSuperview];
			[native release];
		}
		if (target->native_controller != nullptr) {
			[static_cast<ScppWebViewNavigationDelegate *>(target->native_controller) release];
		}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
		auto *holder = static_cast<win32_webview_state_ptr *>(target->native_state);
		if (holder != nullptr) {
			win32_webview_state_ptr state = *holder;
			if (state != nullptr) {
				state->closed = true;
				remove_win32_event_handlers(state.get());
				if (state->controller != nullptr) {
					state->controller->Close();
				}
			}
			delete holder;
		}
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
		auto *state = get_android_state(target);
		if (state != nullptr) {
			JNIEnv *env = android_env(state->vm);
			if (env != nullptr && state->webview != nullptr) {
				jclass webview_class = env->GetObjectClass(state->webview);
				if (webview_class != nullptr) {
					jmethodID destroy = env->GetMethodID(webview_class, "destroy", "()V");
					env->DeleteLocalRef(webview_class);
					if (destroy != nullptr) {
						env->CallVoidMethod(state->webview, destroy);
						if (env->ExceptionCheck()) {
							env->ExceptionClear();
						}
					}
				}
				env->DeleteGlobalRef(state->webview);
			}
			if (env != nullptr && state->activity != nullptr) {
				env->DeleteGlobalRef(state->activity);
			}
			delete state;
		}
#endif
		target->closed = bool_t(true);
		target->native_handle = nullptr;
		target->native_controller = nullptr;
		target->native_state = nullptr;
	}
}

#if defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW

result<bool_t> android_attach_activity_webview(const shared_p<ui::window> &window, JavaVM *vm, jobject activity, jobject webview) {
	if (!window.has_value().native_value() || window.get() == nullptr) {
		return error_t(string_t("webview_android_attach_activity_webview(): requires a ui_window"));
	}
	if (vm == nullptr || activity == nullptr || webview == nullptr) {
		return error_t(string_t("webview_android_attach_activity_webview(): requires non-null JavaVM, Activity, and WebView"));
	}
	JNIEnv *env = android_env(vm);
	if (env == nullptr) {
		return error_t(string_t("webview_android_attach_activity_webview(): Android JNI environment is not available"));
	}

	android_detach_activity_webview(window);
	auto *bridge = new android_activity_webview_bridge();
	bridge->vm = vm;
	bridge->activity = env->NewGlobalRef(activity);
	bridge->webview = env->NewGlobalRef(webview);
	if (bridge->activity == nullptr || bridge->webview == nullptr) {
		if (bridge->activity != nullptr) {
			env->DeleteGlobalRef(bridge->activity);
		}
		if (bridge->webview != nullptr) {
			env->DeleteGlobalRef(bridge->webview);
		}
		delete bridge;
		return error_t(string_t("webview_android_attach_activity_webview(): Android failed to retain bridge objects"));
	}

	window->native_handle = bridge->activity;
	window->native_state = bridge;
	return bool_t(true);
}

void android_detach_activity_webview(const shared_p<ui::window> &window) {
	auto *bridge = get_android_bridge(window);
	if (bridge == nullptr) {
		return;
	}
	JNIEnv *env = android_env(bridge->vm);
	if (env != nullptr) {
		if (bridge->activity != nullptr) {
			env->DeleteGlobalRef(bridge->activity);
		}
		if (bridge->webview != nullptr) {
			env->DeleteGlobalRef(bridge->webview);
		}
	}
	delete bridge;
	window->native_state = nullptr;
	window->native_handle = nullptr;
}

#endif

} // namespace scpp::webview_runtime

#endif
