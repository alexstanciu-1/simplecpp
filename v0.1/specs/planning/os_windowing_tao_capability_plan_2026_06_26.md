# Simple C++ OS Windowing Capability Plan
Doc Status: planning

Date: 2026-06-26

## Purpose

Capture the first direction for a cross-platform application windowing layer for Simple C++ / PHP++ strict projects.

This note is planning guidance only. It does not define current language semantics, runtime contracts, or build behavior.

## Upstream Capability Snapshot

The Tauri ecosystem splits the concern into distinct pieces:

- `tao`: cross-platform window creation and event-loop management.
- `muda`: desktop menu utilities.
- `tray-icon`: desktop system tray icons, with `muda` used for tray context menus.
- `wry`: WebView rendering on top of a window/event-loop backend such as Tao.
- `tauri`: higher-level application framework that wraps the above and adds packaging, command IPC, permissions, plugins, and app lifecycle.

Tao is the best upstream reference/backend candidate for our first objective: native window creation and event loop behavior across Windows, macOS, Linux, iOS, and Android.

Tao should not be treated as the sole upstream for menus or tray icons. Current Tauri v2 exposes menu support through its menu layer backed by `muda`, and tray support through its tray layer backed by `tray-icon`.

Tauri/Tao naming should not be copied into the Simple C++ public surface. Upstream names are implementation research vocabulary only. Public names should follow the current Simple C++ strict runtime-module style: explicit module families, stable handle/value types, and family-prefixed helper names.

## Recommended First Slice

Create a new native runtime module. Preferred module name for now:

- `ui`

Reasoning:

- It is short and domain-level rather than backend-level.
- It leaves room for windows, menus, tray, and later rendering without naming the whole module after one sub-capability.
- It aligns with existing strict subsystem-family style such as `fs_*`, `io_*`, `json_*`, `dt_*`, and `regex_*`.

Use three internal sub-areas:

1. `window`
   - application event loop
   - window creation
   - title, size, min/max size, resizable, visible, decorations
   - show, hide, focus, close request handling
   - redraw/request-redraw callback shape
   - basic monitor/scale factor queries

2. `menu`
   - desktop-only root menu/menu bar
   - submenu
   - text item
   - checkbox item
   - separator
   - enabled/disabled state
   - item activated events
   - basic accelerator representation, after the first event loop slice is stable

3. `tray`
   - desktop-only tray icon
   - tooltip
   - optional context menu
   - click events
   - update/remove tray icon

## Platform Scope

Windowing:

- Target all five requested platforms: Windows, macOS, Linux, iOS, Android.
- Treat mobile as a constrained window/lifecycle target rather than a desktop-window clone.
- Do not promise multiple independent top-level mobile windows in the first slice.

Menu:

- Target desktop only in the first slice: Windows, macOS, Linux.
- Do not expose menu APIs as universally available on iOS/Android unless a later mobile-specific spec defines their behavior.

Tray:

- Target desktop only in the first slice: Windows, macOS, Linux.
- Do not expose tray APIs on iOS/Android.

Linux:

- Expect GTK/AppIndicator style dependencies for menu/tray behavior.
- Keep dependency diagnostics explicit in `scpp build` rather than allowing link errors to be the first user-facing signal.

## Proposed PHP++ Strict Surface

Use family-prefixed strict-native helper names. Do not copy Tao/Tauri names.

First window slice:

```php
$app ui_app = ui_app_create();
$window ui_window = ui_window_create($app, "Hello", 900, 600);

ui_window_show($window);
ui_app_run($app);
```

Later menu/tray sketch:

```php
$menu ui_menu = ui_menu_create();
$file ui_menu = ui_menu_submenu($menu, "File");
$quit_id string = ui_menu_item($file, "Quit");

ui_app_set_menu($app, $menu);

$tray ui_tray = ui_tray_create($app, "App", "assets/icon.png");
ui_tray_set_menu($tray, $menu);

ui_app_run($app);
```

The exact types and return carriers should be specified before implementation. Prefer explicit handle types and `result<T>`-shaped creation failures for strict projects.

## Backend Direction

Preferred architecture:

