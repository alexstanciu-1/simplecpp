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
		scpp::string_t("<!doctype html><html><head><title>Simple C++ WebView</title></head><body style=\"font-family:sans-serif;margin:48px\"><h1>Simple C++ WebView</h1><p>Native WebKitGTK smoke.</p></body></html>")
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

	bool saw_webview_ready = false;
	bool saw_navigation_finished = false;
	bool saw_title_changed = false;
	for (int i = 0; i < 160; ++i) {
		(void) scpp::ui::app_poll(app);
		for (;;) {
			auto event = scpp::ui::app_next_event(app);
			if (!event.has_value().native_value()) {
				break;
			}
			const auto type = scpp::ui::event_type(event).native_value();
			if (type == "webview_ready") {
				saw_webview_ready = true;
			}
			if (type == "webview_navigation_finished") {
				saw_navigation_finished = true;
			}
			if (type == "webview_title_changed") {
				saw_title_changed = true;
			}
		}
		if (saw_webview_ready && saw_navigation_finished && saw_title_changed) {
			break;
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}
	if (!saw_navigation_finished) {
		std::cerr << "Did not receive webview_navigation_finished\n";
		return 1;
	}

	scpp::webview_runtime::close(view);
	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
