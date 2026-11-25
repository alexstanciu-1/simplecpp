# UI And WebView Preview
Doc Status: supporting

This document describes the initial developer-preview surface for native `ui` windows and embedded `webview` rendering.

It is a release guide, not a new semantic authority. The planning source for the first slices remains under `specs/planning/`, and runtime behavior remains owned by the runtime module implementation and specs.

## Release Promise

The initial release is a developer preview for building native windows and one embedded WebView using strict PHP++.

Supported first platforms:

- Windows 11 with WebView2
- Linux with GTK and WebKitGTK
- macOS with AppKit and WKWebView
- iOS Simulator/device boundary with UIKit and WKWebView
- Android with an Activity-owned WebView exposed through JNI

Not included in this preview:

- menu bar
- system tray
- packaging/installers for mobile stores
- custom WebView schemes
- file chooser and download callbacks
- JavaScript return-value marshalling from `webview_eval(...)`
- multiple-window or advanced layout guarantees
- pixel-regression comparison beyond screenshot smoke artifacts

## Runtime Modules

Enable `webview` in `prism.json`:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    },
    "modules": ["json", "filesystem", "datetime", "webview"]
  }
}
```

Requesting `webview` automatically enables `ui`. `scpp explain-build` reports this as `ui (implicit via webview)`.

## Frozen Initial API

The first release should keep these strict PHP++ helper names stable for the preview line.

Window and event helpers:

```text
ui_app_create(): result<ui_app>
ui_window_create(ui_app $app, string $title, int $width, int $height): result<ui_window>
ui_window_show(ui_window $window): result<bool>
ui_window_close(ui_window $window): result<bool>
ui_app_poll(ui_app $app): bool
ui_app_next_event(ui_app $app): ui_event
ui_app_exit(ui_app $app): void
ui_event_type(ui_event $event): string
ui_event_window(ui_event $event): ui_window
ui_event_text(ui_event $event): string
ui_event_webview(ui_event $event): webview
ui_event_message(ui_event $event): string
ui_event_url(ui_event $event): string
```

WebView helpers:

```text
webview_create(ui_window $window): result<webview>
webview_load_url(webview $view, string $url): result<bool>
webview_load_html(webview $view, string $html): result<bool>
webview_load_app(webview $view, string $folder): result<bool>
webview_eval(webview $view, string $script): result<bool>
webview_reply_ok(webview $view, int $id, string $value_json): result<bool>
webview_reply_error(webview $view, int $id, string $code, string $message): result<bool>
webview_message_id(ui_event $event): int
webview_message_command(ui_event $event): string
webview_message_payload_json(ui_event $event): string
webview_close(webview $view): void
```

Browser-like event names:

```text
window_close
webview_ready
webview_message
webview_navigation_started
webview_navigation_finished
webview_load_failed
webview_title_changed
```

`webview_title_changed` is currently validated on Linux and Windows. macOS, iOS, and Android should treat it as part of the reserved event vocabulary until their title-change smokes are added.

## Minimal Window

```php
$err error;

$app ui_app = null;
if (!take($app, $err, ui_app_create())) {
	echo "ui_app_create failed\n";
	return;
}

$window ui_window = null;
if (!take($window, $err, ui_window_create($app, "Simple C++ UI", 700, 460))) {
	echo "ui_window_create failed\n";
	ui_app_exit($app);
	return;
}

$shown bool = false;
if (!take($shown, $err, ui_window_show($window))) {
	echo "ui_window_show failed\n";
	ui_app_exit($app);
	return;
}

$running bool = true;
while ($running) {
	if (ui_app_poll($app)) {
		$event ui_event = ui_app_next_event($app);
		if (ui_event_type($event) === "window_close") {
			$running = false;
		}
	}
}

ui_window_close($window);
ui_app_exit($app);
```

## Minimal WebView

```php
$err error;
$app ui_app = null;
$window ui_window = null;
$view webview = null;
$ok bool = false;

if (!take($app, $err, ui_app_create())) {
	echo "ui_app_create failed\n";
	return;
}

if (!take($window, $err, ui_window_create($app, "Simple C++ WebView", 900, 600))) {
	echo "ui_window_create failed\n";
	ui_app_exit($app);
	return;
}

if (!take($view, $err, webview_create($window))) {
	echo "webview_create failed\n";
	ui_window_close($window);
	ui_app_exit($app);
	return;
}

if (!take($ok, $err, webview_load_html($view, "<!doctype html><h1>Simple C++ WebView</h1>"))) {
	echo "webview_load_html failed\n";
	webview_close($view);
	ui_window_close($window);
	ui_app_exit($app);
	return;
}

take($ok, $err, ui_window_show($window));
```

## JavaScript Bridge Example

Use `docs/examples/php/strict/project_samples/strict_webview_bridge/` as the golden preview example.

It demonstrates:

- loading a local app folder with `webview_load_app(...)`
- browser-to-native calls with `window.scpp.invoke(...)`
- receiving `webview_message`
- reading `webview_message_id(...)`, `webview_message_command(...)`, and `webview_message_payload_json(...)`
- replying with `webview_reply_ok(...)` or `webview_reply_error(...)`

Run it from the sample folder on a GUI-capable machine:

```bash
php ../../../../../../bin/scpp.php build --build-runtime
.prism/build/main
```

## Platform Dependencies

Windows:

- first support target is 64-bit Windows 11
- applications rely on the installed Microsoft Edge WebView2 Runtime
- building native WebView2 code requires the Microsoft.Web.WebView2 SDK headers and loader library
- in local development, install the SDK package with NuGet or set `SCPP_WEBVIEW2_SDK_DIR` to a restored package root

Linux:

- first backend is GTK plus WebKitGTK
- Debian/Ubuntu packages usually include `pkg-config`, `libgtk-3-dev`, and `libwebkit2gtk-4.1-dev`
- Fedora package names usually include `gtk3-devel` and `webkit2gtk4.1-devel`
- headless CI uses Xvfb; local use needs a display session

macOS:

- first backend is WKWebView through WebKit.framework
- native smokes build as Objective-C++

iOS:

- first backend is UIKit plus WKWebView
- current CI proof is simulator build, install, launch, and screenshot

Android:

- first backend uses an Activity-owned `android.webkit.WebView`
- the native runtime attaches to the Activity/WebView through JNI
- CI builds a signed smoke APK and runs it in an emulator

## Diagnostics Checklist

Use this sequence when a preview app does not build or render:

```bash
scpp --doctor
scpp build --build-runtime
scpp explain-build
scpp last-run
scpp full-last-run
```

Expected `scpp explain-build` details for WebView projects:

- `Runtime modules:` includes `webview` and `ui (implicit via webview)`
- `WebView backend:` prints the selected backend or `none (disabled)`
- `WebView diagnostic:` prints missing dependency guidance when a backend cannot be enabled

## Release Gate

Before tagging an initial preview release, the branch head should have a green CI run covering:

- Windows WebView2 build, launch, screenshot, message event, and title event
- Linux GTK/WebKitGTK build, Xvfb launch, screenshot, and WebView events
- macOS AppKit/WKWebView build, launch, screenshot, and WebView events
- iOS simulator UI and WKWebView build, launch, and screenshot
- Android NDK compile, Activity compile, APK package/sign, emulator launch, event bridge assertion, and screenshot

The last known green branch-head run when this guide was written was `28249109804`.