- Define a Simple C++ C++ runtime facade first.
- Hide the backend choice behind that facade.
- Use Tao as the primary reference/backend candidate for window/event-loop semantics, not as the public naming model.
- Use `muda` as the reference/backend candidate for desktop menus.
- Use `tray-icon` as the reference/backend candidate for desktop tray icons.
- Keep WebView (`wry`) out of the first objective unless the product goal changes from native window creation to webview app shell.

This keeps the public Simple C++ surface stable if the backend mix changes later.

## Build-System Implications

This is not just a header-only runtime addition.

The build needs a way to:

- opt into the windowing module from `prism.json`
- compile/link platform-specific native sources
- declare platform packages/frameworks/libraries
- emit clear missing-dependency diagnostics, especially on Linux
- distinguish desktop-only module availability from mobile availability

The module should be disabled by default and enabled explicitly, for example:

```json
{
  "runtime": {
    "modules": ["ui"]
  }
}
```

## Testing Strategy

Use a layered test strategy instead of trying to make every platform run the full GUI matrix on day one.

### Test levels

1. Compile/link checks
   - prove the `ui` module builds on the target operating system
   - verify platform dependencies are detected cleanly
   - run in ordinary CI where possible

2. Headless smoke checks
   - create an app handle
   - create one hidden/minimized test window
   - pump the event loop briefly
   - request close/exit
   - assert no native crash and a clean exit

3. Interactive/manual checks
   - visible window appears
   - close button works
   - focus/show/hide behavior is sane
   - menu/tray behavior is inspected by a human or screenshot/video tool

4. Real-device checks
   - mobile lifecycle works on Android/iOS
   - app starts, receives lifecycle events, exits/suspends cleanly
   - native packaging/signing path is not broken

### Platform plan

Windows:

- Use local Windows for early manual validation.
- Use GitHub Actions Windows runners for compile/link and smoke tests.

Linux:

- Use native Linux CI as the primary Linux proof.
- WSL/WSLg is useful for local developer reproduction, but should not be the only Linux signal because it is not a normal desktop/session environment.
- For CI smoke tests, use a virtual display/session such as Xvfb for the first window-create tests.
- Menu and tray tests on Linux need extra care because desktop shell, GTK, DBus, and AppIndicator behavior vary by environment.

macOS:

- Use GitHub Actions macOS runners for first compile/link and smoke tests.
- For deeper manual testing, prefer either a real Mac or a hosted Mac CI/device provider.
- Do not plan around macOS-in-VirtualBox on ordinary non-Apple hardware as a project testing path.

Android:

- Start with Android cross-compile/build checks.
- Add emulator/device smoke once packaging/lifecycle exists.
- For paid real-device runs, evaluate Firebase Test Lab or AWS Device Farm before larger platforms.

iOS:

- Requires macOS/Xcode infrastructure for build/sign/simulator work.
- Start with macOS CI simulator builds once the mobile target exists.
- Real-device iOS validation likely needs either a physical device attached to a Mac, a Mac mini runner, or a mobile CI provider.

### Cost posture

Prefer this order:

1. Local machines and GitHub Actions for desktop compile/smoke.
2. GitHub Actions macOS for modest macOS coverage.
3. Firebase Test Lab or AWS Device Farm for Android real-device confidence.
4. Hosted Mac/mobile CI only when iOS packaging and real-device lifecycle work becomes active.
5. A physical Mac mini is probably cheaper long-term than heavy hosted macOS usage if this module becomes a core product area.

## Open Design Questions

- Is `ui` the right public runtime module name, or should the module be more explicit, such as `native_ui`?
- Should callbacks be source-language closures in the first slice, or should the first slice expose polling/event IDs?
- Should mobile support be included in the first implementation milestone, or only designed in the public API while desktop lands first?
- Should the first rendering target be blank native windows only, or should we immediately pair with a canvas/webview/story for visible content?
- How should generated C++ own the long-lived event loop when PHP++ source looks sequential?

## Suggested Milestones

1. Write a top-level planning/spec draft for app/window semantics and platform availability.
2. Add project config/module selection for `ui`.
3. Build a minimal native C++ proof-of-concept outside PHP++ lowering: create a window, receive close event, exit.
4. Add strict PHP++ symbols for app/window handles and first creation/run calls.
5. Add menu support for desktop.
6. Add tray support for desktop.
7. Revisit mobile lifecycle and packaging once the desktop event-loop contract is proven.
