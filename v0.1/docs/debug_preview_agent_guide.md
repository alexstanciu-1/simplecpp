# Simple C++ Debug Preview Agent Guide
Doc Status: supporting

Date: 2026-05-27

Purpose:

- give an AI agent a compact release note and testing guide for the current debug preview
- describe the intended testing surface without over-claiming unfinished debugger behavior
- record one focused release-readiness validation matrix

This document is operational guidance, not semantic authority.

## Release Framing

This release should be treated as a **debug preview**.

The current implementation is strong enough for agent-driven testing and feedback in these areas:

- source-targeted debug actions
- structured debug events
- debug slot artifacts
- early callable execution through `--call`
- early expression execution through `--exec`
- VS Code debug artifact inspection helpers
- minimal DAP preview wiring

This release should **not** yet be framed as a finished debugger experience.

## What To Test

### Source-targeted actions

- `--break=file:line`
- `--exit=file:line`
- `--dump-before=file:line:expr`
- `--dump-after=file:line:expr`

Current design rules:

- these actions identify the injection site by `file:line`
- dump expressions are raw injected source text
- multiple dump actions are allowed
- event source locations refer to the rewritten debug source surface for that run

### Callable execution

- `--call=<callable>`
- `--call-args=<json-array>`
- `--call-this=<json>`

Current callable target shapes:

- top-level functions
- static methods via `ClassName::method`
- instance methods via `ClassName::method --call-this=<json>`

Current important boundary:

- instance receivers only work when the receiver type has a real `from_json<T>` conversion path
- otherwise the failure is expected to surface clearly during build/runtime conversion

### Expression execution

- `--exec=<expr>`

Current design rules:

- expression only
- runs through a dedicated harness path
- runs inside the project and benefits from project composition/includes
- does not use project-wide source rewrite

### VS Code preview surface

Current extension support worth testing:

- `Simple C++: Inspect Latest Debug Session`
- `Simple C++: Inspect Debug Slots`
- saved `events.json`
- saved `plan.json`
- saved rewritten source
- saved rewritten-source line map

Current DAP preview:

- debug type: `simplecpp-debug`
- scope: launch, breakpoints, stop events, restart, very light variables from saved event payloads
- treat this as preview infrastructure, not a finished debugger UI

## CLI Quick Reference

Current debug-preview commands of interest:

```bash
scpp debug --break=main.phs:2
scpp debug --exit=main.phs:2
scpp debug --dump-before='main.phs:2:$name . "!"'
scpp debug --dump-after='main.phs:2:$name . "?"'
scpp debug --call=greet --call-args='["Ana",[true,false]]'
scpp debug --call='DemoGreeter::shout' --call-args='["Ana"]'
scpp debug --call='DemoGreeter::suffix' --call-this='{"name":"unused"}' --call-args='["Ana"]'
scpp debug --exec='1 + 2'
```

## Known Preview Limits

- dump export is still shallow (`type` + `preview`), not a rich recursive value tree yet
- instance-method receiver hydration is only available when `from_json<T>` exists for the receiver type
- DAP support is scaffolded but not yet deeply battle-tested in an Extension Development Host session
- structured events are much stronger than the interactive debugger model at this stage

## Validation Matrix

The following checks were run on 2026-05-27.

### `--break`

Command:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime --break=main.phs:2
```

Workspace:

- `/tmp/scpp_debug_release_matrix`

Result:

- passed
- emitted `hit`
- emitted `break`
- summary status: `stopped`

### `--exit`

Command:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime --exit=main.phs:2
```

Workspace:

- `/tmp/scpp_debug_release_matrix`

Result:

- passed
- emitted `hit`
- emitted `exit`
- summary status: `exited`

### `--dump-before`

Command:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime '--dump-before=main.phs:2:$name . "!"'
```

Workspace:

- `/tmp/scpp_debug_release_matrix`

Result:

- passed
- emitted `dump`
- subject text preserved as raw injected expression text
- preview value: `world!`

### `--dump-after`

Command:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime '--dump-after=main.phs:2:$name . "?"'
```

Workspace:

- `/tmp/scpp_debug_release_matrix`

Result:

- passed
- emitted `dump`
- phase: `after`
- event source line reflected rewritten-source positioning (`line: 3` in this run)
- preview value: `world?`

### `--call` success

Commands:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime --call=greet '--call-args=["Ana",[true,false]]'
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime '--call=DemoGreeter::shout' '--call-args=["Ana"]'
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime --no-stan '--exec=1 + 2'
```

Workspace:

- `/tmp/scpp_debug_call_cases`

Result:

- passed for top-level function call
- passed for static method call
- passed for `--exec`
- `--exec` emitted a structured dump preview `3`

### `--call` failure

Command:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php debug --format=json --build-runtime '--call=DemoGreeter::suffix' '--call-this={"name":"unused"}' '--call-args=["Ana"]'
```

Workspace:

- `/tmp/scpp_debug_call_cases`

Result:

- expected failure
- build failed because `from_json<shared_p<DemoGreeter>>` is not defined
- this is acceptable for the current preview and is a good target for agent feedback

### Slot inspection in the extension

Validation:

- store helpers successfully read `.prism/debug/index.json`
- slot listing surfaced populated slots with events/plan/source manifest availability
- latest-slot lookup succeeded

Representative result:

- slots `slot-01` through `slot-04` populated in `/tmp/scpp_debug_release_matrix`
- `slot-05` remained empty
- latest slot resolved to `slot-04`

### DAP preview smoke notes

Validation performed:

- extension package wiring includes debug type `simplecpp-debug`
- activation includes `onDebug:simplecpp-debug`
- internal adapter code passes syntax checks through `npm run check`

Current status:

- package/debugger wiring present
- adapter scaffold present
- not yet fully exercised in a live Extension Development Host session during this hardening pass

This should be treated as:

- **smoke-passed at wiring/syntax level**
- **not yet fully host-verified**

## Suggested Agent Questions

When an AI agent tests this preview, the most valuable feedback areas are:

- Are source-targeted debug actions easy to compose and reason about?
- Are event payloads clear enough to drive automated follow-up actions?
- Is the slot/workspace artifact model easy to inspect and understand?
- Does `--call` fail clearly enough when conversion support is missing?
- Is `--exec` useful in practice, or does it need additional context controls?
- Are the VS Code inspector commands enough to make saved sessions understandable?
- Does the DAP preview feel honest about its current limitations?
