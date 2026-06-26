# WebView Runtime Module First Slice
Doc Status: planning

Date: 2026-06-26

## Purpose

Lock the first implementable slice for a Simple C++ cross-platform WebView rendering runtime module.

This note is planning guidance only. It does not define current language semantics, runtime contracts, or build behavior. It narrows the first milestone so implementation can start from a stable module boundary without copying Tauri/Wry naming or adopting a Rust framework contract.

## Relationship To Tauri/Wry

Tauri/Wry is reference material only.

Use it for:

- platform capability research
- native WebView backend expectations
- event-loop and window-container integration lessons
- examples of how WebView rendering fits above a windowing layer

Do not use it for:

- public Simple C++ naming
- source-level API shape
- semantic authority
- mandatory dependency on Rust or Wry
- framework-level app lifecycle, packaging, or permission design

The Simple C++ implementation should be a native runtime module with its own C++ facade and platform backends.

## Locked First-Slice Decisions

1. Runtime module name:
   - `webview`

2. Public helper family:
   - `webview_*`

3. Required lower module:
   - `ui`
   - WebView creation requires an existing `ui_window`.

4. First milestone scope:
   - create one WebView inside a `ui_window`
   - fill the full parent window by default
   - load a URL
   - load an HTML string
   - evaluate JavaScript as a fire-and-forget command
   - close/destroy cleanly
   - run through the existing `ui_app_poll(...)` event pump

5. Explicitly out of first slice:
   - independent WebView-owned top-level windows
   - arbitrary child bounds/layout
   - navigation policy callbacks
   - download handling
   - file chooser integration
   - drag/drop
   - devtools public API
   - JavaScript return-value marshalling
   - rich typed native/JS RPC
   - custom scheme/protocol serving
   - mobile packaging and store policy details

6. Creation failure shape:
   - use `result<T>`
   - source examples should use `take(...)` at WebView creation boundaries

7. Event direction:
   - first rendering smoke does not require source-level callbacks
   - later browser-like events should extend the existing `ui` event vocabulary rather than inventing a second event loop

## Platform Scope

First implementation target:

- Windows
- Linux
- macOS

Compile-smoke target:

- Android
- iOS

The public design should avoid blocking mobile, but the first useful behavior is desktop rendering inside a native window.

## Public Strict PHP++ Surface

First-slice example:

```php
$app ui_app = take(ui_app_create());
$window ui_window = take(ui_window_create($app, "Simple C++ WebView", 900, 600));
$view webview = take(webview_create($window));

webview_load_html($view, "<!doctype html><h1>Simple C++ WebView</h1>");
ui_window_show($window);

while (ui_app_poll($app)) {
	$event ui_event = ui_app_next_event($app);

	if (ui_event_type($event) === "window_close") {
		webview_close($view);
		ui_window_close(ui_event_window($event));
		ui_app_exit($app);
	}
}
```

URL loading:

```php
$view webview = take(webview_create($window));
webview_load_url($view, "https://example.com");
```

JavaScript command:

```php
webview_eval($view, "document.body.dataset.ready = '1';");
```

`webview_eval(...)` should not return a JavaScript value in the first slice. Returning values requires a typed marshalling contract and should be designed with the native-message channel.

## Handle Types

First-slice source-visible handle/value types:

- `webview`

This is a runtime-owned opaque handle. Source code must not depend on backend-specific platform handles such as `WKWebView *`, `HWND`, `ICoreWebView2 *`, `GtkWidget *`, or Android Java object references.

Future types:

- `webview_message`
- `webview_navigation`
- `webview_request`
- `webview_response`

## Creation And Failure Shape

Use `result<T>` for public creation helpers:

```php
$view webview = take(webview_create($window));
```

Where:

- `webview_create(...)` returns a `result<webview>`-shaped value.
- creation failures remain explicit and inspectable.
- missing platform dependencies should produce useful build or creation diagnostics rather than a raw crash.
- runtime exceptions remain acceptable for internal invariant failures.

## Event Model

First slice should prove rendering without requiring JavaScript-to-native messaging.

Reserved later event kinds:

- `webview_ready`
- `webview_message`
- `webview_navigation_started`
- `webview_navigation_finished`
- `webview_load_failed`
- `webview_title_changed`

Later native-message direction:

```php
if (ui_event_type($event) === "webview_message") {
	$payload string = ui_event_text($event);
}
```

This deliberately routes through `ui_event` so the application still has one event pump. The exact accessor names can be locked when the first message implementation starts.

## First Public Helper Set

Minimum first implementation:

```text
webview_create(ui_window $window): result<webview>
webview_load_url(webview $view, string $url): result<bool>
webview_load_html(webview $view, string $html): result<bool>
webview_eval(webview $view, string $script): result<bool>
webview_close(webview $view): void
```

Later but not first:

