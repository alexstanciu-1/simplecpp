# UI Runtime Module First Slice
Doc Status: planning

Date: 2026-06-26

## Purpose

Lock the first implementable slice for the Simple C++ `ui` runtime module.

This note is planning guidance only. It does not define current language semantics, runtime contracts, or build behavior. It narrows the first implementation milestone so work can start without copying Tauri/Tao naming or adopting a foreign framework contract.

## Relationship To Tauri/Tao

Tauri/Tao is reference material only.

Use it for:

- know-how
- capability research
- architecture inspiration
- examples of what cross-platform windowing needs to handle

Do not use it for:

- public Simple C++ naming
- source-level API shape
- semantic authority
- mandatory backend choice
- Rust framework integration

The first implementation should be a Simple C++ runtime feature with its own C++ facade and platform backends.

## Locked First-Slice Decisions

1. Runtime module name:
   - `ui`

2. Public helper family:
   - `ui_*`

3. First milestone scope:
   - desktop windowing only
   - one app/session handle
   - create one or more windows
   - show/hide windows
   - poll events
   - close and exit cleanly

4. Explicitly out of first slice:
   - menu bar
   - system tray
   - WebView
   - custom drawing/rendering
   - mobile packaging/lifecycle
   - source-level async callbacks
   - source-level closure event listeners

5. Backend direction:
   - thin native C++ backend per platform
   - no Rust/Tao binding in the first slice
   - Tauri/Tao remains inspiration and comparison material only

6. Creation failure shape:
   - use `result<T>` now
   - source examples should use `take(...)` at creation boundaries

7. Polling behavior:
   - `ui_app_poll(...)` is non-blocking in the first public contract
   - async/await may be layered later on top of the same event queue

8. Close ownership:
   - receiving `window_close` does not automatically destroy the window
   - source/runtime code must explicitly close or keep the window

## Platform Scope

First implementation target:

- Windows
- Linux
- macOS

Deferred target:

- Android
- iOS

The public design should avoid blocking mobile later, but first implementation and validation are desktop-only.

## Public Strict PHP++ Surface

First-slice example:

```php
$app ui_app = take(ui_app_create());
$window ui_window = take(ui_window_create($app, "Hello", 900, 600));

ui_window_show($window);

while (ui_app_poll($app)) {
	$event ui_event = ui_app_next_event($app);

	if (ui_event_type($event) === "window_close") {
		ui_window_close(ui_event_window($event));
		ui_app_exit($app);
	}
}
```

Convenience run-loop helper may be added only after the polling path works:

```php
ui_app_run($app);
```

`ui_app_run($app)` should be a convenience over the same event semantics. It must not become the only way to drive the event loop because later browser-like event handling needs an explicit event surface.

## Handle Types

First-slice source-visible handle/value types:

- `ui_app`
- `ui_window`
- `ui_event`

These are runtime-owned opaque handles or value wrappers. Source code must not depend on backend-specific platform handles.

Future types:

- `ui_menu`
- `ui_tray`
- `ui_timer`
- `ui_monitor`

## Creation And Failure Shape

Use `result<T>` for first public creation helpers:

```php
$app ui_app = take(ui_app_create());
$window ui_window = take(ui_window_create($app, "Hello", 900, 600));
```

Where:

- `ui_app_create()` returns a `result<ui_app>`-shaped value.
- `ui_window_create(...)` returns a `result<ui_window>`-shaped value.
- creation failures remain explicit and inspectable.
- a runtime exception is still acceptable for internal invariant failures, but ordinary creation failure should be represented by the result carrier.

## Event Model

First slice uses event polling.

Reason:

- It avoids source-level callback and closure integration work before the native window layer is proven.
- It can be tested deterministically in headless smoke tests.
- It keeps ownership/lifetime easier while the platform backends are still new.

Modern/browser-like direction:

- Event names should be stable strings or enum-like constants that resemble browser event concepts where appropriate.
- Event objects should carry typed accessors rather than backend-specific payloads.
- Later callback APIs should be layered on top of the same event vocabulary, not a second event model.
- Later async/await APIs should also use this event vocabulary. The non-blocking poll loop should be the mechanical base that an async event wait can resume from.

