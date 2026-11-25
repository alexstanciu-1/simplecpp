# Diagnostics And Inspection Plan
Doc Status: planning

Date: 2026-05-08

Purpose:
- capture the remaining diagnostics UX work after separating out source-error mapping
- focus on saved run/error state, short guided errors, build explanation, and inspection

## Scope

This note covers everything except the dedicated source-mapping initiative.

It includes:
- root-cause-first short errors
- saved run metadata
- saved full error reports
- user-facing inspection commands
- guided next steps by error category
- build explanation

## Target UX

After every build-oriented command, the user should be able to answer:
- what command ran
- when it started and ended
- how long it took
- what compiler/backend/launcher was used
- whether runtime and dependencies were reused or rebuilt
- what outputs changed
- what failed, if anything
- what to do next

## Planned Artifacts

### 1. `.prism/last_run.json`

Written after:
- `scpp build`
- `scpp run`
- `scpp runtime-build`

Purpose:
- stable machine-readable record of the most recent command, even on success

Required fields:
- `version`
- `command`
- `argv`
- `cwd`
- `project_root`
- `status`
- `exit_code`
- `started_at`
- `finished_at`
- `duration_ms`

Recommended fields:
- resolved entrypoint
- compiler
- compiler launcher
- Ninja command
- build mode
- runtime reuse/rebuild status
- dependency reuse/rebuild status
- transpiled count
- skipped count
- rebuilt outputs
- artifact paths
- per-phase timings where available

### 2. `.prism/last_error.json`

Written on failure only.

Purpose:
- stable machine-readable record of the most recent failure

Required fields:
- `version`
- `command`
- `argv`
- `cwd`
- `project_root`
- `category`
- `subcategory`
- `short_message`
- `started_at`
- `finished_at`
- `duration_ms`
- `guidance`

Recommended fields:
- structured root cause
- source location when known
- generated location when known
- backend stdout/stderr
- artifact paths
- relevant build/run settings

## Planned Commands

### 1. `scpp error`

Purpose:
- short expanded explanation of the last failure

Reads:
- `.prism/last_error.json`

### 2. `scpp full-error`

Purpose:
- print the complete saved error report

Reads:
- `.prism/last_error.json`

### 3. `scpp last-run`

Purpose:
- short human-readable summary of the most recent build/run/runtime-build

Reads:
- `.prism/last_run.json`

### 4. `scpp full-last-run`

Purpose:
- print the complete saved run report

Reads:
- `.prism/last_run.json`

### 5. `scpp explain-build`

Purpose:
- explain what rebuilt, what reused, and why

Initial implementation approach:
- derive from `.prism/last_run.json`

### 6. `scpp inspect ...`

Initial supported subcommands:
- `scpp inspect generated`
- `scpp inspect build-graph`
- `scpp inspect paths`

Purpose:
- provide a supported inspection path so users do not go straight to raw `ninja`

## Error Guidance Policy

Every short error should end with:
- `Run 'scpp error' for more details.`
- `Run 'scpp full-error' for the full saved report.`

Guidance should vary by category.

Initial categories:
- `syntax`
- `generator`
- `compile`
- `ninja_backend`
- `runtime_cache_write`
- `dependency_build`
- `compiler_missing`
- `ninja_missing`
- `launcher`
- `runtime`

## Timing Policy

Anything meaningful to time should carry:
- `started_at`
- `finished_at`
- `duration_ms`

Phases worth timing when practical:
- config load
- dependency graph resolution
- source scan
- transpile
- runtime build
- dependency build
- Ninja execution
- process execution for `scpp run`

## Owning Layers

### Normative contract

- [specs/project_build_v1.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/project_build_v1.md)

This should own:
- new CLI commands
- saved diagnostics artifact contract
- build/run reporting expectations

### Runtime structured diagnostic contract

- [specs/runtime/error_handling.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/runtime/error_handling.md)

This should own:
- runtime structured error payload shape
- stable vs non-stable runtime JSON fields

### Main implementation surface

- [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)

This is where the current CLI behavior already lives and where most of this work will land first.

## Priority Order

Recommended implementation order:
1. write `.prism/last_run.json`
2. write `.prism/last_error.json`
3. replace wrapper-first error output with root-cause-first short errors
4. add `scpp error`
5. add `scpp full-error`
6. add `scpp last-run`
7. add `scpp full-last-run`
8. add category-based guidance
9. add `scpp explain-build`
10. add `scpp inspect ...`

## Current Code Observation

The current CLI already captures enough context to start this work:
- command arguments
- compiler choice
- build mode
- runtime/dependency reuse intent
- transpiled/skipped counts
- rebuilt outputs
- backend invocation shape

But current failure UX is still wrapper-oriented.

Example current problem area:
- on Ninja failure, the CLI currently reports `Ninja build failed` and previews `build.ninja`
- that is useful as a fallback artifact, but not a good first-user primary error path

## Practical Takeaway

This slice does not depend on solving full original-source remapping first.

We can still deliver a much better diagnostics UX immediately by:
- saving run/error state
- adding readable inspection commands
- foregrounding root cause
- guiding the user explicitly after failures
