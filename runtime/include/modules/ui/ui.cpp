#include "modules/ui/ui.hpp"

#if SCPP_HAS_UI

#if defined(SCPP_UI_BACKEND_GTK) && SCPP_UI_BACKEND_GTK
#include <gtk/gtk.h>
#endif
#if defined(SCPP_UI_BACKEND_WIN32) && SCPP_UI_BACKEND_WIN32
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#ifndef NOMINMAX
#define NOMINMAX
#endif
#include <windows.h>
#include <objbase.h>
#endif
#if defined(SCPP_UI_BACKEND_APPKIT) && SCPP_UI_BACKEND_APPKIT
#import <AppKit/AppKit.h>
#endif
#if defined(SCPP_UI_BACKEND_UIKIT) && SCPP_UI_BACKEND_UIKIT
#import <UIKit/UIKit.h>
#endif

#include <string>

#if defined(SCPP_UI_BACKEND_APPKIT) && SCPP_UI_BACKEND_APPKIT
namespace scpp::ui {
bool appkit_window_should_close(void *data);
void appkit_window_will_close(void *data);
}

@interface ScppUiWindowDelegate : NSObject<NSWindowDelegate> {
	void *_state;
}
- (instancetype)initWithState:(void *)state;
@end

@implementation ScppUiWindowDelegate
- (instancetype)initWithState:(void *)state
{
	self = [super init];
	if (self != nil) {
		_state = state;
	}
	return self;
}

- (BOOL)windowShouldClose:(id)sender
{
	(void)sender;
	return scpp::ui::appkit_window_should_close(_state) ? YES : NO;
}

- (void)windowWillClose:(NSNotification *)notification
{
	(void)notification;
	scpp::ui::appkit_window_will_close(_state);
}
@end
#endif

namespace scpp::ui {

string_t event_type(const shared_p<event> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return string_t("");
	}
	return target->type;
}

shared_p<window> event_window(const shared_p<event> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return null;
	}
	return target->window_handle;
}

shared_p<webview_runtime::view> event_webview(const shared_p<event> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return null;
	}
	return target->webview_handle;
}

string_t event_message(const shared_p<event> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return string_t("");
	}
	return target->message;
}

string_t event_url(const shared_p<event> &target) {
	if (!target.has_value().native_value() || target.get() == nullptr) {
		return string_t("");
	}
	return target->url;
}

#if defined(SCPP_UI_BACKEND_GTK) && SCPP_UI_BACKEND_GTK

namespace {

struct callback_state final {
	shared_p<app> owner;
	shared_p<window> target;
};

void destroy_callback_state(gpointer data, GClosure *) {
	delete static_cast<callback_state *>(data);
}

gboolean handle_window_delete(GtkWidget *, GdkEvent *, gpointer data) {
	auto *state = static_cast<callback_state *>(data);
	if (state == nullptr || !state->owner.has_value().native_value()) {
		return TRUE;
	}

	auto event_value = shared<event>();
	event_value->type = string_t("window_close");
	event_value->window_handle = state->target;
	state->owner->pending_events.push_back(event_value);
	return TRUE;
}

void handle_window_destroy(GtkWidget *, gpointer data) {
	auto *state = static_cast<callback_state *>(data);
	if (state == nullptr || !state->target.has_value().native_value()) {
		return;
	}
	state->target->closed = bool_t(true);
	state->target->visible = bool_t(false);
	state->target->native_handle = nullptr;
}

[[nodiscard]] result<shared_p<app>> require_app(const shared_p<app> &owner, const char *function_name) {
	if (!owner.has_value().native_value() || owner.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid ui_app"));
	}
	return owner;
}

[[nodiscard]] result<shared_p<window>> require_window(const shared_p<window> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open ui_window"));
	}
	return target;
}

} // namespace

result<shared_p<app>> app_create() {
	int argc = 0;
	char **argv = nullptr;
	if (!gtk_init_check(&argc, &argv)) {
		return error_t(string_t("ui_app_create(): GTK initialization failed; check DISPLAY/Wayland session availability"));
	}

	auto owner = shared<app>();
	owner->backend = string_t("gtk");
	return owner;
}