```text
webview_create_with_bounds(...)
webview_set_bounds(...)
webview_reload(...)
webview_go_back(...)
webview_go_forward(...)
webview_can_go_back(...)
webview_can_go_forward(...)
webview_set_user_agent(...)
webview_set_devtools_enabled(...)
webview_register_message_handler(...)
webview_register_scheme(...)
webview_eval_json(...)
```

## Native Backend Shape

Use a runtime facade plus platform implementations.

Suggested C++ layout:

```text
runtime/include/modules/webview/
  webview.hpp
  webview_types.hpp
  webview_backend.hpp

runtime/src/modules/webview/
  webview.cpp
  webview_backend_windows.cpp
  webview_backend_linux.cpp
  webview_backend_macos.mm
  webview_backend_ios.mm
  webview_backend_android.cpp
```

Notes:

- WebView backend code must treat `ui_window` as the parent/native-container owner.
- Backend-specific native handles remain private to runtime implementation.
- The `ui` event pump remains the application pump; WebView backends can integrate platform work into the same native loop.

## Backend Choices

Windows:

- Use Microsoft Edge WebView2.
- First target remains 64-bit Windows 11.
- The name WebView2 refers to the current Chromium-based Microsoft Edge runtime and SDK, not to an Electron-like bundled browser.
- Expect SDK/header availability to be a build concern and WebView2 runtime availability to be a deployment/runtime concern.

macOS:

- Use WebKit.framework / WKWebView.
- Backend source will likely need Objective-C++ (`.mm`).
- First smoke can render local HTML inside the existing AppKit `ui_window`.

Linux:

- Use WebKitGTK with the existing GTK `ui` backend.
- First dependency target should be the GTK/WebKitGTK package family used by current mainstream distros, such as `libwebkit2gtk-4.1-dev` on Debian/Ubuntu and `webkit2gtk4.1-devel` on Fedora.
- Because the first Linux `ui` backend is GTK, WebKitGTK is the least surprising first WebView backend.
- Wayland/X11 differences should stay hidden behind the GTK container path.

iOS:

- Use WKWebView.
- First slice should be compile/run smoke in the iOS simulator after the UIKit `ui` boundary can expose a parent view.
- Do not expose desktop-style multiple-window expectations.

Android:

- Use Android WebView through a JNI/activity boundary.
- First slice should be compile smoke for the native boundary.
- Rendering smoke should wait until the Android lifecycle/activity packaging path exists.

## Build-System Requirements

`webview` must be opt-in and imply or require `ui`:

```json
{
  "runtime": {
    "modules": ["ui", "webview"]
  }
}
```

`scpp build` must eventually:

- include `webview` runtime sources only when the module is enabled
- require or auto-enable `ui` when `webview` is requested
- link platform libraries/frameworks
- fail clearly when required platform dependencies are missing
- keep projects without `webview` unaffected

Initial platform link expectations:

- Windows: WebView2 loader/library plus Win32 UI libraries
- macOS: WebKit.framework plus AppKit/Cocoa dependencies already required by `ui`
- Linux: GTK plus WebKitGTK
- iOS: WebKit.framework plus UIKit
- Android: JNI/native activity integration plus Android WebView activity-side code later

## Testing Requirements

First-slice tests:

1. Module-off build:
   - normal strict project still builds without `webview`

2. Module-on compile/link:
   - strict project with `"modules": ["ui", "webview"]` links the runtime module where dependencies are available

3. Local HTML smoke:
   - create `ui_app`
   - create `ui_window`
   - create `webview`
   - load HTML containing visible text: `Simple C++ WebView`
   - show window briefly
   - capture screenshot where CI supports it
   - close and exit cleanly

4. URL smoke:
   - load a simple URL in manual/local testing
   - avoid network-dependent CI as the first signal

CI target order:

1. Linux GTK + WebKitGTK with Xvfb screenshot.
2. macOS WKWebView screenshot.
3. Windows WebView2 compile/link first, then launch/screenshot when runner support is understood.
4. Android NDK compile smoke.
5. iOS Simulator compile/run smoke after the UIKit parent-view boundary is ready.

## Implementation Milestones

1. Add planning/spec docs for the `webview` first slice.
2. Add module selection plumbing for `webview` without public helper exposure.
3. Add disabled-module facade stubs.
4. Add native C++ proof for one desktop backend.
5. Add strict symbol registry entries for the first helper set.
6. Add native smoke app that renders local HTML.
7. Add platform CI compile/link and screenshot smokes incrementally.

## Open Questions To Resolve During Implementation

- Should requesting `"webview"` automatically enable `"ui"`, or should `scpp build` require both modules explicitly with a clear diagnostic?
- Should `webview_load_html(...)` take an optional base URL in the first public API, or defer that until local asset loading is designed?
- Where should WebView2 SDK headers/libraries come from in Windows CI: checked-in minimal loader header, package restore, Visual Studio component, or a documented external dependency?
- Should Linux target only WebKitGTK 4.1 for the first slice, or support both 4.1 and newer package/API variants behind build detection?
- Should the first JavaScript native-message bridge use plain string payloads only, or reserve JSON-shaped helpers from the start?
