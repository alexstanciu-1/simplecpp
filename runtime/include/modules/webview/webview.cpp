#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#if !WEBKIT_CHECK_VERSION(2, 22, 0)
#include <JavaScriptCore/JavaScript.h>
#endif
#endif

#include <charconv>
#include <filesystem>
#include <sstream>
#include <string>
#include <string_view>

namespace scpp::webview_runtime {

namespace {

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
#endif
		target->closed = bool_t(true);
		target->native_handle = nullptr;
		target->native_controller = nullptr;
		target->native_state = nullptr;
	}
}

} // namespace scpp::webview_runtime

#endif
