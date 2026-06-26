#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#if !WEBKIT_CHECK_VERSION(2, 22, 0)
#include <JavaScriptCore/JavaScript.h>
#endif
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

#include <charconv>
#include <filesystem>
#include <memory>
#include <sstream>
#include <string>
#include <string_view>

namespace scpp::webview_runtime {

constexpr const char *bridge_script = R"JS(
(function () {
	if (window.scpp && typeof window.scpp.invoke === "function") {
		return;
	}

	var nextId = 1;
	var pending = new Map();

	function post(message) {
		var text = JSON.stringify(message);
		if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.scpp) {
			window.webkit.messageHandlers.scpp.postMessage(text);
			return;
		}
		if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
			window.chrome.webview.postMessage(text);
			return;
		}
		if (window.ipc && window.ipc.postMessage) {
			window.ipc.postMessage(text);
			return;
		}
		throw new Error("Simple C++ WebView bridge is not available");
	}

	Object.defineProperty(window, "scpp", {
		value: {
			invoke: function (command, payload) {
				if (typeof command !== "string" || command.length === 0) {
					return Promise.reject(new Error("scpp.invoke(command, payload) requires a non-empty command string"));
				}
				var id = nextId++;
				var message = {
					id: id,
					kind: "invoke",
					command: command,
					payload: payload === undefined ? null : payload
				};
				return new Promise(function (resolve, reject) {
					pending.set(id, { resolve: resolve, reject: reject });
					try {
						post(message);
					} catch (error) {
						pending.delete(id);
						reject(error);
					}
				});
			},
			__resolve: function (response) {
				if (!response || typeof response.id !== "number") {
					return;
				}
				var entry = pending.get(response.id);
				if (!entry) {
					return;
				}
				pending.delete(response.id);
				if (response.ok) {
					entry.resolve(response.value);
				} else {
					var err = response.error || {};
					var jsError = new Error(err.message || "Simple C++ command failed");
					jsError.code = err.code || "scpp_error";
					jsError.detail = err;
					entry.reject(jsError);
				}
			}
		},
		configurable: false,
		enumerable: false,
		writable: false
	});
})();
)JS";

} // namespace scpp::webview_runtime

#if (defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW) || (defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW)
@interface ScppWKBridgeHandler : NSObject<WKScriptMessageHandler>
- (instancetype)initWithTarget:(scpp::shared_p<scpp::webview_runtime::view>)target;
@end

@implementation ScppWKBridgeHandler {
	scpp::shared_p<scpp::webview_runtime::view> _target;
}

- (instancetype)initWithTarget:(scpp::shared_p<scpp::webview_runtime::view>)target
{
	self = [super init];
	if (self != nil) {
		_target = target;
	}
	return self;
}

- (void)userContentController:(WKUserContentController *)userContentController didReceiveScriptMessage:(WKScriptMessage *)message
{
	(void)userContentController;
	if (!_target.has_value().native_value() || _target.get() == nullptr) {
		return;
	}

	std::string text;
	if ([message.body isKindOfClass:[NSString class]]) {
		text = [(NSString *)message.body UTF8String];
	} else {
		text = [[message.body description] UTF8String];
	}

	auto window = _target->window_handle;
	if (!window.has_value().native_value() || window.get() == nullptr || !window->app_handle.has_value().native_value()) {
		return;
	}

	auto event_value = scpp::shared<scpp::ui::event>();
	event_value->type = scpp::string_t("webview_message");
	event_value->window_handle = window;
	event_value->text = scpp::string_t(text);
	window->app_handle->pending_events.push_back(event_value);
}
@end
#endif

