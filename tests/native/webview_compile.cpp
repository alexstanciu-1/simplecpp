#include "scpp/webview.hpp"

int main() {
	scpp::shared_p<scpp::ui_window> window = scpp::null;
	auto create_result = scpp::webview_runtime::create(window);
	(void) create_result;

	scpp::shared_p<scpp::webview> view = scpp::null;
	(void) scpp::webview_runtime::load_url(view, scpp::string_t("about:blank"));
	(void) scpp::webview_runtime::load_html(view, scpp::string_t("<!doctype html><h1>Simple C++ WebView</h1>"));
	(void) scpp::webview_runtime::eval(view, scpp::string_t("document.body.dataset.ready = '1';"));
	(void) scpp::webview_runtime::enqueue_event(view, scpp::string_t("webview_message"), scpp::string_t("hello"), scpp::string_t("about:blank"));
	scpp::webview_runtime::close(view);

	auto app = scpp::shared<scpp::ui_app>();
	auto event_window = scpp::shared<scpp::ui_window>();
	auto event_view = scpp::shared<scpp::webview>();
	event_window->app_handle = app;
	event_window->native_handle = reinterpret_cast<void *>(1);
	event_view->window_handle = event_window;
	event_view->native_handle = reinterpret_cast<void *>(1);
	(void) scpp::webview_runtime::enqueue_event(
		event_view,
		scpp::string_t("webview_message"),
		scpp::string_t("payload"),
		scpp::string_t("https://example.com")
	);
	auto event = scpp::ui::app_next_event(app);
	(void) scpp::ui::event_type(event);
	(void) scpp::ui::event_window(event);
	(void) scpp::ui::event_webview(event);
	(void) scpp::ui::event_message(event);
	(void) scpp::ui::event_url(event);
	return 0;
}
