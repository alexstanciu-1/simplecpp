#include "scpp/ui.hpp"
#include "scpp/webview.hpp"

#include <chrono>
#include <iostream>
#include <string>
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
	auto load_app_result = scpp::webview_runtime::load_app(
		view,
		scpp::string_t("tests/fixtures/webview_app")
	);
	if (!load_app_result.has_value().native_value()) {
		std::cerr << load_app_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	bool replied = false;
	bool saw_webview_ready = false;
	bool saw_navigation_finished = false;
	bool saw_title_changed = false;
	bool sent_bridge_probe = false;
	for (int i = 0; i < 240 && (!replied || !saw_webview_ready || !saw_navigation_finished || !saw_title_changed); ++i) {
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
			if (type == "webview_title_changed" && scpp::ui::event_message(event).native_value() == "Simple C++ WebView App") {
				saw_title_changed = true;
			}
			if (type == "webview_message") {
				const auto id = scpp::webview_runtime::message_id(event);
				const auto command = scpp::webview_runtime::message_command(event);
				if (!replied && command.native_value() == "bridge.ping") {
					auto reply_result = scpp::webview_runtime::reply_ok(
						view,
						id,
						scpp::string_t("{\"status\":\"pong\",\"transport\":\"WebView2 WebMessageReceived\"}")
					);
					if (!reply_result.has_value().native_value()) {
						std::cerr << "webview bridge reply failed: " << reply_result.error()->get_message().native_value() << "\n";
						return 1;
					}
					replied = true;
				}
			}
		}
		if (saw_navigation_finished && !sent_bridge_probe) {
			auto eval_result = scpp::webview_runtime::eval(
				view,
				scpp::string_t("if(window.scpp&&window.scpp.invoke){window.scpp.invoke('bridge.ping',{source:'webview2-smoke'}).catch(function(){});}")
			);
			if (!eval_result.has_value().native_value()) {
				std::cerr << eval_result.error()->get_message().native_value() << "\n";
				return 1;
			}
			sent_bridge_probe = true;
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}
	if (!saw_webview_ready) {
		std::cerr << "Did not receive webview_ready\n";
		return 1;
	}
	if (!saw_navigation_finished) {
		std::cerr << "Did not receive webview_navigation_finished\n";
		return 1;
	}
	if (!replied) {
		std::cerr << "Did not complete webview bridge reply\n";
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
