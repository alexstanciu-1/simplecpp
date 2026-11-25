# SimpleC++ Debug Code Layout
Doc Status: planning

Date: 2026-05-26

Purpose:

- define where the new debug feature code should live before implementation starts
- keep `scpp debug`, generator instrumentation, runtime helpers, and VS Code integration separated by ownership
- avoid spreading early debug code across unrelated files and folders

This note is planning guidance, not semantic authority.

## Why This Exists

The debug feature will likely touch:

- CLI command handling
- STAN-backed resolution
- generator instrumentation
- runtime helper support
- VS Code integration

If those pieces are added opportunistically into existing large files, the feature will become harder to reason about, test, and evolve.

So before implementation, the project should choose a dedicated location plan and treat it as a guardrail.

## Main Design Rule

The debug feature should be organized by owning layer, not by convenience of the first patch.

Current preferred rule:

- command and plan orchestration live under `bin/`
- generator instrumentation lives under `generators/php/src/`
- runtime structured debug helpers live under `runtime/include/`
- VS Code integration lives under `tools/vscode_phs_extension/extension/src/`
- large existing entry files should dispatch into debug-specific code instead of absorbing the full implementation

## Primary Layout

### 1. CLI / command-side code

Preferred home:

- `bin/debug/`

Recommended initial files:

- `bin/debug/debug_command.php`
- `bin/debug/debug_plan.php`
- `bin/debug/debug_plan_normalizer.php`
- `bin/debug/debug_session_io.php`
- `bin/debug/debug_output.php`
- `bin/debug/debug_event_stream.php`
- `bin/debug/debug_stan_resolution.php`

Responsibilities:

- parse or receive debug command options
- normalize options into the `DebugPlan`
- coordinate STAN-backed resolution
- load/save session artifacts
- launch the debug build/run path
- render or stream debug events

Boundary rule:

- `bin/project_services.php` should only route `scpp debug` into this subtree and keep top-level command registration/help text
- the main debug implementation should not be written inline inside `bin/project_services.php`
- only simple explicit source-site actions that target `file:line` and inject a helper `before` or `after` that site may produce narrow rewritten-source overrides from this subtree
- non-source-targeted features must not be implemented by rewriting every project source file from this subtree

### 2. Generator-side instrumentation

Preferred home:

- `generators/php/src/Debug/`

Recommended initial files:

- `generators/php/src/Debug/DebugPlan.php`
- `generators/php/src/Debug/DebugInstrumentationRequest.php`
- `generators/php/src/Debug/DebugHarnessEmitter.php`
- `generators/php/src/Debug/DebugLineHookEmitter.php`
- `generators/php/src/Debug/DebugCallableHarnessEmitter.php`

Responsibilities:

- consume a normalized debug instrumentation request
- emit temporary debug harness code
- inject source-aware hooks
- preserve source-mapping continuity for debug variants
- keep ordinary non-debug generation paths separate

Boundary rule:

- generator debug code should consume normalized request objects, not parse raw CLI flags or VS Code-specific payloads

### 3. Runtime debug helpers

Preferred home:

- `runtime/include/scpp/debug/`

Recommended initial files:

- `runtime/include/scpp/debug/event.hpp`
- `runtime/include/scpp/debug/emitter.hpp`
- `runtime/include/scpp/debug/value_dump.hpp`
- `runtime/include/scpp/debug/session.hpp`

Responsibilities:

- define structured debug event payload support
- emit session/debug events from instrumented code
- serialize basic value/type/shape observations
- keep runtime event support isolated from unrelated operators/helpers

Boundary rule:

- new structured debug session/event helpers should not be mixed directly into unrelated operator folders unless a specific operator-owned hook is truly needed

### 4. VS Code integration

Preferred home:

- `tools/vscode_phs_extension/extension/src/debug/`

Recommended initial files:

- `tools/vscode_phs_extension/extension/src/debug/commands.js`
- `tools/vscode_phs_extension/extension/src/debug/scpp_debug_runner.js`
- `tools/vscode_phs_extension/extension/src/debug/event_parser.js`
- `tools/vscode_phs_extension/extension/src/debug/output_view.js`

Responsibilities:

- register debug-related editor commands
- build and launch `scpp debug` invocations
- parse structured debug output
- present results in VS Code surfaces

Boundary rule:

- `extension.js` should stay the activation/registration entrypoint and delegate debug work into `src/debug/`

## Routing and Entry Files

The existing entry files should remain thin.

### `bin/scpp.php`

Should continue to:

- bootstrap
- include project services
- call `main($argv)`

It should not become the implementation home for the debug feature.

### `bin/project_services.php`

Should continue to:

- own top-level command routing
- print help text
- delegate to command handlers

For debug specifically, preferred behavior is:

- recognize `scpp debug`
- call one small debug entry handler from `bin/debug/`

It should not absorb the full debug plan builder, session serializer, output renderer, and instrumentation coordinator inline.

## Ownership Summary

Preferred ownership map:

- `bin/debug/`: CLI-facing debug orchestration
- `generators/php/src/Debug/`: generator instrumentation
- `runtime/include/scpp/debug/`: runtime structured event support
- `tools/vscode_phs_extension/extension/src/debug/`: editor integration

This keeps the feature aligned with the repository’s “work by owning layer” rule.

## Naming Guidance

Prefer names that say `debug` explicitly in this feature subtree.

Why:

- the feature is cross-layer and easy to lose in generic names like `support`, `helpers`, or `misc`
- explicit naming makes ownership and searchability much better

Examples:

- prefer `DebugPlan` over a vague `Plan`
- prefer `debug_event_stream.php` over a vague `events.php`
- prefer `scpp/debug/emitter.hpp` over a generic `support/emitter.hpp`

## Anti-Sprawl Rules

The following should be treated as preferred constraints during implementation:

1. do not put the main debug implementation directly into `bin/project_services.php`
2. do not put generator debug code into generic `Support/` if it represents instrumentation ownership
3. do not put runtime structured debug-session helpers into unrelated operator folders by default
4. do not add substantial VS Code debug behavior directly to `extension.js`
5. do not let editor-specific concerns leak into generator/runtime contracts
6. do not let CLI flag parsing become the generator’s responsibility

These are design hygiene rules, not proof that exceptions are impossible, but the default should be to preserve separation.

## Phase 1 Recommendation

Before behavior-rich implementation starts, the project should create at least:

- `bin/debug/`
- `generators/php/src/Debug/`
- `runtime/include/scpp/debug/`

The VS Code subtree can be added when editor command work starts, but the path should be reserved conceptually from the beginning:

- `tools/vscode_phs_extension/extension/src/debug/`

## Relationship To Existing Debug Helpers

Existing source-level debug helpers such as `dbg(...)` already have their own implementation path and should not be treated as the required home for the new structured `scpp debug` session machinery.

Current recommendation:

- reuse existing inspection logic where that helps
- keep the new session/event architecture in its own debug-specific locations

This avoids conflating:

- source-authored inline debug calls
- CLI-driven structured debug sessions

## Non-Goals

- this note does not define the `DebugPlan` schema
- this note does not define the debug event schema
- this note does not require every suggested file to exist immediately
- this note does not freeze final class/function names permanently

## Current Recommended Next Step

Use this layout note as the placement constraint for the first implementation slice:

1. add `scpp debug` command dispatch into `bin/debug/`
2. keep the first plan/session/output objects under `bin/debug/`
3. add generator/runtime debug subtrees only when their first concrete hooks land

That preserves a clean feature home from the first code patch onward.