namespace scpp::webview_runtime {

namespace {

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
struct bridge_state final {
	shared_p<view> target;
};

void handle_bridge_message(WebKitUserContentManager *, WebKitJavascriptResult *message, gpointer data) {
	auto *state = static_cast<bridge_state *>(data);
	if (state == nullptr || !state->target.has_value().native_value() || state->target.get() == nullptr) {
		return;
	}

	std::string text;
#if WEBKIT_CHECK_VERSION(2, 22, 0)
	JSCValue *value = webkit_javascript_result_get_js_value(message);
	if (value != nullptr) {
		char *raw = jsc_value_to_string(value);
		if (raw != nullptr) {
			text = raw;
			g_free(raw);
		}
	}
#else
	JSGlobalContextRef context = webkit_javascript_result_get_global_context(message);
	JSValueRef value = webkit_javascript_result_get_value(message);
	JSStringRef js_text = JSValueToStringCopy(context, value, nullptr);
	if (js_text != nullptr) {
		const std::size_t max_size = JSStringGetMaximumUTF8CStringSize(js_text);
		std::string buffer(max_size, '\0');
		const std::size_t actual_size = JSStringGetUTF8CString(js_text, buffer.data(), max_size);
		if (actual_size > 0) {
			buffer.resize(actual_size - 1);
			text = buffer;
		}
		JSStringRelease(js_text);
	}
#endif

	auto window = state->target->window_handle;
	if (!window.has_value().native_value() || window.get() == nullptr || !window->app_handle.has_value().native_value()) {
		return;
	}

	auto event_value = shared<ui::event>();
	event_value->type = string_t("webview_message");
	event_value->window_handle = window;
	event_value->text = string_t(text);
	window->app_handle->pending_events.push_back(event_value);
}

[[nodiscard]] bool has_prefix_path(const std::filesystem::path &path, const std::filesystem::path &root) {
	auto path_it = path.begin();
	auto root_it = root.begin();
	for (; root_it != root.end(); ++root_it, ++path_it) {
		if (path_it == path.end() || *path_it != *root_it) {
			return false;
		}
	}
	return true;
}

[[nodiscard]] bool navigation_allowed(const shared_p<view> &target, const char *uri) {
	if (uri == nullptr || !target.has_value().native_value() || target.get() == nullptr) {
		return false;
	}
	if (target->app_root_path.native_value().empty()) {
		return true;
	}

	const std::string value(uri);
	if (value == "about:blank" || value.starts_with("data:") || value.starts_with("blob:")) {
		return true;
	}
	if (!value.starts_with("file:")) {
		return false;
	}

	GError *error = nullptr;
	char *filename = g_filename_from_uri(uri, nullptr, &error);
	if (error != nullptr) {
		g_error_free(error);
	}
	if (filename == nullptr) {
		return false;
	}

	bool allowed = false;
	try {
		const auto path = std::filesystem::weakly_canonical(std::filesystem::path(filename));
		const auto root = std::filesystem::weakly_canonical(std::filesystem::path(target->app_root_path.native_value()));
		allowed = has_prefix_path(path, root);
	} catch (const std::filesystem::filesystem_error &) {
		allowed = false;
	}
	g_free(filename);
	return allowed;
}

gboolean handle_navigation_policy(WebKitWebView *, WebKitPolicyDecision *decision, WebKitPolicyDecisionType type, gpointer data) {
	if (type != WEBKIT_POLICY_DECISION_TYPE_NAVIGATION_ACTION) {
		return FALSE;
	}
	auto *state = static_cast<bridge_state *>(data);
	if (state == nullptr || !state->target.has_value().native_value()) {
		webkit_policy_decision_ignore(decision);
		return TRUE;
	}

	WebKitNavigationPolicyDecision *navigation = WEBKIT_NAVIGATION_POLICY_DECISION(decision);
	WebKitURIRequest *request = webkit_navigation_policy_decision_get_request(navigation);
	const char *uri = request == nullptr ? nullptr : webkit_uri_request_get_uri(request);
	if (navigation_allowed(state->target, uri)) {
		webkit_policy_decision_use(decision);
	} else {
		webkit_policy_decision_ignore(decision);
	}
	return TRUE;
}
#endif

[[nodiscard]] std::string json_string_literal(const std::string &value) {
	std::ostringstream out;
	out << '"';
	for (unsigned char ch : value) {
		switch (ch) {
			case '"': out << "\\\""; break;
			case '\\': out << "\\\\"; break;
			case '\b': out << "\\b"; break;
			case '\f': out << "\\f"; break;
			case '\n': out << "\\n"; break;
			case '\r': out << "\\r"; break;
			case '\t': out << "\\t"; break;
			default:
				if (ch < 0x20) {
					out << "\\u";
					const char *digits = "0123456789abcdef";
					out << "00" << digits[(ch >> 4) & 0x0f] << digits[ch & 0x0f];
				} else {
					out << static_cast<char>(ch);
				}
				break;
		}
	}
	out << '"';
	return out.str();
}

[[nodiscard]] std::size_t skip_ws(std::string_view text, std::size_t offset) {
	while (offset < text.size()) {
		const char ch = text[offset];
		if (ch != ' ' && ch != '\n' && ch != '\r' && ch != '\t') {
			break;
		}
		++offset;
	}
	return offset;
}

[[nodiscard]] std::size_t skip_json_string(std::string_view text, std::size_t offset) {
	if (offset >= text.size() || text[offset] != '"') {
		return std::string_view::npos;
	}
	++offset;
	while (offset < text.size()) {
		const char ch = text[offset++];
		if (ch == '\\') {
			if (offset >= text.size()) {
				return std::string_view::npos;
			}
			++offset;
			continue;
		}
		if (ch == '"') {
			return offset;
		}
	}
	return std::string_view::npos;
}

[[nodiscard]] std::size_t skip_json_value(std::string_view text, std::size_t offset) {
	offset = skip_ws(text, offset);
	if (offset >= text.size()) {
		return std::string_view::npos;
	}
	if (text[offset] == '"') {
		return skip_json_string(text, offset);
	}
	if (text[offset] == '{' || text[offset] == '[') {
		const char open = text[offset];
		const char close = open == '{' ? '}' : ']';
		std::size_t depth = 1;
		++offset;
		while (offset < text.size()) {
			if (text[offset] == '"') {
				offset = skip_json_string(text, offset);
				if (offset == std::string_view::npos) {
					return std::string_view::npos;
				}
				continue;
			}
			if (text[offset] == open) {
				++depth;
			} else if (text[offset] == close) {
				--depth;
				if (depth == 0) {
					return offset + 1;
				}
			}
			++offset;
		}
		return std::string_view::npos;
	}
	while (offset < text.size()) {
		const char ch = text[offset];
		if (ch == ',' || ch == '}' || ch == ']' || ch == ' ' || ch == '\n' || ch == '\r' || ch == '\t') {
			return offset;
		}
		++offset;
	}
	return offset;
}

[[nodiscard]] std::string decode_json_string(std::string_view value) {
	if (value.size() < 2 || value.front() != '"' || value.back() != '"') {
		return "";
	}
	std::string out;
	for (std::size_t offset = 1; offset + 1 < value.size(); ++offset) {
		const char ch = value[offset];
		if (ch != '\\') {
			out.push_back(ch);
			continue;
		}
		if (++offset + 1 >= value.size()) {
			break;
		}
		switch (value[offset]) {
			case '"': out.push_back('"'); break;
			case '\\': out.push_back('\\'); break;
			case '/': out.push_back('/'); break;
			case 'b': out.push_back('\b'); break;
			case 'f': out.push_back('\f'); break;
			case 'n': out.push_back('\n'); break;
			case 'r': out.push_back('\r'); break;
			case 't': out.push_back('\t'); break;
			default: out.push_back('?'); break;
		}
	}
	return out;
}

[[nodiscard]] bool top_level_json_field(std::string_view text, std::string_view key, std::size_t &value_start, std::size_t &value_end) {
	std::size_t offset = skip_ws(text, 0);
	if (offset >= text.size() || text[offset] != '{') {
		return false;
	}
	++offset;
	for (;;) {
		offset = skip_ws(text, offset);
		if (offset >= text.size() || text[offset] == '}') {
			return false;
		}
		if (text[offset] != '"') {
			return false;
		}
		const std::size_t key_start = offset;
		const std::size_t key_end = skip_json_string(text, offset);
		if (key_end == std::string_view::npos) {
			return false;
		}
		offset = skip_ws(text, key_end);
		if (offset >= text.size() || text[offset] != ':') {
			return false;
		}
		++offset;
		value_start = skip_ws(text, offset);
		value_end = skip_json_value(text, value_start);
		if (value_end == std::string_view::npos) {
			return false;
		}
		if (decode_json_string(text.substr(key_start, key_end - key_start)) == std::string(key)) {
			return true;
		}
		offset = skip_ws(text, value_end);
		if (offset >= text.size() || text[offset] != ',') {
			return false;
		}
		++offset;
	}
}

[[nodiscard]] std::string event_text_or_empty(const shared_p<ui::event> &message) {
	if (!message.has_value().native_value() || message.get() == nullptr) {
		return "";
	}
	return message->text.native_value();
}

[[nodiscard]] std::string file_url_from_path(const std::filesystem::path &path) {
	std::string text = path.generic_string();
	std::ostringstream out;
	out << "file://";
	if (!text.empty() && text[0] != '/') {
		out << '/';
	}
	for (const unsigned char ch : text) {
		const bool safe = (ch >= 'A' && ch <= 'Z')
			|| (ch >= 'a' && ch <= 'z')
			|| (ch >= '0' && ch <= '9')
			|| ch == '/'
			|| ch == '-'
			|| ch == '_'
			|| ch == '.'
			|| ch == '~';
		if (safe) {
			out << static_cast<char>(ch);
		} else {
			const char *digits = "0123456789ABCDEF";
			out << '%' << digits[(ch >> 4) & 0x0f] << digits[ch & 0x0f];
		}
	}
	return out.str();
}

#if defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2

struct win32_webview_state final {
	HWND parent = nullptr;
	shared_p<view> target = null;
	Microsoft::WRL::ComPtr<ICoreWebView2Environment> environment;
	Microsoft::WRL::ComPtr<ICoreWebView2Controller> controller;
	Microsoft::WRL::ComPtr<ICoreWebView2> core;
	EventRegistrationToken web_message_token{};
	std::wstring pending_url;
	std::wstring pending_html;
	std::wstring pending_script;
	bool bridge_ready = false;
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
	if (state == nullptr || state->core == nullptr || !state->bridge_ready || state->closed) {
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

void push_win32_bridge_message(const win32_webview_state_ptr &state, const std::string &text) {
	if (state == nullptr || !state->target.has_value().native_value() || state->target.get() == nullptr) {
		return;
	}
	auto window = state->target->window_handle;
	if (!window.has_value().native_value() || window.get() == nullptr || !window->app_handle.has_value().native_value()) {
		return;
	}

	auto event_value = shared<ui::event>();
	event_value->type = string_t("webview_message");
	event_value->window_handle = window;
	event_value->text = string_t(text);
	window->app_handle->pending_events.push_back(event_value);
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
	WebKitUserContentManager *manager = webkit_user_content_manager_new();
	webkit_user_content_manager_register_script_message_handler(manager, "scpp");
	WebKitUserScript *script = webkit_user_script_new(
		bridge_script,
		WEBKIT_USER_CONTENT_INJECT_ALL_FRAMES,
		WEBKIT_USER_SCRIPT_INJECT_AT_DOCUMENT_START,
		nullptr,
		nullptr
	);
	webkit_user_content_manager_add_script(manager, script);
	webkit_user_script_unref(script);

	GtkWidget *native = webkit_web_view_new_with_user_content_manager(manager);
	g_object_unref(manager);
	if (native == nullptr) {
		return error_t(string_t("webview_create(): WebKitGTK failed to create a native webview"));
	}

	gtk_container_add(GTK_CONTAINER(parent), native);
	gtk_widget_show(native);

	auto target = shared<view>();
	target->window_handle = window;
	target->native_handle = native;
	auto *state = new bridge_state{target};
	target->native_state = state;
	g_signal_connect(
		webkit_web_view_get_user_content_manager(WEBKIT_WEB_VIEW(native)),
		"script-message-received::scpp",
		G_CALLBACK(handle_bridge_message),
		state
	);
	g_signal_connect(
		WEBKIT_WEB_VIEW(native),
		"decide-policy",
		G_CALLBACK(handle_navigation_policy),
		state
	);
	return target;
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSWindow *parent = static_cast<NSWindow *>(window->native_handle);
		NSView *content = [parent contentView];
		if (content == nil) {
			return error_t(string_t("webview_create(): AppKit window has no content view"));
		}

		auto target = shared<view>();
		target->window_handle = window;

		WKWebViewConfiguration *configuration = [[WKWebViewConfiguration alloc] init];
		WKUserContentController *user_content = [[WKUserContentController alloc] init];
		NSString *source = [NSString stringWithUTF8String:bridge_script];
		WKUserScript *script = [[WKUserScript alloc] initWithSource:source injectionTime:WKUserScriptInjectionTimeAtDocumentStart forMainFrameOnly:NO];
		ScppWKBridgeHandler *handler = [[ScppWKBridgeHandler alloc] initWithTarget:target];
		[user_content addUserScript:script];
		[user_content addScriptMessageHandler:handler name:@"scpp"];
		configuration.userContentController = user_content;

		WKWebView *native = [[WKWebView alloc] initWithFrame:[content bounds] configuration:configuration];
		if (native == nil) {
			[user_content removeScriptMessageHandlerForName:@"scpp"];
			[script release];
			[user_content release];
			[configuration release];
			[handler release];
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		[script release];
		[user_content release];
		[configuration release];
		[native setAutoresizingMask:NSViewWidthSizable | NSViewHeightSizable];
		[content addSubview:native];

		target->native_handle = native;
		target->native_state = handler;
		return target;
	}
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
	@autoreleasepool {
		UIWindow *parent = static_cast<UIWindow *>(window->native_handle);
		UIView *content = parent.rootViewController.view;
		if (content == nil) {
			return error_t(string_t("webview_create(): UIKit window has no root content view"));
		}

		auto target = shared<view>();
		target->window_handle = window;

		WKWebViewConfiguration *configuration = [[WKWebViewConfiguration alloc] init];
		WKUserContentController *user_content = [[WKUserContentController alloc] init];
		NSString *source = [NSString stringWithUTF8String:bridge_script];
		WKUserScript *script = [[WKUserScript alloc] initWithSource:source injectionTime:WKUserScriptInjectionTimeAtDocumentStart forMainFrameOnly:NO];
		ScppWKBridgeHandler *handler = [[ScppWKBridgeHandler alloc] initWithTarget:target];
		[user_content addUserScript:script];
		[user_content addScriptMessageHandler:handler name:@"scpp"];
		configuration.userContentController = user_content;

		WKWebView *native = [[WKWebView alloc] initWithFrame:content.bounds configuration:configuration];
		if (native == nil) {
			[user_content removeScriptMessageHandlerForName:@"scpp"];
			[script release];
			[user_content release];
			[configuration release];
			[handler release];
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		[script release];
		[user_content release];
		[configuration release];
		native.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
		[content addSubview:native];

		target->native_handle = native;
		target->native_state = handler;
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
	state->target = target;

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
							if (state->core != nullptr) {
								HRESULT script_result = state->core->AddScriptToExecuteOnDocumentCreated(
									utf8_to_wide(bridge_script).c_str(),
									Microsoft::WRL::Callback<ICoreWebView2AddScriptToExecuteOnDocumentCreatedCompletedHandler>(
										[state](HRESULT, LPCWSTR) -> HRESULT {
											state->bridge_ready = true;
											flush_win32_pending(state.get());
											return S_OK;
										}
									).Get()
								);
								if (FAILED(script_result)) {
									state->bridge_ready = true;
								}
								state->core->add_WebMessageReceived(
									Microsoft::WRL::Callback<ICoreWebView2WebMessageReceivedEventHandler>(
										[state](ICoreWebView2 *, ICoreWebView2WebMessageReceivedEventArgs *args) -> HRESULT {
											if (args == nullptr || state->closed) {
												return S_OK;
											}
											LPWSTR raw = nullptr;
											HRESULT message_result = args->TryGetWebMessageAsString(&raw);
											if (SUCCEEDED(message_result) && raw != nullptr) {
												std::wstring wide(raw);
												CoTaskMemFree(raw);
												std::string text;
												const int needed = WideCharToMultiByte(CP_UTF8, 0, wide.c_str(), static_cast<int>(wide.size()), nullptr, 0, nullptr, nullptr);
												if (needed > 0) {
													text.resize(static_cast<std::size_t>(needed));
													WideCharToMultiByte(CP_UTF8, 0, wide.c_str(), static_cast<int>(wide.size()), text.data(), needed, nullptr, nullptr);
												}
												push_win32_bridge_message(state, text);
											}
											return S_OK;
										}
									).Get(),
									&state->web_message_token
								);
							}
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
	target->app_root_path = string_t("");
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
	target->app_root_path = string_t("");
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

result<bool_t> load_app(const shared_p<view> &target, const string_t &folder) {
	auto checked = require_view(target, "webview_load_app()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	if (folder.native_value().empty()) {
		return error_t(string_t("webview_load_app(): app folder must not be empty"));
	}

	std::filesystem::path root;
	try {
		root = std::filesystem::weakly_canonical(std::filesystem::absolute(std::filesystem::path(folder.native_value())));
	} catch (const std::filesystem::filesystem_error &) {
		return error_t(string_t("webview_load_app(): app folder does not exist"));
	}
	if (!std::filesystem::is_directory(root)) {
		return error_t(string_t("webview_load_app(): app folder does not exist"));
	}

	const auto index = root / "index.html";
	if (!std::filesystem::is_regular_file(index)) {
		return error_t(string_t("webview_load_app(): index.html was not found in app folder"));
	}

	target->app_root_path = string_t(root.string());
	target->current_url = string_t(file_url_from_path(index));
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
	webkit_web_view_load_uri(WEBKIT_WEB_VIEW(target->native_handle), target->current_url.native_value().c_str());
	return bool_t(true);
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
	@autoreleasepool {
		NSString *index_path = [NSString stringWithUTF8String:index.string().c_str()];
		NSString *root_path = [NSString stringWithUTF8String:root.string().c_str()];
		NSURL *index_url = [NSURL fileURLWithPath:index_path isDirectory:NO];
		NSURL *root_url = [NSURL fileURLWithPath:root_path isDirectory:YES];
		if (index_url == nil || root_url == nil) {
			return error_t(string_t("webview_load_app(): failed to create file URL"));
		}
		WKWebView *native = static_cast<WKWebView *>(target->native_handle);
		[native loadFileURL:index_url allowingReadAccessToURL:root_url];
		return bool_t(true);
	}
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
	@autoreleasepool {
		NSString *index_path = [NSString stringWithUTF8String:index.string().c_str()];
		NSString *root_path = [NSString stringWithUTF8String:root.string().c_str()];
		NSURL *index_url = [NSURL fileURLWithPath:index_path isDirectory:NO];
		NSURL *root_url = [NSURL fileURLWithPath:root_path isDirectory:YES];
		if (index_url == nil || root_url == nil) {
			return error_t(string_t("webview_load_app(): failed to create file URL"));
		}
		WKWebView *native = static_cast<WKWebView *>(target->native_handle);
		[native loadFileURL:index_url allowingReadAccessToURL:root_url];
		return bool_t(true);
	}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
	auto *state = get_win32_state(target);
	if (state == nullptr || state->closed) {
		return error_t(string_t("webview_load_app(): WebView2 state is not available"));
	}
	state->pending_url = utf8_to_wide(target->current_url.native_value());
	state->pending_html.clear();
	flush_win32_pending(state);
	return bool_t(true);
#elif defined(SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW) && SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW
	return android_call_webview_string_method(get_android_state(target), "webview_load_app()", "loadUrl", "(Ljava/lang/String;)V", target->current_url);
#else
	return error_t(string_t("webview_load_app(): no native webview backend is selected in this build"));
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

result<bool_t> reply_ok(const shared_p<view> &target, const int_t &id, const string_t &value_json) {
	const std::string payload = value_json.native_value().empty() ? "null" : value_json.native_value();
	const std::string script = "window.scpp&&window.scpp.__resolve&&window.scpp.__resolve({\"id\":"
		+ std::to_string(id.native_value())
		+ ",\"ok\":true,\"value\":"
		+ payload
		+ "});";
	return eval(target, string_t(script));
}

result<bool_t> reply_error(const shared_p<view> &target, const int_t &id, const string_t &code, const string_t &message) {
	const std::string script = "window.scpp&&window.scpp.__resolve&&window.scpp.__resolve({\"id\":"
		+ std::to_string(id.native_value())
		+ ",\"ok\":false,\"error\":{\"code\":"
		+ json_string_literal(code.native_value())
		+ ",\"message\":"
		+ json_string_literal(message.native_value())
		+ "}});";
	return eval(target, string_t(script));
}

int_t message_id(const shared_p<ui::event> &message) {
	const std::string text = event_text_or_empty(message);
	std::size_t start = 0;
	std::size_t end = 0;
	if (!top_level_json_field(text, "id", start, end)) {
		return int_t(0);
	}
	std::int64_t value = 0;
	const auto parsed = std::from_chars(text.data() + start, text.data() + end, value);
	if (parsed.ec != std::errc{} || parsed.ptr != text.data() + end) {
		return int_t(0);
	}
	return int_t(value);
}

string_t message_command(const shared_p<ui::event> &message) {
	const std::string text = event_text_or_empty(message);
	std::size_t start = 0;
	std::size_t end = 0;
	if (!top_level_json_field(text, "command", start, end)) {
		return string_t("");
	}
	return string_t(decode_json_string(std::string_view(text).substr(start, end - start)));
}

string_t message_payload_json(const shared_p<ui::event> &message) {
	const std::string text = event_text_or_empty(message);
	std::size_t start = 0;
	std::size_t end = 0;
	if (!top_level_json_field(text, "payload", start, end)) {
		return string_t("null");
	}
	return string_t(text.substr(start, end - start));
}

void close(const shared_p<view> &target) {
	if (target.has_value().native_value() && target.get() != nullptr) {
#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
		if (target->native_handle != nullptr) {
			gtk_widget_destroy(GTK_WIDGET(target->native_handle));
		}
		delete static_cast<bridge_state *>(target->native_state);
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
		if (target->native_handle != nullptr) {
			WKWebView *native = static_cast<WKWebView *>(target->native_handle);
			[native.configuration.userContentController removeScriptMessageHandlerForName:@"scpp"];
			[native stopLoading];
			[native removeFromSuperview];
			[native release];
		}
		if (target->native_state != nullptr) {
			ScppWKBridgeHandler *handler = static_cast<ScppWKBridgeHandler *>(target->native_state);
			[handler release];
			target->native_state = nullptr;
		}
#elif defined(SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW
		if (target->native_handle != nullptr) {
			WKWebView *native = static_cast<WKWebView *>(target->native_handle);
			[native.configuration.userContentController removeScriptMessageHandlerForName:@"scpp"];
			[native stopLoading];
			[native removeFromSuperview];
			[native release];
		}
		if (target->native_state != nullptr) {
			ScppWKBridgeHandler *handler = static_cast<ScppWKBridgeHandler *>(target->native_state);
			[handler release];
			target->native_state = nullptr;
		}
#elif defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2
		auto *holder = static_cast<win32_webview_state_ptr *>(target->native_state);
		if (holder != nullptr) {
			win32_webview_state_ptr state = *holder;
			if (state != nullptr) {
				state->closed = true;
				if (state->core != nullptr) {
					state->core->remove_WebMessageReceived(state->web_message_token);
				}
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
