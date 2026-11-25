# STAN Build Worker Integration Plan
Doc Status: planning

Date: 2026-05-25

## Purpose

Define the first implementation shape for using STAN as a per-project background analyzer that supports:

- `scpp build`
- `scpp run`
- future editor and tooling reuse

This note is planning only.
It does not change current CLI or language semantics by itself.

## Related Authorities And Context

Normative owner:

- [specs/project_build_v1.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/project_build_v1.md)

Related planning/context:

- [specs/planning/stan_implementation_plan_2026_05_22.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/stan_implementation_plan_2026_05_22.md)
- [specs/planning/stan_lsp_handoff_2026_05_25.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/stan_lsp_handoff_2026_05_25.md)

## Goal

When a user runs `scpp build`, the build flow should consult the current STAN project state before continuing.

The intended user-visible behavior is:

1. STAN is project-local and reusable across commands
2. STAN watches the project source tree and maintains current analysis state
3. `scpp build` reuses current STAN state when it matches the current project source fingerprint
4. if STAN says the project has `compile-errors`, `scpp build` stops before code generation / C++ compilation continues
5. if STAN has only advisory findings, `scpp build` continues and prints only a short static-analysis summary

## Current Design Direction

STAN should be treated as a per-project worker.

`scpp build` should not need to know STAN internals.

The preferred coordination shape is:

- project-local file-based state
- project-local worker heartbeat
- project-local analysis report
- short polling by `scpp build` when the current STAN state is stale

This keeps the design:

- simple
- debuggable
- restartable
- reusable by both CLI and editor workflows

## Project-Local Worker Model

Each Prism project may have one active STAN worker associated with its project root.

The worker should:

1. start against one resolved project root
2. perform one full workspace pass on startup
3. watch source/config inputs for change
4. perform incremental re-analysis when watched inputs change
5. publish the latest state to project-local files
6. stop itself after an inactivity timeout, releasing RAM

The current preferred inactivity timeout is:

- 15 minutes without meaningful activity

Meaningful activity may include:

- file changes
- explicit CLI-triggered refresh requests
- editor-driven requests
- build-driven requests

## Watch Scope

The worker should watch only inputs that affect STAN analysis for the current project shape.

Initial watch scope:

- project `*.phs`
- compatible project `*.php`
- `prism.json`

Initial ignore scope:

- `.prism/**`
- generated files
- build outputs
- temp/cache artifacts under `.prism/`

The worker may debounce bursts of filesystem events slightly before running one incremental pass.

## Source Fingerprint

The canonical freshness key should be:

- `source_fingerprint`

This should be preferred over a loose combination of separate timestamp checks and ad-hoc state checks.

For the first implementation, the source fingerprint should include:

- all watched project source files that participate in STAN analysis
- `prism.json`

Explicit non-goals for the first implementation:

- dependency project fingerprints
- STAN version in the fingerprint

Dependency project fingerprints may be added later.

STAN version should not be part of this first source-fingerprint contract.

## Worker State Files

The preferred first shape is to use separate files instead of one large mutable file.

### 1. Status file

Suggested path:

- `.prism/cache/stan_status.json`

Purpose:

- small, fast-to-read state for CLI/editor/build checks

Suggested fields:

- `project_root`
- `analysis_state`
- `source_fingerprint`
- `requested_fingerprint`
- `run_id`
- `started_at`
- `finished_at`
- `last_activity_at`
- `compile_error_count`
- `stan_error_count`
- `stan_warning_count`
- `stan_notice_count`
- `report_path`

Suggested `analysis_state` values:

- `idle`
- `running`
- `ready`
- `failed`

### 2. Report file

Suggested path:

- `.prism/cache/stan_report.json`

Purpose:

- full findings payload for inspection and future tooling use

Suggested contents:

- repeated top-level fingerprint/run metadata
- per-file findings
- counts by bucket
- raw STAN diagnostic kind
- mapped build bucket

### 3. Heartbeat file

Suggested path:

- `.prism/cache/stan_worker.json`

Purpose:

- prove that a worker is alive for this project
- allow `scpp build` to distinguish “stale state but live worker” from “no worker currently active”

Suggested fields:

- `pid`
- `project_root`
- `last_heartbeat_at`
- `last_seen_request_at`

### 4. Request file

Suggested path:

- `.prism/cache/stan_request.json`

Purpose:

- allow `scpp build` or editor actions to request a refresh without tight process coupling

Suggested fields:

- `requested_at`
- `requested_fingerprint`
- `reason`

Possible initial `reason` values:

- `build`
- `editor`
- `manual`

## Atomic Publish Rule

The worker should not write partial JSON directly into the live state files.

Preferred rule:

1. write temp file
2. fsync if needed
3. atomic rename into place

This should apply at least to:

- `stan_status.json`
- `stan_report.json`

## CLI / Worker Separation

The user-visible role split should stay clear.

Suggested boundary:

