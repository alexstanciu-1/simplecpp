# WebView Cross-Platform Backlog
Doc Status: planning

Date: 2026-06-26

## Purpose

Track the remaining work required to finish the Simple C++ `webview` runtime module across the supported OS set:

- Windows 11
- macOS
- Linux
- iOS
- Android

This is a planning backlog, not a semantic authority. The first locked API and backend choices remain in `specs/planning/webview_runtime_module_first_slice_2026_06_26.md`.

## Current Status

| Platform | Backend | Status | CI signal |
| --- | --- | --- | --- |
| Linux | WebKitGTK | Implemented first rendering slice | Build, launch under Xvfb, screenshot artifact |
| macOS | WKWebView | Implemented first rendering slice | Build, launch, `screencapture` artifact |
| Windows 11 | WebView2 | Implemented first rendering slice | Build, launch, screenshot artifact |
| iOS | WKWebView | Implemented first rendering slice | Build, simulator launch, screenshot artifact |
| Android | Android WebView | Implemented first rendering slice | Android WebView JNI, native smoke library, Activity compile, signed APK package, APK artifact, emulator launch, screenshot artifact |

Latest heartbeat slice:

- Android WebView now has a first native JNI bridge API: attach an Activity-owned `WebView` to a `ui_window`, create a `webview` from that bridge, and call `loadUrl`, `loadDataWithBaseURL`, `evaluateJavascript`, and `destroy` through JNI.
- Android bridge messaging now has the native dispatch half: shared JavaScript recognizes `window.scppAndroid.postMessage(...)`, and Java/Kotlin activity code can forward that string to `webview_android_dispatch_bridge_message(...)` so it enters the same `webview_message` UI event queue as WebKitGTK, WKWebView, and WebView2.
- The Android NDK smoke source now references the attach/detach and dispatch bridge APIs so CI compile coverage protects the boundary. The Android activity-owned `@JavascriptInterface` object remains the Java/Kotlin-side packaging slice.
- Android WebView smoke APK packaging now signs and verifies a debug APK.
- CI uploads the signed smoke APK to `/tmp/scpp_ci_artifacts` with the GitHub run id in the filename, matching the screenshot artifact convention.
- Added an Android emulator render-smoke job that builds an x86_64 signed APK, launches the Activity, captures `android-webview-ui-${GITHUB_RUN_ID}.png`, and uploads it when the hosted runner can boot the emulator.
- Android emulator render smoke is bounded with CI timeouts and uploads `android-emulator-${GITHUB_RUN_ID}.log` when hosted emulator boot needs diagnosis.
- Android emulator render CI pins `ANDROID_AVD_HOME` so `avdmanager` and `emulator` resolve the same AVD directory on hosted runners.
- Android smoke manifest declares SDK 24/35 so direct `aapt2` packages install on current Android emulator images.
- CI run `28235130333` validated Android emulator launch, smoke app process/window presence, and a screenshot that renders the WebView content.
- Android render CI verifies the smoke app process/window before screenshot and uploads logcat/window diagnostics if the app exits.
- Android smoke APK packaging includes the NDK `libc++_shared.so` next to the smoke native library for both `arm64-v8a` and emulator `x86_64` APKs.
- Browser-like event bridge has its first backend-neutral boundary: `ui_event` can carry a WebView handle, message text, and URL payload, strict PHP++ exposes typed accessors, and WebView runtime code can enqueue events into the existing `ui_app` queue.
- Linux WebKitGTK now wires native load/title callbacks into that shared event queue, and the Linux WebView smoke app requires `webview_navigation_finished`.
- Apple WKWebView backends now wire `WKNavigationDelegate` navigation callbacks into that shared event queue, and the macOS WKWebView smoke app requires `webview_navigation_finished`.
- Windows WebView2 now wires `NavigationStarting`, `NavigationCompleted`, and `WebMessageReceived` callbacks into that shared event queue; Windows CI validates build, launch, and screenshot capture.
- Android WebView now emits `webview_ready` from native creation and the smoke Activity wires `WebViewClient` plus `JavascriptInterface` callbacks into native queue events.
- Linux WebKitGTK screenshot capture now retries until the rendered frame passes the screenshot shape check, avoiding single-frame black captures from hosted Xvfb/WebKit timing.
- Windows WebView2 CI now launches the smoke app, captures `windows-webview-ui-${GITHUB_RUN_ID}.png`, and uploads it from `/tmp/scpp_ci_artifacts`; CI run `28242929755` validated the path.
- WebView build reporting now carries structured dependency diagnostics and renders missing Linux `pkg-config`/WebKitGTK package guidance in `scpp explain-build`; CI run `28244035315` validated the slice.
- WebKitGTK and WKWebView now expose a `SimpleCpp` JavaScript message handler; Linux, macOS, iOS, Windows, and Android WebView smokes assert `webview_message` payload delivery. CI run `28245240915` validated the cross-platform message slice.
- Added an opt-in strict PHP++ `strict_webview_events` project sample that demonstrates `ui_app_poll`, `ui_event_type`, `ui_event_message`, and `ui_event_url` for browser-like WebView events. CI run `28246508869` validated the sample metadata/test slice.
- Windows WebView2 smoke now waits for the async `webview_ready` event before loading HTML and sends the JavaScript message probe after navigation completion. CI run `28247631339` validated the stabilized Windows launch plus the full current matrix.
- Windows WebView2 now emits `webview_title_changed` through the shared `ui_event` queue; CI run `28248575256` validated build, launch, screenshot, and title-event delivery.

