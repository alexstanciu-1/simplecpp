#include "modules/webview/webview.hpp"

#if SCPP_HAS_WEBVIEW

#if defined(SCPP_WEBVIEW_BACKEND_WEBKITGTK) && SCPP_WEBVIEW_BACKEND_WEBKITGTK
#include <webkit2/webkit2.h>
#if !WEBKIT_CHECK_VERSION(2, 22, 0)
#include <JavaScriptCore/JavaScript.h>
#endif
#endif

#include <sstream>
#include <string>

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