- `scpp stan`
  - human-facing CLI report / one-shot analysis
- `scpp stan worker`
  - long-lived project-local worker mode

The worker should remain largely unaware of `scpp build` beyond reading request-state files and publishing analysis-state files.

## `scpp build` Handshake

The first intended `scpp build` handshake is:

1. resolve root project and source set as normal
2. compute the current `source_fingerprint`
3. read `.prism/cache/stan_status.json` if present
4. if status is `ready` and `source_fingerprint` matches, use the published STAN result immediately
5. if status is stale or missing, request fresh STAN analysis
6. if there is no live worker heartbeat, start or re-start the worker
7. poll for fresh matching state
8. if a matching `ready` result arrives in time, use it
9. if STAN reports `compile-errors`, stop the build
10. otherwise continue build and print only a short advisory summary

## Polling And Timeout

For the first implementation, `scpp build` may poll simply.

Preferred first defaults:

- poll interval: `100 ms`
- default wait timeout: `10 seconds`
- upper practical range if needed later: `20 seconds`

If the timeout expires, `scpp build` should fail clearly rather than silently continuing with uncertain STAN freshness.

## Initial Severity Buckets

The first public build-facing buckets should be:

- `compile-errors`
- `stan-errors`
- `stan-warnings`
- `stan-notices`

These are build-facing buckets.

They are not required to replace current internal STAN diagnostic kinds.

Instead, current STAN diagnostics should be mapped into these buckets.

## Build-Blocking Policy

The first implementation should keep `compile-errors` intentionally narrow.

The rule is:

- only findings that STAN considers highly likely to become downstream C++ compile failures should be promoted to `compile-errors`

This should bias toward:

- “definitely compile-fatal” first

And explicitly avoid:

- broad advisory findings
- speculative type-shape concerns
- style or maintainability concerns

This bucket can expand later after confidence improves.

## First-Pass Mapping Guidance

The initial mapping should remain conservative.

### `compile-errors`

Good first candidates:

- unresolved function calls where lowering/build will later require a real callable target
- unresolved method calls where the target member does not exist in the semantic model
- unresolved class references required for instantiation or member access
- other diagnostics where STAN has high confidence that generated C++ compilation will fail

### `stan-errors`

Use for:

- severe semantic findings that are not yet proven compile-fatal in the current lowering/runtime model

### `stan-warnings`

Use for:

- the current warning-style advisory diagnostics, including existing STAN warning families unless explicitly promoted

### `stan-notices`

Use for:

- low-severity informational findings

## Build Output Policy

If `compile-errors > 0`, `scpp build` should:

1. print the compile-errors in detail
2. stop before continuing with the build
3. point the user to `scpp stan` for the full report

Illustrative shape:

```text
STAN pre-build check failed: 2 compile-errors

[compile-error] Missing method `SampleGreeter::grete()`
  at main.phs:12

[compile-error] Unresolved function `build_demo_usr()`
  at main.phs:18

Build stopped before C++ generation/compilation.
Run `scpp stan` for the full static-analysis report.
```

If `compile-errors == 0`, `scpp build` should continue and print only a short advisory summary when advisory findings exist.

Illustrative shape:

```text
Static Analysis: 1 error, 3 warnings, 2 notices.
Run `scpp stan` for more details.
```

The first implementation should not dump all advisory findings during `scpp build`.

## `scpp run` Relationship

`scpp run` should inherit the same STAN pre-build behavior through the build path it already relies on.

The same build-blocking rule should apply:

- `compile-errors` stop progress before the program is run

## Reuse With Editor/LSP Work

This worker/state model should be compatible with the current LSP/editor direction.

Desired reuse:

- editor features can keep their request/overlay/session logic
- project-local worker state can become a shared freshness and findings source
- the worker should not be forced to encode editor-specific transport concerns

The STAN worker is the semantic engine.
LSP remains a protocol/UX layer.

## Recommended Implementation Order

1. define the worker file contract and helper read/write routines
2. implement worker heartbeat + status/report publishing
3. implement initial full-pass worker loop
4. add watch + incremental invalidation
5. add `scpp build` freshness check + request + poll handshake
6. add the first conservative diagnostic bucket mapping
7. stop builds only on `compile-errors`
8. print advisory summary only for the non-blocking buckets

## Out Of Scope For This First Pass

- dependency project fingerprints
- STAN version as part of the source fingerprint
- broad compile-error promotion
- perfect semantic reference tracking through build state files
- replacing the current editor overlay/session model
- sophisticated IPC beyond project-local file coordination

## Success Criteria

This plan is successful when:

1. one project-local STAN worker can stay warm for a project
2. the worker publishes current state into `.prism/cache/`
3. `scpp build` can reuse that state when the source fingerprint matches
4. `scpp build` can request/poll for refresh when state is stale
5. truly compile-fatal STAN findings stop the build early
6. ordinary STAN advisory findings do not block the build and are summarized cleanly
