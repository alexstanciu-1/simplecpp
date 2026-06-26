#include "scpp/webview.hpp"

int main() {
	scpp::shared_p<scpp::ui_window> window = scpp::shared<scpp::ui_window>();
	JavaVM *vm = nullptr;
	jobject activity = nullptr;
	jobject webview = nullptr;
	(void) scpp::webview_runtime::android_attach_activity_webview(window, vm, activity, webview);
	scpp::webview_runtime::android_detach_activity_webview(window);

	auto create_result = scpp::webview_runtime::create(window);
	(void) create_result;

	scpp::shared_p<scpp::webview> view = scpp::null;
	(void) scpp::webview_runtime::load_url(view, scpp::string_t("about:blank"));
	(void) scpp::webview_runtime::load_html(view, scpp::string_t("<!doctype html><h1>Simple C++ WebView</h1>"));
	(void) scpp::webview_runtime::eval(view, scpp::string_t("document.body.dataset.ready = '1';"));
	(void) scpp::webview_runtime::android_dispatch_bridge_message(
		view,
		scpp::string_t("{\"id\":1,\"kind\":\"invoke\",\"command\":\"bridge.ping\",\"payload\":{\"source\":\"android-compile\"}}")
	);
	scpp::webview_runtime::close(view);
	return 0;
}