Initial event kinds:

- `app_ready`
- `window_close`
- `window_resized`
- `window_focused`
- `window_blurred`
- `window_redraw`

First implementation only needs to prove:

- `app_ready`
- `window_close`

Other event kinds may be reserved but not emitted until implemented.

## First Public Helper Set

Minimum first implementation:

```text
ui_app_create(): result<ui_app>
ui_app_poll(ui_app $app): bool
ui_app_next_event(ui_app $app): ui_event
ui_app_exit(ui_app $app): void

ui_window_create(ui_app $app, string $title, int $width, int $height): result<ui_window>
ui_window_show(ui_window $window): void
ui_window_hide(ui_window $window): void
ui_window_close(ui_window $window): void
ui_window_set_title(ui_window $window, string $title): void

ui_event_type(ui_event $event): string
ui_event_window(ui_event $event): ui_window
```

Later but not first:

```text
ui_app_run(ui_app $app): void
ui_app_wait_event(ui_app $app): async ui_event
ui_window_set_size(...)
ui_window_request_redraw(...)
ui_event_width(...)
ui_event_height(...)
ui_event_key(...)
ui_event_pointer_x(...)
ui_event_pointer_y(...)
```

## Native Backend Shape

Use a runtime facade plus platform implementations.

Suggested C++ layout:

```text
runtime/include/modules/ui/
  ui.hpp
  ui_types.hpp
  ui_events.hpp
  ui_backend.hpp

runtime/src/modules/ui/
  ui.cpp
  ui_backend_windows.cpp
  ui_backend_linux.cpp
  ui_backend_macos.mm
```

Notes:

- macOS backend likely needs Objective-C++ (`.mm`) for Cocoa/AppKit integration.
- Windows backend should use Win32 directly for first slice.
- Linux backend should use GTK first.
- GTK is the practical first Linux target because it covers the GNOME/GTK path common on Ubuntu, Debian, Fedora, and many derivative desktops; Linux Mint is also Ubuntu/Debian-family and ships GTK-oriented desktops. KDE/Qt and gaming-focused Arch/SteamOS/Bazzite-style environments matter, but the first backend should stay hidden behind the facade and can be broadened later.
- Backend-specific native handles remain private to runtime implementation.

## Build-System Requirements

`ui` must be opt-in:

```json
{
  "runtime": {
    "modules": ["ui"]
  }
}
```

`scpp build` must eventually:

- include `ui` runtime sources only when the module is enabled
- link platform libraries/frameworks
- fail clearly when required platform dependencies are missing
- keep projects without `ui` unaffected

Initial platform link expectations:

- Windows: Win32 user/windowing libraries
- macOS: Cocoa/AppKit frameworks
- Linux: GTK for first slice

## Testing Requirements

First-slice tests:

1. Module-off build:
   - normal strict project still builds without `ui`

2. Module-on compile/link:
   - empty strict project with `"modules": ["ui"]` links the runtime module

3. Hidden-window smoke:
   - create `ui_app`
   - create hidden or briefly visible `ui_window`
   - poll events briefly
   - trigger close/exit
   - process exits cleanly

4. Manual visible-window smoke:
   - window appears
   - title is correct
   - close button produces `window_close`
   - app exits cleanly

CI target order:

1. Linux with virtual display/session.
2. Windows GitHub Actions runner.
3. macOS GitHub Actions runner.

Local manual target order:

1. Windows.
2. Linux/WSLg or native Linux.
3. macOS when hardware or hosted runner access is available.

## Implementation Milestones

1. Add planning/spec docs for `ui` first slice.
2. Add module selection plumbing for `ui` without any public helpers.
3. Add native C++ proof-of-concept under runtime module boundaries.
4. Add strict symbol registry entries for first helper set.
5. Add PHP++ smoke sample.
6. Add focused build tests for module-on/module-off behavior.
7. Add platform smoke tests where CI allows window creation.

## Open Questions To Resolve During Implementation

- Can the current strict registry expose `result<ui_app>` and `result<ui_window>` cleanly for opaque handle types?
- What should `ui_app_next_event($app)` do when no event is available: return a sentinel event, throw, or require a preceding successful poll?