result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t &width, const int_t &height) {
	auto checked_owner = require_app(owner, "ui_window_create()");
	if (!checked_owner.has_value().native_value()) {
		return *checked_owner.error();
	}
	if (width.native_value() <= 0 || height.native_value() <= 0) {
		return error_t(string_t("ui_window_create(): width and height must be positive"));
	}

	GtkWidget *native = gtk_window_new(GTK_WINDOW_TOPLEVEL);
	if (native == nullptr) {
		return error_t(string_t("ui_window_create(): GTK failed to create a native window"));
	}

	auto target = shared<window>();
	target->app_handle = owner;
	target->title = title;
	target->width = width;
	target->height = height;
	target->native_handle = native;

	gtk_window_set_title(GTK_WINDOW(native), title.native_value().c_str());
	gtk_window_set_default_size(GTK_WINDOW(native), static_cast<gint>(width.native_value()), static_cast<gint>(height.native_value()));

	auto *delete_state = new callback_state{owner, target};
	g_signal_connect_data(
		G_OBJECT(native),
		"delete-event",
		G_CALLBACK(handle_window_delete),
		delete_state,
		destroy_callback_state,
		static_cast<GConnectFlags>(0)
	);

	auto *destroy_state = new callback_state{owner, target};
	g_signal_connect_data(
		G_OBJECT(native),
		"destroy",
		G_CALLBACK(handle_window_destroy),
		destroy_state,
		destroy_callback_state,
		static_cast<GConnectFlags>(0)
	);

	return target;
}

result<bool_t> window_show(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_show()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	gtk_widget_show_all(GTK_WIDGET(target->native_handle));
	target->visible = bool_t(true);
	return bool_t(true);
}

result<bool_t> window_close(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	gtk_widget_destroy(GTK_WIDGET(target->native_handle));
	target->closed = bool_t(true);
	target->visible = bool_t(false);
	target->native_handle = nullptr;
	return bool_t(true);
}

bool_t app_poll(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->exit_requested.native_value()) {
		return bool_t(false);
	}
	while (gtk_events_pending() != 0) {
		gtk_main_iteration_do(FALSE);
	}
	return bool_t(!owner->pending_events.empty());
}

shared_p<event> app_next_event(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->pending_events.empty()) {
		return null;
	}
	auto value = owner->pending_events.front();
	owner->pending_events.pop_front();
	return value;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

#elif defined(SCPP_UI_BACKEND_WIN32) && SCPP_UI_BACKEND_WIN32

namespace {

constexpr const char *window_class_name = "SimpleCppUiWindow";

struct win32_callback_state final {
	shared_p<app> owner;
	shared_p<window> target;
};

LRESULT CALLBACK window_proc(HWND native, UINT message, WPARAM wparam, LPARAM lparam) {
	if (message == WM_NCCREATE) {
		auto *create = reinterpret_cast<CREATESTRUCTA *>(lparam);
		SetWindowLongPtrA(native, GWLP_USERDATA, reinterpret_cast<LONG_PTR>(create->lpCreateParams));
	}

	auto *state = reinterpret_cast<win32_callback_state *>(GetWindowLongPtrA(native, GWLP_USERDATA));
	if (state == nullptr) {
		return DefWindowProcA(native, message, wparam, lparam);
	}

	if (message == WM_CLOSE) {
		if (state->owner.has_value().native_value()) {
			auto event_value = shared<event>();
			event_value->type = string_t("window_close");
			event_value->window_handle = state->target;
			state->owner->pending_events.push_back(event_value);
		}
		return 0;
	}

	if (message == WM_DESTROY) {
		if (state->target.has_value().native_value()) {
			state->target->closed = bool_t(true);
			state->target->visible = bool_t(false);
			state->target->native_handle = nullptr;
		}
		SetWindowLongPtrA(native, GWLP_USERDATA, 0);
		return 0;
	}

	return DefWindowProcA(native, message, wparam, lparam);
}

[[nodiscard]] result<shared_p<app>> require_app(const shared_p<app> &owner, const char *function_name) {
	if (!owner.has_value().native_value() || owner.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid ui_app"));
	}
	return owner;
}

[[nodiscard]] result<shared_p<window>> require_window(const shared_p<window> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open ui_window"));
	}
	return target;
}

