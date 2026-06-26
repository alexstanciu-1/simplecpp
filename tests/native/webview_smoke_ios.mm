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
	bool _sawReady;
	bool _sawNavigationFinished;
	bool _replied;
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
	_sawReady = false;
	_sawNavigationFinished = false;
	_replied = false;

	auto htmlResult = scpp::webview_runtime::load_html(
		_view,
		scpp::string_t("<!doctype html><html><body><h1>Simple C++ WebView</h1><p id=\"status\">Waiting for bridge reply...</p><script>setTimeout(async function(){var status=document.getElementById('status');try{var reply=await window.scpp.invoke('bridge.ping',{source:'ios-smoke'});status.textContent='Bridge reply received: '+JSON.stringify(reply);document.body.dataset.bridge='ok';}catch(error){status.textContent='Bridge reply failed: '+(error&&error.message?error.message:String(error));document.body.dataset.bridge='error';}},500);</script></body></html>")
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

	[NSTimer scheduledTimerWithTimeInterval:0.05 target:self selector:@selector(pollSmoke:) userInfo:nil repeats:YES];
	[NSTimer scheduledTimerWithTimeInterval:30.0 target:self selector:@selector(finishSmoke:) userInfo:nil repeats:NO];
	return YES;
}

- (void)pollSmoke:(NSTimer *)timer
{
	(void)timer;
	if (!_app.has_value().native_value() || !_view.has_value().native_value()) {
		return;
	}
		if (scpp::ui::app_poll(_app).native_value()) {
			auto event = scpp::ui::app_next_event(_app);
			const auto type = scpp::ui::event_type(event).native_value();
			if (type == "webview_ready") {
				_sawReady = true;
			}
			if (type == "webview_navigation_finished") {
				_sawNavigationFinished = true;
			}
			if (type == "webview_message") {
				const auto id = scpp::webview_runtime::message_id(event);
				const auto command = scpp::webview_runtime::message_command(event);
				if (!_replied && command.native_value() == "bridge.ping") {
				auto replyResult = scpp::webview_runtime::reply_ok(
					_view,
					id,
					scpp::string_t("{\"status\":\"pong\",\"transport\":\"WKScriptMessageHandler\"}")
				);
				if (!replyResult.has_value().native_value()) {
					std::cerr << "webview bridge reply failed: " << replyResult.error()->get_message().native_value() << "\n";
				}
				_replied = true;
			}
		}
	}
}

	- (void)finishSmoke:(NSTimer *)timer
	{
		(void)timer;
		while (_app.has_value().native_value() && !_app->pending_events.empty()) {
			auto event = _app->pending_events.front();
			_app->pending_events.pop_front();
		if (!event.has_value().native_value()) {
			continue;
			}
			const auto type = event->type.native_value();
			if (type == "webview_ready") {
				_sawReady = true;
			}
			if (type == "webview_navigation_finished") {
				_sawNavigationFinished = true;
			}
		}
		if (!_sawReady) {
			std::cerr << "Did not receive webview_ready\n";
			std::exit(1);
		}
		if (!_sawNavigationFinished) {
			std::cerr << "Did not receive webview_navigation_finished\n";
			std::exit(1);
		}
		if (!_replied) {
			std::cerr << "Did not complete webview bridge reply\n";
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
