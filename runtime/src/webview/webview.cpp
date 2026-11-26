#include "webview_provider.hpp"
#include "../ui/ui_private.hpp"
#include <webkit2/webkit2.h>
#include <json-glib/json-glib.h>
#include <filesystem>
#include <limits>
#include <map>
#include <string>

namespace fs = std::filesystem;
namespace scpp::detail {
struct webview_state {
    std::atomic<size_t> references{1};
    ui_window window;
    WebKitWebView *widget{};
    bool closed{}, trusted{};
    int64 next_id{};
    std::map<int64, int64> requests;
    void detach();
    ~webview_state() {
        detach();
    }
};
void webview_state::detach() {
    requests.clear();
    if (!widget)
        return;
    auto *manager = webkit_web_view_get_user_content_manager(widget);
    g_signal_handlers_disconnect_by_data(widget, this);
    g_signal_handlers_disconnect_by_data(manager, this);
    gtk_widget_destroy(GTK_WIDGET(widget));
    g_object_unref(widget);
    widget = nullptr;
}
} // namespace scpp::detail
namespace scpp::webview_api {
using namespace detail;
static constexpr char app_uri[] = "scpp-app://app/";
static constexpr char bridge[] = R"JS((()=>{
const pending=new Map(); let id=0;
Object.defineProperty(window,'scpp',{value:Object.freeze({invoke(command,payload){
return new Promise((resolve,reject)=>{const n=++id;const msg=JSON.stringify({id:n,command,payload});
pending.set(n,[resolve,reject]);window.webkit.messageHandlers.scpp.postMessage(msg);});}})});
Object.defineProperty(window,'__scppReply',{value:(id,ok,value)=>{
const item=pending.get(id);if(item){pending.delete(id);item[ok?0:1](value);}}});
})();)JS";
static string text(const char *s) {
    return string{s ? s : ""};
}
static bool checked_text(const string &s) {
    return g_utf8_validate(s.data(), s.size(), nullptr);
}
static result_bool_t failure(const char *s) {
    return result_bool_t{error_t{text(s)}};
}
static webview_state *active(const webview &handle) {
    auto *s = webview_access::get(handle);
    if (!ui_thread() || !s || s->closed)
        return nullptr;
    auto *w = ui_window_access::get(s->window);
    return w && !w->closed ? s : nullptr;
}
static void event(webview_state &s, const char *kind, string message = {}, int64 id = 0, string command = {},
                  string payload = {}) {
    s.references.fetch_add(1, std::memory_order_relaxed);
    auto owner = webview_access::adopt(&s);
    enqueue_event(s.window, kind, std::make_shared<webview>(owner), std::move(message),
                  text(webkit_web_view_get_uri(s.widget)), id, std::move(command), std::move(payload));
}
static void load_changed(WebKitWebView *, WebKitLoadEvent phase, gpointer data) noexcept {
    auto &s = *static_cast<webview_state *>(data);
    try {
        if (phase == WEBKIT_LOAD_STARTED) {
            s.requests.clear();
            event(s, "webview_navigation_started");
        }
        if (phase == WEBKIT_LOAD_FINISHED)
            event(s, "webview_navigation_finished");
    } catch (...) {
        g_warning("could not enqueue navigation event");
    }
}
static gboolean load_failed(WebKitWebView *, WebKitLoadEvent, const char *, GError *error,
                            gpointer data) noexcept {
    try {
        event(*static_cast<webview_state *>(data), "webview_load_failed", text(error->message));
    } catch (...) {
        g_warning("could not enqueue load failure");
    }
    return true;
}
static gboolean policy(WebKitWebView *, WebKitPolicyDecision *decision, WebKitPolicyDecisionType type,
                       gpointer data) {
    if (type == WEBKIT_POLICY_DECISION_TYPE_RESPONSE)
        return false;
    auto &s = *static_cast<webview_state *>(data);
    auto *action =
        webkit_navigation_policy_decision_get_navigation_action(WEBKIT_NAVIGATION_POLICY_DECISION(decision));
    const char *uri = webkit_uri_request_get_uri(webkit_navigation_action_get_request(action));
    bool allowed = s.trusted ? g_str_has_prefix(uri, app_uri) || g_str_equal(uri, "about:blank")
                             : g_str_has_prefix(uri, "https://") || g_str_has_prefix(uri, "http://");
    if (!allowed || type == WEBKIT_POLICY_DECISION_TYPE_NEW_WINDOW_ACTION) {
        webkit_policy_decision_ignore(decision);
        return true;
    }
    return false;
}
static void message(WebKitUserContentManager *, WebKitJavascriptResult *result, gpointer data) noexcept {
    auto &s = *static_cast<webview_state *>(data);
    if (!s.trusted || !s.widget)
        return;
    auto *value = webkit_javascript_result_get_js_value(result);
    if (!jsc_value_is_string(value))
        return;
    g_autofree char *raw = jsc_value_to_string(value);
    g_autoptr(JsonParser) parser = json_parser_new();
    if (!json_parser_load_from_data(parser, raw, -1, nullptr))
        return;
    auto *node = json_parser_get_root(parser);
    if (!node || !JSON_NODE_HOLDS_OBJECT(node))
        return;
    auto *obj = json_node_get_object(node);
    auto *id = json_object_get_member(obj, "id");
    auto *name = json_object_get_member(obj, "command");
    auto *payload = json_object_get_member(obj, "payload");
    if (!id || json_node_get_value_type(id) != G_TYPE_INT64 || json_node_get_int(id) <= 0 || !name ||
        json_node_get_value_type(name) != G_TYPE_STRING || !payload ||
        s.next_id == std::numeric_limits<int64>::max())
        return;
    try {
        g_autofree char *json = json_to_string(payload, false);
        int64 ticket = ++s.next_id;
        s.requests.emplace(ticket, json_node_get_int(id));
        event(s, "webview_message", text(raw), ticket, text(json_node_get_string(name)), text(json));
    } catch (...) {
        g_warning("could not queue bridge request");
    }
}
// Each local page gets an isolated origin and a fixed asset root. The renderer
// never receives a file:// URL or permission to read arbitrary local files.
static void asset(WebKitURISchemeRequest *request, gpointer data) noexcept {
    try {
        const auto &root = *static_cast<const fs::path *>(data);
        const char *path = webkit_uri_scheme_request_get_path(request);
        g_autofree char *decoded = g_uri_unescape_string(path, nullptr);
        if (!root.empty() && decoded && decoded[0] == '/') {
            auto file = fs::canonical(root / fs::path(decoded).relative_path());
            auto relative = file.lexically_relative(root);
            if (!relative.empty() && *relative.begin() != ".." && fs::is_regular_file(file)) {
                g_autoptr(GFile) source = g_file_new_for_path(file.c_str());
                g_autoptr(GFileInputStream) stream = g_file_read(source, nullptr, nullptr);
                if (stream) {
                    g_autofree char *content = g_content_type_guess(file.c_str(), nullptr, 0, nullptr);
                    g_autofree char *mime = g_content_type_get_mime_type(content);
                    webkit_uri_scheme_request_finish(request, G_INPUT_STREAM(stream), -1, mime);
                    return;
                }
            }
        }
    } catch (...) { /* report a failed resource request to the renderer */
    }
    g_autoptr(GError) error = g_error_new_literal(G_IO_ERROR, G_IO_ERROR_NOT_FOUND, "App asset unavailable");
    webkit_uri_scheme_request_finish_error(request, error);
}
static void open_page(webview_state &s, bool local, const fs::path &root) {
    s.detach();
    s.trusted = local;
    g_autoptr(WebKitWebContext) context = webkit_web_context_new_ephemeral();
    g_autoptr(WebKitUserContentManager) manager = webkit_user_content_manager_new();
    if (local) {
        webkit_web_context_register_uri_scheme(context, "scpp-app", asset, new fs::path(root),
                                               [](gpointer p) { delete static_cast<fs::path *>(p); });
        webkit_security_manager_register_uri_scheme_as_secure(
            webkit_web_context_get_security_manager(context), "scpp-app");
        auto *script = webkit_user_script_new(bridge, WEBKIT_USER_CONTENT_INJECT_TOP_FRAME,
                                              WEBKIT_USER_SCRIPT_INJECT_AT_DOCUMENT_START, nullptr, nullptr);
        webkit_user_content_manager_add_script(manager, script);
        webkit_user_script_unref(script);
        g_signal_connect(manager, "script-message-received::scpp", G_CALLBACK(message), &s);
        webkit_user_content_manager_register_script_message_handler(manager, "scpp");
    }
    const char *csp =
        "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src "
        "'self' data:; frame-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'";
    s.widget = WEBKIT_WEB_VIEW(
        g_object_new(WEBKIT_TYPE_WEB_VIEW, "web-context", context, "user-content-manager", manager,
                     "default-content-security-policy", local ? csp : nullptr, nullptr));
    g_object_ref_sink(s.widget);
    g_signal_connect(s.widget, "load-changed", G_CALLBACK(load_changed), &s);
    g_signal_connect(s.widget, "load-failed", G_CALLBACK(load_failed), &s);
    g_signal_connect(s.widget, "decide-policy", G_CALLBACK(policy), &s);
    gtk_container_add(GTK_CONTAINER(ui_window_access::get(s.window)->widget), GTK_WIDGET(s.widget));
    gtk_widget_show(GTK_WIDGET(s.widget));
}
result_webview webview_create(const ui_window &window) {
    auto *w = ui_window_access::get(window);
    if (!ui_thread() || !w || w->closed || gtk_bin_get_child(GTK_BIN(w->widget)))
        return result_webview{error_t{text("window unavailable")}};
    auto *s = new webview_state;
    auto view = webview_access::adopt(s);
    s->window = window;
    open_page(*s, true, {});
    event(*s, "webview_ready");
    return result_webview{std::move(view)};
}
result_bool_t webview_load_html(const webview &v, const string &html) {
    auto *s = active(v);
    if (!s || !checked_text(html))
        return failure("invalid view or HTML");
    open_page(*s, true, {});
    webkit_web_view_load_html(s->widget, std::string(html.data(), html.size()).c_str(), app_uri);
    return result_bool_t{true};
}
result_bool_t webview_load_app(const webview &v, const string &folder) {
    auto *s = active(v);
    if (!s || !checked_text(folder))
        return failure("invalid view or app folder");
    std::error_code ec;
    auto root = fs::canonical(std::string(folder.data(), folder.size()), ec);
    if (ec || !fs::is_regular_file(root / "index.html", ec))
        return failure("app requires index.html");
    open_page(*s, true, root);
    webkit_web_view_load_uri(s->widget, "scpp-app://app/index.html");
    return result_bool_t{true};
}
result_bool_t webview_load_url(const webview &v, const string &url) {
    auto *s = active(v);
    std::string uri(url.data(), url.size());
    if (!s || !checked_text(url) || !(uri.starts_with("https://") || uri.starts_with("http://")))
        return failure("expected HTTP or HTTPS URL");
    open_page(*s, false, {});
    webkit_web_view_load_uri(s->widget, uri.c_str());
    return result_bool_t{true};
}
result_bool_t webview_eval(const webview &v, const string &script) {
    auto *s = active(v);
    if (!s || !s->trusted || !s->widget || !checked_text(script))
        return failure("evaluation requires an app page");
    webkit_web_view_evaluate_javascript(s->widget, script.data(), script.size(), nullptr, nullptr, nullptr,
                                        nullptr, nullptr);
    return result_bool_t{true};
}
static result_bool_t reply(const webview &v, int64 ticket, bool ok, JsonNode *value) {
    auto *s = active(v);
    if (!s || !s->trusted || !value)
        return failure("invalid reply");
    auto it = s->requests.find(ticket);
    if (it == s->requests.end())
        return failure("unknown or expired request");
    g_autofree char *json = json_to_string(value, false);
    auto code = std::string("window.__scppReply(") + std::to_string(it->second) + "," +
                (ok ? "true," : "false,") + json + ")";
    s->requests.erase(it);
    webkit_web_view_evaluate_javascript(s->widget, code.data(), code.size(), nullptr, nullptr, nullptr,
                                        nullptr, nullptr);
    return result_bool_t{true};
}
result_bool_t webview_reply_ok(const webview &v, int64 id, const string &json) {
    g_autoptr(JsonParser) p = json_parser_new();
    if (!checked_text(json) || !json_parser_load_from_data(p, json.data(), json.size(), nullptr))
        return failure("invalid JSON reply");
    return reply(v, id, true, json_parser_get_root(p));
}
result_bool_t webview_reply_error(const webview &v, int64 id, const string &code, const string &msg) {
    if (!checked_text(code) || !checked_text(msg))
        return failure("invalid reply text");
    g_autoptr(JsonNode) node = json_node_new(JSON_NODE_OBJECT);
    auto *obj = json_object_new();
    json_node_take_object(node, obj);
    json_object_set_string_member(obj, "code", std::string(code.data(), code.size()).c_str());
    json_object_set_string_member(obj, "message", std::string(msg.data(), msg.size()).c_str());
    return reply(v, id, false, node);
}
nullable_webview ui_event_webview(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    if (!s || !s->view)
        return {};
    return nullable_webview{*std::static_pointer_cast<webview>(s->view)};
}
int64 webview_message_id(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s ? s->id : 0;
}
string webview_message_command(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s ? s->command : string{};
}
string webview_message_payload_json(const ui_event &e) {
    auto *s = ui_event_access::get(e);
    return s && s->id ? s->payload : text("null");
}
void webview_close(const webview &v) {
    auto *s = webview_access::get(v);
    if (s && ui_thread()) {
        s->closed = true;
        s->detach();
    }
}
} // namespace scpp::webview_api
#include "webview_values.hpp"