[[nodiscard]] bool register_window_class(HINSTANCE instance) {
	WNDCLASSA existing{};
	if (GetClassInfoA(instance, window_class_name, &existing) != 0) {
		return true;
	}

	WNDCLASSA window_class{};
	window_class.lpfnWndProc = window_proc;
	window_class.hInstance = instance;
	window_class.lpszClassName = window_class_name;
	window_class.hCursor = LoadCursorA(nullptr, IDC_ARROW);
	window_class.hbrBackground = reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1);
	return RegisterClassA(&window_class) != 0;
}

} // namespace

result<shared_p<app>> app_create() {
	HRESULT com_result = CoInitializeEx(nullptr, COINIT_APARTMENTTHREADED);
	if (FAILED(com_result)) {
		return error_t(string_t("ui_app_create(): Win32 failed to initialize an STA COM apartment"));
	}

	HINSTANCE instance = GetModuleHandleA(nullptr);
	if (instance == nullptr) {
		CoUninitialize();
		return error_t(string_t("ui_app_create(): Win32 failed to resolve the current module handle"));
	}
	if (!register_window_class(instance)) {
		CoUninitialize();
		return error_t(string_t("ui_app_create(): Win32 failed to register the window class"));
	}

	auto owner = shared<app>();
	owner->backend = string_t("win32");
	owner->native_handle = instance;
	owner->native_state = reinterpret_cast<void *>(1);
	return owner;
}

result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t &width, const int_t &height) {
	auto checked_owner = require_app(owner, "ui_window_create()");
	if (!checked_owner.has_value().native_value()) {
		return *checked_owner.error();
	}
	if (width.native_value() <= 0 || height.native_value() <= 0) {
		return error_t(string_t("ui_window_create(): width and height must be positive"));
	}

	auto target = shared<window>();
	target->app_handle = owner;
	target->title = title;
	target->width = width;
	target->height = height;

	auto *state = new win32_callback_state{owner, target};
	RECT frame{0, 0, static_cast<LONG>(width.native_value()), static_cast<LONG>(height.native_value())};
	const DWORD style = WS_OVERLAPPEDWINDOW;
	AdjustWindowRect(&frame, style, FALSE);

	HWND native = CreateWindowExA(
		0,
		window_class_name,
		title.native_value().c_str(),
		style,
		CW_USEDEFAULT,
		CW_USEDEFAULT,
		frame.right - frame.left,
		frame.bottom - frame.top,
		nullptr,
		nullptr,
		static_cast<HINSTANCE>(owner->native_handle),
		state
	);
	if (native == nullptr) {
		delete state;
		return error_t(string_t("ui_window_create(): Win32 failed to create a native window"));
	}

	target->native_handle = native;
	target->native_state = state;
	return target;
}

result<bool_t> window_show(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_show()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	ShowWindow(static_cast<HWND>(target->native_handle), SW_SHOWNORMAL);
	UpdateWindow(static_cast<HWND>(target->native_handle));
	target->visible = bool_t(true);
	return bool_t(true);
}

result<bool_t> window_close(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}

	HWND native = static_cast<HWND>(target->native_handle);
	auto *state = static_cast<win32_callback_state *>(target->native_state);
	DestroyWindow(native);
	delete state;
	target->native_state = nullptr;
	target->native_handle = nullptr;
	target->closed = bool_t(true);
	target->visible = bool_t(false);
	return bool_t(true);
}

bool_t app_poll(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->exit_requested.native_value()) {
		return bool_t(false);
	}

	MSG message{};
	while (PeekMessageA(&message, nullptr, 0, 0, PM_REMOVE) != 0) {
		TranslateMessage(&message);
		DispatchMessageA(&message);
	}
	return bool_t(!owner->pending_events.empty());
}

shared_p<event> app_next_event(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->pending_events.empty()) {
		return null;
	}
	auto value = owner->pending_events.front();
	owner->pending_events.pop_front();
	return value;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
		if (owner->native_state != nullptr) {
			CoUninitialize();
			owner->native_state = nullptr;
		}
	}
}

#elif defined(SCPP_UI_BACKEND_APPKIT) && SCPP_UI_BACKEND_APPKIT

