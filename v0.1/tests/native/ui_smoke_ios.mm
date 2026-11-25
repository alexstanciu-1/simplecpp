#include "scpp/ui.hpp"
#include "ui_smoke_surface.hpp"

#import <UIKit/UIKit.h>

#include <cstdlib>
#include <iostream>

@interface ScppUiSmokeAppDelegate : UIResponder<UIApplicationDelegate>
@property (nonatomic, strong) UIWindow *systemWindow;
@end

@implementation ScppUiSmokeAppDelegate {
	scpp::shared_p<scpp::ui::app> _app;
	scpp::shared_p<scpp::ui::window> _window;
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
		scpp::string_t(scpp::ui::smoke::title),
		scpp::int_t(static_cast<int>(bounds.size.width)),
		scpp::int_t(static_cast<int>(bounds.size.height))
	);
	if (!windowResult.has_value().native_value()) {
		std::cerr << windowResult.error()->get_message().native_value() << "\n";
		std::exit(1);
	}
	_window = windowResult.value();
	self.systemWindow = static_cast<UIWindow *>(_window->native_handle);
	UIWindow *nativeWindow = static_cast<UIWindow *>(_window->native_handle);
	UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(0.0, 120.0, bounds.size.width, 80.0)];
	label.text = [NSString stringWithUTF8String:scpp::ui::smoke::label];
	label.textAlignment = NSTextAlignmentCenter;
	label.textColor = [UIColor blackColor];
	label.font = [UIFont boldSystemFontOfSize:28.0];
	[nativeWindow.rootViewController.view addSubview:label];
	[label release];

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
	(void)scpp::ui::window_close(_window);
	scpp::ui::app_exit(_app);
	std::exit(0);
}

@end

int main(int argc, char *argv[]) {
	@autoreleasepool {
		return UIApplicationMain(argc, argv, nil, NSStringFromClass([ScppUiSmokeAppDelegate class]));
	}
}
