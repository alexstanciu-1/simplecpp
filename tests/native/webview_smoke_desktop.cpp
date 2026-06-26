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
	for (int i = 0; i < 240; ++i) {
		if (scpp::ui::app_poll(app).native_value()) {
			auto event = scpp::ui::app_next_event(app);
			if (scpp::ui::event_type(event).native_value() == "webview_message") {
				const auto id = scpp::webview_runtime::message_id(event);
				const auto command = scpp::webview_runtime::message_command(event);
				const auto payload = scpp::webview_runtime::message_payload_json(event);
				std::cout << "webview_message: id=" << id.native_value()
					<< " command=" << command.native_value()
					<< " payload=" << payload.native_value() << "\n";
				if (!replied && command.native_value() == "bridge.ping") {
					auto reply_result = scpp::webview_runtime::reply_ok(
						view,
						id,
						scpp::string_t("{\"status\":\"pong\",\"transport\":\"WebKitGTK script-message\"}")
					);
					if (!reply_result.has_value().native_value()) {
						std::cerr << "webview bridge reply failed: " << reply_result.error()->get_message().native_value() << "\n";
					} else {
						std::cout << "webview_reply_ok: sent\n";
					}
					replied = true;
				}
			}
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	scpp::webview_runtime::close(view);
	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
