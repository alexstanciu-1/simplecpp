#include "scpp/ui.hpp"
#include "scpp/webview.hpp"

#import <UIKit/UIKit.h>

#include <cstdlib>
#include <iostream>

@interface ScppWebViewSmokeAppDelegate : UIResponder<UIApplicationDelegate>
@property (nonatomic, strong) UIWindow *systemWindow;
@end

@implementation ScppWebViewSmokeAppDelegate {
	scpp::shared_p<scpp::ui::app> _app;
	scpp::shared_p<scpp::ui::window> _window;
	scpp::shared_p<scpp::webview> _view;
}

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions
{
	(void)application;
	(void)launchOptions;

	auto appResult = scpp::ui::app_create();
	if (!appResult.has_value().native_value()) {
		std::cerr << appResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}
	_app = appResult.value();

	CGRect bounds = UIScreen.mainScreen.bounds;
	auto windowResult = scpp::ui::window_create(
		_app,
		scpp::string_t("Simple C++ WebView"),
		scpp::int_t(static_cast<int>(bounds.size.width)),
		scpp::int_t(static_cast<int>(bounds.size.height))
	);
	if (!windowResult.has_value().native_value()) {
		std::cerr << windowResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}
	_window = windowResult.value();
	self.systemWindow = static_cast<UIWindow *>(_window->native_handle);

	auto viewResult = scpp::webview_runtime::create(_window);
	if (!viewResult.has_value().native_value()) {
		std::cerr << viewResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}
	_view = viewResult.value();

	auto htmlResult = scpp::webview_runtime::load_html(
		_view,
		scpp::string_t("<!doctype html><html><body><h1>Simple C++ WebView</h1><p>Native iOS WKWebView smoke.</p><script>window.webkit.messageHandlers.SimpleCpp.postMessage('webkit-ready');</script></body></html>")
	);
	if (!htmlResult.has_value().native_value()) {
		std::cerr << htmlResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}

	auto showResult = scpp::ui::window_show(_window);
	if (!showResult.has_value().native_value()) {
		std::cerr << showResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}

	[NSTimer scheduledTimerWithTimeInterval:30.0 target:self selector:@selector(finishSmoke:) userInfo:nil repeats:NO];
	return YES;
}

- (void)finishSmoke:(NSTimer *)timer
{
	(void)timer;
	bool sawReady = false;
	bool sawNavigationFinished = false;
	bool sawMessage = false;
	while (_app.has_value().native_value() && !_app->pending_events.empty()) {
		auto event = _app->pending_events.front();
		_app->pending_events.pop_front();
		if (!event.has_value().native_value()) {
			continue;
		}
		const auto type = event->type.native_value();
		if (type == "webview_ready") {
			sawReady = true;
		}
		if (type == "webview_navigation_finished") {
			sawNavigationFinished = true;
		}
		if (type == "webview_message" && event->message.native_value() == "webkit-ready") {
			sawMessage = true;
		}
	}
	if (!sawReady) {
		std::cerr << "Did not receive webview_ready\n";
		std::exit(1);
	}
	if (!sawNavigationFinished) {
		std::cerr << "Did not receive webview_navigation_finished\n";
		std::exit(1);
	}
	if (!sawMessage) {
		std::cerr << "Did not receive webview_message\n";
		std::exit(1);
	}
	scpp::webview_runtime::close(_view);
	(void)scpp::ui::window_close(_window);
	scpp::ui::app_exit(_app);
	std::exit(0);
}

@end

int main(int argc, char *argv[]) {
	@autoreleasepool {
		return UIApplicationMain(argc, argv, nil, NSStringFromClass([ScppWebViewSmokeAppDelegate class]));
	}
}