## Done Definition

The WebView implementation is considered cross-platform complete for this backlog when all five platforms have:

- backend selection behind a platform-specific `SCPP_WEBVIEW_BACKEND_*` define
- runtime implementation for:
  - `webview_create`
  - `webview_load_url`
  - `webview_load_html`
  - `webview_eval`
  - `webview_close`
- clear build configuration for native libraries/frameworks
- a native smoke app or equivalent platform smoke target
- CI compile/link coverage
- CI launch/render coverage where the platform runner can support it
- screenshot artifact where practical
- clear diagnostics when a required platform dependency is missing

## Backlog

### 1. Windows 11 WebView2 Backend

Goal:

- Implement the Windows backend behind `SCPP_WEBVIEW_BACKEND_WEBVIEW2`.

Implementation tasks:

- Choose the first WebView2 SDK acquisition path for CI. Initial choice: restore `Microsoft.Web.WebView2` with NuGet in Windows CI.
- Add WebView2 native handle ownership to the private `webview` runtime implementation. Initial boundary added behind `SCPP_WEBVIEW_BACKEND_WEBVIEW2`.
- Parent the WebView2 controller inside the existing Win32 `ui_window`.
- Implement HTML load, URL load, JavaScript fire-and-forget eval, and close. Initial async creation path queues pending operations until WebView2 is ready.
- Keep Windows target limited to Windows 11 for now.

Testing tasks:

- Add `tests/native/webview_smoke_windows.cpp`. Initial smoke source added.
- Build/link in Windows CI. Initial CI step added.
- Add launch/screenshot artifact when desktop capture is stable enough on GitHub Actions. Initial launch/screenshot CI step added and green.

Open decision:

- Prefer package restore or checked-in minimal WebView2 loader boundary for CI? Initial answer: package restore via NuGet; revisit only if CI proves flaky or too slow.

### 2. iOS WKWebView Backend

Goal:

- Implement iOS WebView rendering behind `SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW`.

Implementation tasks:

- Expose or obtain the UIKit parent view from the existing `ui_window` runtime handle. Initial path uses `UIWindow.rootViewController.view`.
- Add Objective-C++ WKWebView creation for iOS. Initial branch added behind `SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW`.
- Match the same public helper behavior as macOS. Initial helpers cover HTML load, URL load, fire-and-forget eval, and close.
- Avoid desktop-only assumptions such as multiple independent top-level windows.

Testing tasks:

- Add an iOS WebView simulator smoke app. Initial smoke source and bundle plist added.
- Build, install, launch, screenshot, and validate image dimensions in macOS CI. Initial CI steps added and green.

Open decision:

- Whether iOS and macOS WKWebView code should share a small private helper or stay separate until the bridge grows. Initial answer: keep separate until event/message bridging adds meaningful shared behavior.

### 3. Android WebView Backend

Goal:

- Define and implement the Android WebView boundary without blocking native runtime builds.

Implementation tasks:

- Define the JNI/activity ownership boundary for `ui_window`. Initial attach/detach bridge added with `JavaVM`, Activity, and WebView global references.
- Decide which side creates the Android `WebView`: native request into Java/Kotlin activity code, or activity-owned view exposed to native. Initial answer: activity-owned `WebView` exposed to native.
- Add first native facade backend behind `SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW`. Initial compile boundary added.
- Implement enough JNI calls for HTML load, URL load, eval, and close. Initial direct JNI calls added for `loadUrl`, `loadDataWithBaseURL`, `evaluateJavascript`, and `destroy`.
- Keep lifecycle assumptions explicit: activity creation, pause/resume, destroy.

Testing tasks:

- Start with Android NDK compile smoke for the native boundary. Initial runtime and helper compile smoke added and green.
- Add Android app-side Activity compile smoke. Initial Activity-owned WebView fixture and `javac` CI step added.
- Add native JNI smoke library build. Initial `libsimplecpp_webview_smoke.so` CI build added.
- Add package smoke for the Android fixture. Initial direct SDK `aapt2`/`d8`/`zipalign` APK package step added, including the NDK C++ shared runtime needed by the native smoke library.
- Sign, verify, and upload the Android smoke APK. Initial debug signing and run-id artifact upload added.
- Add emulator render smoke only after the Android app packaging path exists. Emulator render job is green with app-active validation and screenshot artifact.

