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
#include <wrl/client.h>
#include <WebView2.h>
#endif

#include <atomic>
#include <charconv>
#include <filesystem>
#include <memory>
#include <sstream>
#include <string>
#include <string_view>
#include <utility>

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
		if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.SimpleCpp) {
			window.webkit.messageHandlers.SimpleCpp.postMessage(text);
			return;
		}
		if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.scpp) {
			window.webkit.messageHandlers.scpp.postMessage(text);
			return;
		}
		if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
			window.chrome.webview.postMessage(text);
			return;
		}
		if (window.scppAndroid && typeof window.scppAndroid.postMessage === "function") {
			window.scppAndroid.postMessage(text);
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
@interface ScppWebViewNavigationDelegate : NSObject<WKNavigationDelegate, WKScriptMessageHandler> {
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

- (NSString *)targetURLString
{
	if (!_target.has_value().native_value() || _target.get() == nullptr || _target->native_handle == nullptr) {
		return @"";
	}
	WKWebView *webView = static_cast<WKWebView *>(_target->native_handle);
	return [self currentURLString:webView];
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

- (void)userContentController:(WKUserContentController *)userContentController didReceiveScriptMessage:(WKScriptMessage *)scriptMessage
{
	(void)userContentController;
	NSString *name = scriptMessage.name;
	if (name == nil || ![name isEqualToString:@"SimpleCpp"]) {
		return;
	}
	id body = scriptMessage.body;
	NSString *payload = body == nil ? @"" : [body description];
	[self enqueueType:"webview_message" message:payload url:[self targetURLString]];
}
@end
#endif

namespace scpp::webview_runtime {

namespace {

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
					const char *digits = "0123456789abcdef";
					out << "\\u00" << digits[(ch >> 4) & 0x0f] << digits[ch & 0x0f];
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

[[nodiscard]] std::string event_message_or_empty(const shared_p<ui::event> &message) {
	if (!message.has_value().native_value() || message.get() == nullptr) {
		return "";
	}
	return message->message.native_value();
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
	auto *state = static_cast<webkitgtk_callback_state *>(data);
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

void handle_webkitgtk_script_message(WebKitUserContentManager *, WebKitJavascriptResult *result, gpointer data) {
	auto *state = static_cast<webkitgtk_callback_state *>(data);
	if (state == nullptr || result == nullptr) {
		return;
	}
	string_t message = string_t("");
	JSCValue *value = webkit_javascript_result_get_js_value(result);
	if (value != nullptr) {
		gchar *text = jsc_value_to_string(value);
		if (text != nullptr) {
			message = string_t(text);
			g_free(text);
		}
	}
	string_t url = string_t("");
	if (state->target.has_value().native_value() && state->target.get() != nullptr && state->target->native_handle != nullptr) {
		url = webkitgtk_current_uri(WEBKIT_WEB_VIEW(state->target->native_handle));
	}
	enqueue_webkitgtk_event(state->target, "webview_message", message, url);
}

#endif

#if defined(SCPP_WEBVIEW_BACKEND_WEBVIEW2) && SCPP_WEBVIEW_BACKEND_WEBVIEW2

template <typename TInterface, typename TArg1, typename TArg2, typename TLambda>
class win32_callback2 final : public TInterface {
public:
	explicit win32_callback2(TLambda callback) : callback_(std::move(callback)) {}

	HRESULT STDMETHODCALLTYPE QueryInterface(REFIID riid, void **object) override {
		if (object == nullptr) {
			return E_POINTER;
		}
		*object = nullptr;
		if (IsEqualIID(riid, __uuidof(IUnknown))
#if !defined(__MINGW32__)
			|| IsEqualIID(riid, __uuidof(TInterface))
#endif
		) {
			*object = static_cast<TInterface *>(this);
			AddRef();
			return S_OK;
		}
		return E_NOINTERFACE;
	}

	ULONG STDMETHODCALLTYPE AddRef() override {
		return ++refs_;
	}

	ULONG STDMETHODCALLTYPE Release() override {
		const ULONG refs = --refs_;
		if (refs == 0) {
			delete this;
		}
		return refs;
	}

	HRESULT STDMETHODCALLTYPE Invoke(TArg1 arg1, TArg2 arg2) override {
		return callback_(arg1, arg2);
	}

private:
	std::atomic<ULONG> refs_{1};
	TLambda callback_;
};

template <typename TInterface, typename TArg1, typename TArg2, typename TLambda>
[[nodiscard]] Microsoft::WRL::ComPtr<TInterface> make_win32_callback2(TLambda callback) {
	Microsoft::WRL::ComPtr<TInterface> result;
	result.Attach(new win32_callback2<TInterface, TArg1, TArg2, TLambda>(std::move(callback)));
	return result;
}

struct win32_webview_state final {
	HWND parent = nullptr;
	Microsoft::WRL::ComPtr<ICoreWebView2Environment> environment;
	Microsoft::WRL::ComPtr<ICoreWebView2Controller> controller;
	Microsoft::WRL::ComPtr<ICoreWebView2> core;
	EventRegistrationToken navigation_starting_token{};
	EventRegistrationToken navigation_completed_token{};
	EventRegistrationToken web_message_received_token{};
	EventRegistrationToken document_title_changed_token{};
	std::wstring pending_url;
	std::wstring pending_html;
	std::wstring pending_script;
	bool has_navigation_starting_token = false;
	bool has_navigation_completed_token = false;
	bool has_web_message_received_token = false;
	bool has_document_title_changed_token = false;
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
	if (state->has_document_title_changed_token) {
		state->core->remove_DocumentTitleChanged(state->document_title_changed_token);
		state->has_document_title_changed_token = false;
	}
}

void register_win32_event_handlers(const win32_webview_state_ptr &state, const shared_p<view> &target) {
	if (state == nullptr || state->core == nullptr || state->closed) {
		return;
	}

	HRESULT hr = state->core->add_NavigationStarting(
		make_win32_callback2<ICoreWebView2NavigationStartingEventHandler, ICoreWebView2 *, ICoreWebView2NavigationStartingEventArgs *>(
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
		make_win32_callback2<ICoreWebView2NavigationCompletedEventHandler, ICoreWebView2 *, ICoreWebView2NavigationCompletedEventArgs *>(
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
		make_win32_callback2<ICoreWebView2WebMessageReceivedEventHandler, ICoreWebView2 *, ICoreWebView2WebMessageReceivedEventArgs *>(
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

	hr = state->core->add_DocumentTitleChanged(
		make_win32_callback2<ICoreWebView2DocumentTitleChangedEventHandler, ICoreWebView2 *, IUnknown *>(
			[target](ICoreWebView2 *sender, IUnknown *) -> HRESULT {
				PWSTR title = nullptr;
				string_t payload = string_t("");
				if (sender != nullptr && SUCCEEDED(sender->get_DocumentTitle(&title))) {
					payload = take_win32_string(title);
				}
				enqueue_win32_event(target, "webview_title_changed", payload, win32_current_source(sender));
				return S_OK;
			}
		).Get(),
		&state->document_title_changed_token
	);
	state->has_document_title_changed_token = SUCCEEDED(hr);
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
	shared_p<view> target = null;
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

void push_android_bridge_message(android_webview_state *state, const std::string &text) {
	if (state == nullptr || !state->target.has_value().native_value() || state->target.get() == nullptr) {
		return;
	}
	(void) enqueue_event(state->target, string_t("webview_message"), string_t(text), string_t(""));
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
	WebKitUserContentManager *content_manager = webkit_user_content_manager_new();
	if (content_manager == nullptr) {
		return error_t(string_t("webview_create(): WebKitGTK failed to create a user content manager"));
	}
	webkit_user_content_manager_register_script_message_handler(content_manager, "SimpleCpp");
	WebKitUserScript *script = webkit_user_script_new(
		bridge_script,
		WEBKIT_USER_CONTENT_INJECT_ALL_FRAMES,
		WEBKIT_USER_SCRIPT_INJECT_AT_DOCUMENT_START,
		nullptr,
		nullptr
	);
	webkit_user_content_manager_add_script(content_manager, script);
	webkit_user_script_unref(script);
	GtkWidget *native = webkit_web_view_new_with_user_content_manager(content_manager);
	if (native == nullptr) {
		g_object_unref(content_manager);
		return error_t(string_t("webview_create(): WebKitGTK failed to create a native webview"));
	}

	gtk_container_add(GTK_CONTAINER(parent), native);
	gtk_widget_show(native);

	auto target = shared<view>();
	target->window_handle = window;
	target->native_handle = native;
	auto *message_state = new webkitgtk_callback_state{target};
	g_signal_connect_data(
		G_OBJECT(content_manager),
		"script-message-received::SimpleCpp",
		G_CALLBACK(handle_webkitgtk_script_message),
		message_state,
		destroy_webkitgtk_callback_state,
		static_cast<GConnectFlags>(0)
	);
	g_object_unref(content_manager);
	auto *policy_state = new webkitgtk_callback_state{target};
	g_signal_connect_data(
		G_OBJECT(native),
		"decide-policy",
		G_CALLBACK(handle_navigation_policy),
		policy_state,
		destroy_webkitgtk_callback_state,
		static_cast<GConnectFlags>(0)
	);
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

		auto target = shared<view>();
		target->window_handle = window;
		ScppWebViewNavigationDelegate *delegate = [[ScppWebViewNavigationDelegate alloc] initWithTarget:target];
		WKUserContentController *contentController = [[WKUserContentController alloc] init];
		NSString *source = [NSString stringWithUTF8String:bridge_script];
		WKUserScript *script = [[WKUserScript alloc] initWithSource:source injectionTime:WKUserScriptInjectionTimeAtDocumentStart forMainFrameOnly:NO];
		[contentController addUserScript:script];
		[contentController addScriptMessageHandler:delegate name:@"SimpleCpp"];
		WKWebViewConfiguration *configuration = [[WKWebViewConfiguration alloc] init];
		configuration.userContentController = contentController;
		WKWebView *native = [[WKWebView alloc] initWithFrame:[content bounds] configuration:configuration];
		[script release];
		[configuration release];
		[contentController release];
		if (native == nil) {
			[delegate release];
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		[native setAutoresizingMask:NSViewWidthSizable | NSViewHeightSizable];
		[content addSubview:native];

		target->native_handle = native;
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

		auto target = shared<view>();
		target->window_handle = window;
		ScppWebViewNavigationDelegate *delegate = [[ScppWebViewNavigationDelegate alloc] initWithTarget:target];
		WKUserContentController *contentController = [[WKUserContentController alloc] init];
		NSString *source = [NSString stringWithUTF8String:bridge_script];
		WKUserScript *script = [[WKUserScript alloc] initWithSource:source injectionTime:WKUserScriptInjectionTimeAtDocumentStart forMainFrameOnly:NO];
		[contentController addUserScript:script];
		[contentController addScriptMessageHandler:delegate name:@"SimpleCpp"];
		WKWebViewConfiguration *configuration = [[WKWebViewConfiguration alloc] init];
		configuration.userContentController = contentController;
		WKWebView *native = [[WKWebView alloc] initWithFrame:content.bounds configuration:configuration];
		[script release];
		[configuration release];
		[contentController release];
		if (native == nil) {
			[delegate release];
			return error_t(string_t("webview_create(): WKWebView failed to create a native webview"));
		}
		native.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
		[content addSubview:native];

		target->native_handle = native;
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
	state->target = target;
	(void) enqueue_event(target, string_t("webview_ready"));
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
		make_win32_callback2<ICoreWebView2CreateCoreWebView2EnvironmentCompletedHandler, HRESULT, ICoreWebView2Environment *>(
			[state, target](HRESULT result, ICoreWebView2Environment *environment) -> HRESULT {
				if (FAILED(result) || environment == nullptr || state->closed) {
					return result;
				}
				state->environment = environment;
				return environment->CreateCoreWebView2Controller(
					state->parent,
					make_win32_callback2<ICoreWebView2CreateCoreWebView2ControllerCompletedHandler, HRESULT, ICoreWebView2Controller *>(
						[state, target](HRESULT controller_result, ICoreWebView2Controller *controller) -> HRESULT {
							if (FAILED(controller_result) || controller == nullptr || state->closed) {
								return controller_result;
							}
							state->controller = controller;
							controller->get_CoreWebView2(&state->core);
							if (state->core != nullptr) {
								HRESULT script_result = state->core->AddScriptToExecuteOnDocumentCreated(
									utf8_to_wide(bridge_script).c_str(),
									make_win32_callback2<ICoreWebView2AddScriptToExecuteOnDocumentCreatedCompletedHandler, HRESULT, LPCWSTR>(
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
							}
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

result<bool_t> reply_ok(const shared_p<view> &target, const int_t<> &id, const string_t &value_json) {
	const std::string payload = value_json.native_value().empty() ? "null" : value_json.native_value();
	const std::string script = "window.scpp&&window.scpp.__resolve&&window.scpp.__resolve({\"id\":"
		+ std::to_string(id.native_value())
		+ ",\"ok\":true,\"value\":"
		+ payload
		+ "});";
	return eval(target, string_t(script));
}

result<bool_t> reply_error(const shared_p<view> &target, const int_t<> &id, const string_t &code, const string_t &message) {
	const std::string script = "window.scpp&&window.scpp.__resolve&&window.scpp.__resolve({\"id\":"
		+ std::to_string(id.native_value())
		+ ",\"ok\":false,\"error\":{\"code\":"
		+ json_string_literal(code.native_value())
		+ ",\"message\":"
		+ json_string_literal(message.native_value())
		+ "}});";
	return eval(target, string_t(script));
}

int_t<> message_id(const shared_p<ui::event> &message) {
	const std::string text = event_message_or_empty(message);
	std::size_t start = 0;
	std::size_t end = 0;
	if (!top_level_json_field(text, "id", start, end)) {
		return int_t<>(0);
	}
	std::int64_t value = 0;
	const auto parsed = std::from_chars(text.data() + start, text.data() + end, value);
	if (parsed.ec != std::errc{} || parsed.ptr != text.data() + end) {
		return int_t<>(0);
	}
	return int_t<>(value);
}

string_t message_command(const shared_p<ui::event> &message) {
	const std::string text = event_message_or_empty(message);
	std::size_t start = 0;
	std::size_t end = 0;
	if (!top_level_json_field(text, "command", start, end)) {
		return string_t("");
	}
	return string_t(decode_json_string(std::string_view(text).substr(start, end - start)));
}

string_t message_payload_json(const shared_p<ui::event> &message) {
	const std::string text = event_message_or_empty(message);
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
#elif defined(SCPP_WEBVIEW_BACKEND_WKWEBVIEW) && SCPP_WEBVIEW_BACKEND_WKWEBVIEW
		if (target->native_handle != nullptr) {
			WKWebView *native = static_cast<WKWebView *>(target->native_handle);
			[native stopLoading];
			[native.configuration.userContentController removeScriptMessageHandlerForName:@"SimpleCpp"];
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
			[native.configuration.userContentController removeScriptMessageHandlerForName:@"SimpleCpp"];
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

result<bool_t> android_dispatch_bridge_message(const shared_p<view> &target, const string_t &message_json) {
	auto checked = require_view(target, "webview_android_dispatch_bridge_message()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto *state = get_android_state(target);
	if (state == nullptr || state->webview == nullptr) {
		return error_t(string_t("webview_android_dispatch_bridge_message(): Android WebView state is not available"));
	}
	if (message_json.native_value().empty()) {
		return error_t(string_t("webview_android_dispatch_bridge_message(): message must not be empty"));
	}

	push_android_bridge_message(state, message_json.native_value());
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
