#include "scpp/ui.hpp"
#include "ui_smoke_surface.hpp"

#import <AppKit/AppKit.h>

#include <chrono>
#include <iostream>
#include <thread>

namespace {

void attach_smoke_surface(const scpp::shared_p<scpp::ui::window> &window) {
	NSWindow *native = static_cast<NSWindow *>(window->native_handle);
	NSView *content = [native contentView];
	NSRect bounds = [content bounds];
	NSTextField *label = [[NSTextField alloc] initWithFrame:NSMakeRect(0.0, (bounds.size.height - 48.0) / 2.0, bounds.size.width, 48.0)];
	[label setStringValue:[NSString stringWithUTF8String:scpp::ui::smoke::label]];
	[label setAlignment:NSTextAlignmentCenter];
	[label setTextColor:[NSColor blackColor]];
	[label setFont:[NSFont boldSystemFontOfSize:28.0]];
	[label setBezeled:NO];
	[label setDrawsBackground:NO];
	[label setEditable:NO];
	[label setSelectable:NO];
	[content addSubview:label];
	[label release];
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

	for (int i = 0; i < 120; ++i) {
		(void) scpp::ui::app_poll(app);
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
