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
| Windows 11 | WebView2 | Not implemented | UI Win32 compile/link only |
| iOS | WKWebView | Not implemented | UIKit UI simulator compile/run only |
| Android | Android WebView | Not implemented | Android NDK UI-disabled compile smoke only |

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

- Choose the first WebView2 SDK acquisition path for CI.
- Add WebView2 native handle ownership to the private `webview` runtime implementation.
- Parent the WebView2 controller inside the existing Win32 `ui_window`.
- Implement HTML load, URL load, JavaScript fire-and-forget eval, and close.
- Keep Windows target limited to Windows 11 for now.

Testing tasks:

- Add `tests/native/webview_smoke_windows.cpp`.
- Build/link in Windows CI.
- Add launch/screenshot artifact when desktop capture is stable enough on GitHub Actions.

Open decision:

- Prefer package restore or checked-in minimal WebView2 loader boundary for CI?

### 2. iOS WKWebView Backend

Goal:

- Implement iOS WebView rendering behind `SCPP_WEBVIEW_BACKEND_UIKIT_WKWEBVIEW`.

Implementation tasks:

- Expose or obtain the UIKit parent view from the existing `ui_window` runtime handle.
- Add Objective-C++ WKWebView creation for iOS.
- Match the same public helper behavior as macOS.
- Avoid desktop-only assumptions such as multiple independent top-level windows.

Testing tasks:

- Add an iOS WebView simulator smoke app.
- Build, install, launch, screenshot, and validate image dimensions in macOS CI.

Open decision:

- Whether iOS and macOS WKWebView code should share a small private helper or stay separate until the bridge grows.

### 3. Android WebView Backend

Goal:

- Define and implement the Android WebView boundary without blocking native runtime builds.

Implementation tasks:

- Define the JNI/activity ownership boundary for `ui_window`.
- Decide which side creates the Android `WebView`: native request into Java/Kotlin activity code, or activity-owned view exposed to native.
- Add first native facade backend behind `SCPP_WEBVIEW_BACKEND_ANDROID_WEBVIEW`.
- Implement enough JNI calls for HTML load, URL load, eval, and close.
- Keep lifecycle assumptions explicit: activity creation, pause/resume, destroy.

Testing tasks:

- Start with Android NDK compile smoke for the native boundary.
- Add emulator render smoke only after the Android app packaging path exists.

Open decision:

- Whether the first Android smoke belongs in the existing native test layout or needs a minimal Gradle project under `tests/native/android_webview_smoke`.

### 4. Shared Build Reporting

Goal:

- Make implicit WebView build behavior visible and easy to diagnose.

Implementation tasks:

- Report that requesting `webview` auto-enables `ui`.
- Print selected backend in build output or build-report data.
- Print missing dependency diagnostics by platform.

Testing tasks:

- Add focused build-config tests for selected backend and missing dependency paths.
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
6. Build reporting for implicit `ui` enablement and selected WebView backend.
7. Browser-like event bridge.

## Latest Known Good CI Run

Run `28223199903` validated:

- Linux WebKitGTK WebView screenshot
- macOS WKWebView screenshot
- Windows Win32 UI compile/link
- iOS UIKit UI simulator screenshot
- Android NDK UI compile smoke