namespace {

struct appkit_callback_state final {
	shared_p<app> owner;
	shared_p<window> target;
};

[[nodiscard]] result<shared_p<app>> require_app(const shared_p<app> &owner, const char *function_name) {
	if (!owner.has_value().native_value() || owner.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid ui_app"));
	}
	return owner;
}

[[nodiscard]] result<shared_p<window>> require_window(const shared_p<window> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open ui_window"));
	}
	return target;
}

} // namespace

bool appkit_window_should_close(void *data) {
	auto *state = static_cast<appkit_callback_state *>(data);
	if (state == nullptr || !state->owner.has_value().native_value()) {
		return false;
	}

	auto event_value = shared<event>();
	event_value->type = string_t("window_close");
	event_value->window_handle = state->target;
	state->owner->pending_events.push_back(event_value);
	return false;
}

void appkit_window_will_close(void *data) {
	auto *state = static_cast<appkit_callback_state *>(data);
	if (state == nullptr || !state->target.has_value().native_value()) {
		return;
	}
	state->target->closed = bool_t(true);
	state->target->visible = bool_t(false);
	state->target->native_handle = nullptr;
}

result<shared_p<app>> app_create() {
	@autoreleasepool {
		NSApplication *application = [NSApplication sharedApplication];
		if (application == nil) {
			return error_t(string_t("ui_app_create(): AppKit failed to create NSApplication"));
		}
		[application setActivationPolicy:NSApplicationActivationPolicyRegular];
		[application finishLaunching];
		auto owner = shared<app>();
		owner->backend = string_t("appkit");
		owner->native_handle = application;
		return owner;
	}
}

result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t &width, const int_t &height) {
	auto checked_owner = require_app(owner, "ui_window_create()");
	if (!checked_owner.has_value().native_value()) {
		return *checked_owner.error();
	}
	if (width.native_value() <= 0 || height.native_value() <= 0) {
		return error_t(string_t("ui_window_create(): width and height must be positive"));
	}

	@autoreleasepool {
		const NSRect frame = NSMakeRect(160.0, 160.0, static_cast<CGFloat>(width.native_value()), static_cast<CGFloat>(height.native_value()));
		const NSWindowStyleMask style = NSWindowStyleMaskTitled | NSWindowStyleMaskClosable | NSWindowStyleMaskMiniaturizable | NSWindowStyleMaskResizable;
		NSWindow *native = [[NSWindow alloc] initWithContentRect:frame styleMask:style backing:NSBackingStoreBuffered defer:NO];
		if (native == nil) {
			return error_t(string_t("ui_window_create(): AppKit failed to create a native window"));
		}
		[native setReleasedWhenClosed:NO];

		auto target = shared<window>();
		target->app_handle = owner;
		target->title = title;
		target->width = width;
		target->height = height;
		target->native_handle = native;

		auto *state = new appkit_callback_state{owner, target};
		ScppUiWindowDelegate *delegate = [[ScppUiWindowDelegate alloc] initWithState:state];
		target->native_state = state;
		target->native_delegate = delegate;

		[native setTitle:[NSString stringWithUTF8String:title.native_value().c_str()]];
		[native setDelegate:delegate];
		[native center];
		return target;
	}
}

result<bool_t> window_show(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_show()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}

	@autoreleasepool {
		NSWindow *native = static_cast<NSWindow *>(target->native_handle);
		[native makeKeyAndOrderFront:nil];
		[NSApp activateIgnoringOtherApps:YES];
		target->visible = bool_t(true);
		return bool_t(true);
	}
}

result<bool_t> window_close(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}

	@autoreleasepool {
		NSWindow *native = static_cast<NSWindow *>(target->native_handle);
		[native setDelegate:nil];
		[native orderOut:nil];
		[native close];
		[native release];
		if (target->native_delegate != nullptr) {
			[static_cast<ScppUiWindowDelegate *>(target->native_delegate) release];
			target->native_delegate = nullptr;
		}
		delete static_cast<appkit_callback_state *>(target->native_state);
		target->native_state = nullptr;
		target->native_handle = nullptr;
		target->closed = bool_t(true);
		target->visible = bool_t(false);
		return bool_t(true);
	}
}

bool_t app_poll(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->exit_requested.native_value()) {
		return bool_t(false);
	}

	@autoreleasepool {
		for (;;) {
			NSEvent *event_value = [NSApp nextEventMatchingMask:NSEventMaskAny
				untilDate:[NSDate distantPast]
				inMode:NSDefaultRunLoopMode
				dequeue:YES];
			if (event_value == nil) {
				break;
			}
			[NSApp sendEvent:event_value];
			[NSApp updateWindows];
		}
	}
	return bool_t(!owner->pending_events.empty());
}