Open decision:

- Whether the first Android packaged smoke should use a minimal Gradle project or direct SDK tools under `tests/native/android_webview_smoke`.

### 4. Shared Build Reporting

Goal:

- Make implicit WebView build behavior visible and easy to diagnose.

Implementation tasks:

- Report that requesting `webview` auto-enables `ui`. Initial resolved config metadata records `implicit_modules["ui"] = "webview"`; `scpp explain-build` prints `ui (implicit via webview)`.
- Print selected backend in build output or build-report data. Initial WebView build spec reports backend names such as `webkitgtk`, `wkwebview`, `webview2`, `facade`, or `none`; `scpp explain-build` prints the active WebView backend.
- Print missing dependency diagnostics by platform. Initial Linux WebKitGTK missing-package diagnostics added to the WebView build spec and `scpp explain-build`.

Testing tasks:

- Add focused build-config tests for selected backend and missing dependency paths. Initial module test covers implicit `ui` reporting, WebView backend metadata, Linux missing dependency diagnostics, and explain-build output.
- Keep projects without `webview` unchanged.

### 5. Browser-Like Event Follow-Up

Goal:

- Prepare for modern browser-style events after all render backends exist.

Deferred event kinds:

- `webview_ready`
- `webview_message`
- `webview_navigation_started`
- `webview_navigation_finished`
- `webview_load_failed`
- `webview_title_changed`

Constraint:

- Events should flow through the existing `ui` event pump rather than adding a second application loop.

Initial implementation slice:

- `ui_event` carries `webview_handle`, `message`, and `url` payload fields.
- Strict PHP++ exposes `ui_event_type`, `ui_event_window`, `ui_event_webview`, `ui_event_message`, and `ui_event_url`.
- `webview_runtime::enqueue_event(...)` provides the backend callback handoff point into `ui_app.pending_events`.
- Linux WebKitGTK emits `webview_ready`, `webview_navigation_started`, `webview_navigation_finished`, `webview_load_failed`, `webview_title_changed`, and `webview_message`.
- Apple WKWebView emits `webview_ready`, `webview_navigation_started`, `webview_navigation_finished`, `webview_load_failed`, and `webview_message`.
- WebView2 emits `webview_ready`, `webview_navigation_started`, `webview_navigation_finished`, `webview_load_failed`, `webview_title_changed`, and `webview_message`.
- Android emits `webview_ready`, `webview_navigation_started`, `webview_navigation_finished`, `webview_load_failed`, and `webview_message` through the Java-hosted `WebViewClient`/`JavascriptInterface` bridge contract.
- Strict PHP++ source-facing example added under `docs/examples/php/strict/project_samples/strict_webview_events`; it remains outside the default console sample runner because it requires a GUI/WebView backend.

## Suggested Implementation Order

1. Windows 11 WebView2 compile/link backend.
2. Windows local HTML smoke app.
3. iOS WKWebView backend and simulator screenshot.
4. Android JNI boundary compile smoke.
5. Android rendering smoke after packaging exists. Initial CI job added.
6. Build reporting for implicit `ui` enablement and selected WebView backend. Initial explain-build slice added.
7. Browser-like event bridge.

## Latest Known Good CI Run

Run `28248575256` validated:

- Linux WebKitGTK WebView screenshot retry and `webview_navigation_finished` event delivery through the `ui_app` queue
- macOS WKWebView screenshot and `webview_navigation_finished` event delivery through the `ui_app` queue
- Windows WebView2 build, launch, screenshot artifact, and `webview_message` payload delivery through the `ui_app` queue
- iOS UIKit UI simulator screenshot
- iOS WKWebView simulator screenshot
- Android NDK UI compile smoke
- Android WebView JNI boundary compile smoke
- Android WebView native smoke library build
- Android WebView Activity compile smoke
- Android WebView APK package smoke with packaged `libc++_shared.so`
- Android WebView signed APK artifact
- Android WebView emulator launch, app-active validation, event bridge assertion, and screenshot artifact rendering "Simple C++ WebView"
- Shared WebView backend metadata
- Shared explain-build backend reporting
- Linux WebView missing dependency diagnostics in the WebView build spec and explain-build rendering
- Linux WebKitGTK, macOS WKWebView, and iOS WKWebView JavaScript-to-native `webview_message` payload delivery
- Strict PHP++ opt-in WebView event sample metadata and source-facing event accessor coverage
- Windows WebView2 async readiness before load, navigation-finished delivery, JavaScript-to-native `webview_message`, launch, and screenshot artifact
- Windows WebView2 `webview_title_changed` delivery through the `ui_app` queue
