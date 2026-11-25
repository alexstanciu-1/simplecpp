#include "scpp/ui.hpp"
#include "ui_smoke_surface.hpp"

#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#ifndef NOMINMAX
#define NOMINMAX
#endif
#include <windows.h>

#include <chrono>
#include <iostream>
#include <thread>

namespace {

void attach_smoke_surface(const scpp::shared_p<scpp::ui::window> &window) {
	HWND native = static_cast<HWND>(window->native_handle);
	RECT bounds{};
	GetClientRect(native, &bounds);

	const int label_height = 48;
	HWND label = CreateWindowExA(
		0,
		"STATIC",
		scpp::ui::smoke::label,
		WS_CHILD | WS_VISIBLE | SS_CENTER,
		0,
		((bounds.bottom - bounds.top) - label_height) / 2,
		bounds.right - bounds.left,
		label_height,
		native,
		nullptr,
		GetModuleHandleA(nullptr),
		nullptr
	);
	if (label != nullptr) {
		SendMessageA(label, WM_SETFONT, reinterpret_cast<WPARAM>(GetStockObject(DEFAULT_GUI_FONT)), TRUE);
	}
}

} // namespace

int main() {
	auto app_result = scpp::ui::app_create();
	if (!app_result.has_value().native_value()) {
		std::cerr << app_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto app = app_result.value();
	auto window_result = scpp::ui::window_create(app, scpp::string_t(scpp::ui::smoke::title), scpp::int_t(640), scpp::int_t(420));
	if (!window_result.has_value().native_value()) {
		std::cerr << window_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto window = window_result.value();
	attach_smoke_surface(window);

	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	for (int i = 0; i < 40; ++i) {
		(void) scpp::ui::app_poll(app);
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
