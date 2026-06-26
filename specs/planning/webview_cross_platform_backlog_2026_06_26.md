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
| Windows 11 | WebView2 | Initial backend boundary implemented | WebView2 compile/link smoke |
| iOS | WKWebView | Implemented first rendering slice | Build, simulator launch, screenshot artifact |
| Android | Android WebView | Native boundary in progress | Android WebView JNI compile smoke |

Latest heartbeat slice:

- `scpp explain-build` now reports selected runtime modules, including implicit module enablement such as `ui (implicit via webview)`.
- `scpp explain-build` now reports the selected WebView backend when the `webview` module is active.
- The saved `build_explanation` data carries this runtime-module report for build tooling and diagnostics.

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
- Add launch/screenshot artifact when desktop capture is stable enough on GitHub Actions.

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

- Define the JNI/activity ownership boundary for `ui_window`. Initial native state placeholder added with `JavaVM`, activity, and WebView object slots.
- Decide which side creates the Android `WebView`: native request into Java/Kotlin activity code, or activity-owned view exposed to native.
- Add first native facade backend behind `SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW`. Initial compile boundary added.
- Implement enough JNI calls for HTML load, URL load, eval, and close.
- Keep lifecycle assumptions explicit: activity creation, pause/resume, destroy.

Testing tasks:

- Start with Android NDK compile smoke for the native boundary. Initial runtime and helper compile smoke added and green.
- Add emulator render smoke only after the Android app packaging path exists.

Open decision:

- Whether the first Android smoke belongs in the existing native test layout or needs a minimal Gradle project under `tests/native/android_webview_smoke`.

### 4. Shared Build Reporting

Goal:

- Make implicit WebView build behavior visible and easy to diagnose.

Implementation tasks:

- Report that requesting `webview` auto-enables `ui`. Initial resolved config metadata records `implicit_modules["ui"] = "webview"`; `scpp explain-build` prints `ui (implicit via webview)`.
- Print selected backend in build output or build-report data. Initial WebView build spec reports backend names such as `webkitgtk`, `wkwebview`, `webview2`, `facade`, or `none`; `scpp explain-build` prints the active WebView backend.
- Print missing dependency diagnostics by platform.

Testing tasks:

- Add focused build-config tests for selected backend and missing dependency paths. Initial module test covers implicit `ui` reporting, WebView backend metadata, and explain-build output.
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

## Suggested Implementation Order

1. Windows 11 WebView2 compile/link backend.
2. Windows local HTML smoke app.
3. iOS WKWebView backend and simulator screenshot.
4. Android JNI boundary compile smoke.
5. Android rendering smoke after packaging exists.
6. Build reporting for implicit `ui` enablement and selected WebView backend. Initial explain-build slice added.
7. Browser-like event bridge.

## Latest Known Good CI Run

Run `28226809487` validated:

- Linux WebKitGTK WebView screenshot
- macOS WKWebView screenshot
- Windows WebView2 compile/link
- iOS UIKit UI simulator screenshot
- iOS WKWebView simulator screenshot
- Android NDK UI compile smoke
- Android WebView JNI boundary compile smoke
- Shared WebView backend metadata
