#include "scpp/ui.hpp"

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
	auto window_result = scpp::ui::window_create(app, scpp::string_t("Simple C++ UI"), scpp::int_t(640), scpp::int_t(420));
	if (!window_result.has_value().native_value()) {
		std::cerr << window_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto window = window_result.value();
	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	for (int i = 0; i < 120; ++i) {
		(void) scpp::ui::app_poll(app);
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
