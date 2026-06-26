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
	auto window_result = scpp::ui::window_create(app, scpp::string_t("Simple C++ WebView"), scpp::int_t(900), scpp::int_t(600));
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
	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	bool saw_webview_ready = false;
	for (int i = 0; i < 240 && !saw_webview_ready; ++i) {
		(void) scpp::ui::app_poll(app);
		for (;;) {
			auto event = scpp::ui::app_next_event(app);
			if (!event.has_value().native_value()) {
				break;
			}
			if (scpp::ui::event_type(event).native_value() == "webview_ready") {
				saw_webview_ready = true;
			}
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}
	if (!saw_webview_ready) {
		std::cerr << "Did not receive webview_ready\n";
		return 1;
	}

	auto html_result = scpp::webview_runtime::load_html(
		view,
		scpp::string_t("<!doctype html><html><head><title>Simple C++ WebView</title></head><body><h1>Simple C++ WebView</h1><p>Native WebView2 smoke.</p></body></html>")
	);
	if (!html_result.has_value().native_value()) {
		std::cerr << html_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	bool saw_navigation_finished = false;
	bool saw_message = false;
	bool saw_title_changed = false;
	bool sent_message_probe = false;
	for (int i = 0; i < 240 && (!saw_navigation_finished || !saw_message || !saw_title_changed); ++i) {
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
			if (type == "webview_message" && scpp::ui::event_message(event).native_value() == "webview2-ready") {
				saw_message = true;
			}
			if (type == "webview_title_changed" && scpp::ui::event_message(event).native_value() == "Simple C++ WebView") {
				saw_title_changed = true;
			}
		}
		if (saw_navigation_finished && !sent_message_probe) {
			auto eval_result = scpp::webview_runtime::eval(
				view,
				scpp::string_t("if (window.chrome && window.chrome.webview) { window.chrome.webview.postMessage('webview2-ready'); }")
			);
			if (!eval_result.has_value().native_value()) {
				std::cerr << eval_result.error()->get_message().native_value() << "\n";
				return 1;
			}
			sent_message_probe = true;
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}
	if (!saw_navigation_finished) {
		std::cerr << "Did not receive webview_navigation_finished\n";
		return 1;
	}
	if (!saw_message) {
		std::cerr << "Did not receive webview_message\n";
		return 1;
	}
	if (!saw_title_changed) {
		std::cerr << "Did not receive webview_title_changed\n";
		return 1;
	}

	scpp::webview_runtime::close(view);
	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
