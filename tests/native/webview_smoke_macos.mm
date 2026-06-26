#include "scpp/ui.hpp"
#include "scpp/webview.hpp"

#include <chrono>
#include <iostream>
#include <thread>

int main() {
	auto app_result = scpp::ui::app_create();
	if (!app_result.has_value().native_value()) {
		std::cerr << app_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto app = app_result.value();
	auto window_result = scpp::ui::window_create(app, scpp::string_t("Simple C++ WebView"), scpp::int_t(700), scpp::int_t(460));
	if (!window_result.has_value().native_value()) {
		std::cerr << window_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto window = window_result.value();
	auto view_result = scpp::webview_runtime::create(window);
	if (!view_result.has_value().native_value()) {
		std::cerr << view_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto view = view_result.value();
	auto html_result = scpp::webview_runtime::load_html(
		view,
		scpp::string_t("<!doctype html><html><body style=\"font-family:-apple-system,sans-serif;margin:48px\"><h1>Simple C++ WebView</h1><p>Native WKWebView smoke.</p></body></html>")
	);
	if (!html_result.has_value().native_value()) {
		std::cerr << html_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	for (int i = 0; i < 160; ++i) {
		(void) scpp::ui::app_poll(app);
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	scpp::webview_runtime::close(view);
	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