shared_p<event> app_next_event(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->pending_events.empty()) {
		return null;
	}
	auto value = owner->pending_events.front();
	owner->pending_events.pop_front();
	return value;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

#elif defined(SCPP_UI_BACKEND_UIKIT) && SCPP_UI_BACKEND_UIKIT

namespace {

[[nodiscard]] result<shared_p<app>> require_app(const shared_p<app> &owner, const char *function_name) {
	if (!owner.has_value().native_value() || owner.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid ui_app"));
	}
	return owner;
}

[[nodiscard]] result<shared_p<window>> require_window(const shared_p<window> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open ui_window"));
	}
	return target;
}

} // namespace

result<shared_p<app>> app_create() {
	@autoreleasepool {
		UIApplication *application = [UIApplication sharedApplication];
		if (application == nil) {
			return error_t(string_t("ui_app_create(): UIKit requires an active UIApplication"));
		}
		auto owner = shared<app>();
		owner->backend = string_t("uikit");
		owner->native_handle = application;
		return owner;
	}
}

result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t &width, const int_t &height) {
	auto checked_owner = require_app(owner, "ui_window_create()");
	if (!checked_owner.has_value().native_value()) {
		return *checked_owner.error();
	}
	if (width.native_value() <= 0 || height.native_value() <= 0) {
		return error_t(string_t("ui_window_create(): width and height must be positive"));
	}

	@autoreleasepool {
		const CGRect frame = CGRectMake(0.0, 0.0, static_cast<CGFloat>(width.native_value()), static_cast<CGFloat>(height.native_value()));
		UIWindow *native = [[UIWindow alloc] initWithFrame:frame];
		if (native == nil) {
			return error_t(string_t("ui_window_create(): UIKit failed to create a native window"));
		}

		UIViewController *controller = [[UIViewController alloc] init];
		controller.title = [NSString stringWithUTF8String:title.native_value().c_str()];
		controller.view.backgroundColor = [UIColor whiteColor];
		native.rootViewController = controller;
		[controller release];

		auto target = shared<window>();
		target->app_handle = owner;
		target->title = title;
		target->width = width;
		target->height = height;
		target->native_handle = native;
		return target;
	}
}

result<bool_t> window_show(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_show()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}

	@autoreleasepool {
		UIWindow *native = static_cast<UIWindow *>(target->native_handle);
		[native makeKeyAndVisible];
		target->visible = bool_t(true);
		return bool_t(true);
	}
}

result<bool_t> window_close(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}

	@autoreleasepool {
		UIWindow *native = static_cast<UIWindow *>(target->native_handle);
		native.hidden = YES;
		native.rootViewController = nil;
		[native release];
		target->native_handle = nullptr;
		target->closed = bool_t(true);
		target->visible = bool_t(false);
		return bool_t(true);
	}
}

bool_t app_poll(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->exit_requested.native_value()) {
		return bool_t(false);
	}
	return bool_t(!owner->pending_events.empty());
}

shared_p<event> app_next_event(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->pending_events.empty()) {
		return null;
	}
	auto value = owner->pending_events.front();
	owner->pending_events.pop_front();
	return value;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

#else

result<shared_p<app>> app_create() {
	return error_t(string_t("ui_app_create(): native ui backend is not implemented yet for this platform"));
}

result<shared_p<window>> window_create(const shared_p<app> &, const string_t &, const int_t &, const int_t &) {
	return error_t(string_t("ui_window_create(): native ui backend is not implemented yet for this platform"));
}

result<bool_t> window_show(const shared_p<window> &) {
	return error_t(string_t("ui_window_show(): native ui backend is not implemented yet for this platform"));
}

result<bool_t> window_close(const shared_p<window> &) {
	return error_t(string_t("ui_window_close(): native ui backend is not implemented yet for this platform"));
}

bool_t app_poll(const shared_p<app> &) {
	return bool_t(false);
}

shared_p<event> app_next_event(const shared_p<app> &) {
	return null;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

#endif

} // namespace scpp::ui

#endif
