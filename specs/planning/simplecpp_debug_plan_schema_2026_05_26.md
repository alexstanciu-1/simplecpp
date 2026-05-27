# SimpleC++ DebugPlan Schema
Doc Status: planning

Date: 2026-05-26

Purpose:

- define the normalized internal request shape for `scpp debug`
- separate CLI UX flags from the generator/runtime-facing debug request
- give CLI, STAN, generator, runtime, and editor tooling one shared contract target

This note is planning guidance, not semantic authority.

Implementation note for the current slice:

- for dump actions, `file:line` selects the injection site
- the trailing `expr` is injected at that site and evaluated there
- the implementation does not attempt source-expression matching against existing author code
- only simple explicit source-site actions that name `file:line` and insert a helper `before` or `after` that site are eligible for source rewrite in this feature
- inputs such as `argv`, `stdin`, and `env` are runtime/session controls and must not trigger project-wide source rewriting

## Why This Exists

`scpp debug` should not let each downstream layer interpret ad hoc flag combinations on its own.

The system needs one normalized object that represents:

- what will run
- what inputs it receives
- what observations are requested
- what output format is desired
- what pre-resolved semantic/source targets were selected

That object is the `DebugPlan`.

## Design Goals

The `DebugPlan` should be:

- explicit
- serializable
- replayable
- source-first
- stable enough for CLI, editor, and future debug-adapter use
- narrow enough that Phase 1 can implement only part of it safely

## Layering Rule

Preferred ownership:

1. CLI parses user-facing flags and session files
2. STAN/shared front-end resolves semantic/source targets
3. `scpp debug` builds one normalized `DebugPlan`
4. generator/runtime consume the plan
5. output/rendering layers consume emitted debug events, not raw flags

The generator should not become the parser for end-user flag syntax.

## High-Level Shape

The normalized `DebugPlan` is a JSON-serializable object with these top-level sections:

- `version`
- `session`
- `mode`
- `target`
- `inputs`
- `actions`
- `output`
- `resolution`
- `build`

Phase 1 does not need every field below, but new work should fit into this shape rather than invent unrelated one-off config.

## Canonical Skeleton

```json
{
  "version": 1,
  "session": {
    "id": "dbg-2026-05-26T10-15-00Z-7f3c",
    "label": "stop-at-main-42",
    "created_at": "2026-05-26T10:15:00Z"
  },
  "mode": "process",
  "target": {
    "project_root": "/abs/project",
    "entry": {
      "kind": "project_entry"
    }
  },
  "inputs": {
    "argv": ["--env=dev", "42"],
    "stdin": {
      "kind": "file",
      "path": "/tmp/in.txt"
    },
    "env": {
      "APP_ENV": "dev"
    }
  },
  "actions": [
    {
      "kind": "exit",
      "location": {
        "file": "/abs/project/main.phs",
        "line": 42
      }
    }
  ],
  "output": {
    "format": "ndjson",
    "summary": true,
    "destination": {
      "kind": "stdout"
    }
  },
  "resolution": {
    "resolver": "stan",
    "status": "resolved"
  },
  "build": {
    "variant": "debug",
    "cache_key": "sha256:...",
    "instrumentation_scope": "narrow"
  }
}
```

## Top-Level Fields

### `version`

Meaning:

- schema version for the normalized plan

Rules:

- required
- integer
- first version is `1`

### `session`

Meaning:

- metadata for replay, saved sessions, and UX display

Fields:

- `id`: stable session identifier for one saved plan
- `label`: optional human-friendly label
- `created_at`: ISO-8601 UTC timestamp

Rules:

- `session` is recommended even for ephemeral runs
- replay/load/save workflows should preserve it

### `mode`

Meaning:

- what kind of execution harness is requested

Allowed values for the wider design:

- `process`
- `function`
- `static_method`
- `instance_method`

Phase 1:

- required support: `process`
- future support: `function`, `static_method`
- later support: `instance_method`

### `target`

Meaning:

- what code will be run or inspected

Required subfields depend on `mode`.

Common fields:

- `project_root`: absolute project path
- `entry`: entry/harness target description

#### `target.entry`

For `process` mode:

```json
{
  "kind": "project_entry"
}
```

For `function` mode:

```json
{
  "kind": "function",
  "callable": "foo",
  "resolved_file": "/abs/project/src/main.phs",
  "resolved_line": 18
}
```

For `static_method` mode:

```json
{
  "kind": "static_method",
  "callable": "User::loadById",
  "resolved_file": "/abs/project/src/User.phs",
  "resolved_line": 73
}
```

For `instance_method` mode:

```json
{
  "kind": "instance_method",
  "callable": "User:loadProfile",
  "resolved_file": "/abs/project/src/User.phs",
  "resolved_line": 102
}
```

Rules:

- all callable forms should store the original callable string and the resolved source anchor once resolution succeeds
- generator/runtime should use resolved identity, not re-parse human shorthand

### `inputs`

Meaning:

- execution inputs for the chosen harness

Fields:

- `argv`: ordered process argument list
- `stdin`: stdin source descriptor
- `env`: environment-variable map
- `call_args`: positional callable arguments
- `named_args`: named callable arguments
- `receiver`: instance receiver payload for instance-method mode

#### `inputs.argv`

Rules:

- array of strings
- valid only for `process` mode

#### `inputs.stdin`

Supported shape:

```json
{
  "kind": "file",
  "path": "/tmp/input.txt"
}
```

Future shapes may include:

- `kind = "inline"`
- `kind = "none"`

Phase 1:

- `file` and implicit none are enough

#### `inputs.env`

Rules:

- string-to-string map
- debug-launch environment only
- no promise of shell expansion behavior

#### `inputs.call_args`

Shape:

```json
[
  { "encoding": "raw_json", "value": 123 },
  { "encoding": "raw_json", "value": "abc" }
]
```

Rules:

- ordered array
- intended for `function` / `static_method` / `instance_method`
- each argument should carry explicit encoding mode when callable invocation arrives

#### `inputs.named_args`

Shape:

```json
{
  "id": { "encoding": "raw_json", "value": 123 }
}
```

Rules:

- only valid if STAN-backed parameter-name resolution is available
- should remain optional even after positional mode exists

#### `inputs.receiver`

Meaning:

- receiver-state payload for `instance_method`

Rules:

- deferred beyond Phase 1
- should be explicit and isolated from ordinary call arguments

### `actions`

Meaning:

- requested observations or stop behavior during the run

Rules:

- array
- actions are evaluated in plan order unless later implementation defines explicit precedence rules
- a plan may contain zero actions for plain debug-run/session capture use cases

Supported wider-design action kinds:

- `exit`
- `break`
- `dump_before`
- `dump_after`
- `trace_runtime_casts`

Phase 1 recommended action kinds:

- `exit`
- optionally one of `dump_before` or `dump_after`

#### Location shape

Source-oriented actions use:

```json
{
  "file": "/abs/project/main.phs",
  "line": 42
}
```

Future extension may add:

- `column`
- `span`
- `statement_id`

#### `exit`

Shape:

```json
{
  "kind": "exit",
  "location": {
    "file": "/abs/project/main.phs",
    "line": 42
  }
}
```

Meaning:

- terminate the debug run once execution reaches the mapped source location

#### `dump_before`

Shape:

```json
{
  "kind": "dump_before",
  "location": {
    "file": "/abs/project/main.phs",
    "line": 42
  },
  "subject": {
    "kind": "local_name",
    "name": "$row"
  },
  "detail": {
    "shape": true,
    "depth": 2
  }
}
```

Meaning:

- emit a structured dump event before the source point executes

#### `dump_after`

Same shape as `dump_before`, but observation occurs after execution of the mapped source point.

#### `trace_runtime_casts`

Shape:

```json
{
  "kind": "trace_runtime_casts"
}
```

Meaning:

- emit events for selected runtime typed-boundary cast/check sites

### `output`

Meaning:

- requested event rendering and destination behavior

Fields:

- `format`
- `summary`
- `destination`

#### `output.format`

Allowed values:

- `text`
- `json`
- `ndjson`

Rules:

- Phase 1 should support all three if practical
- `ndjson` is the preferred canonical event stream for tooling
- `text` should be treated as a presentation layer over structured events where possible

#### `output.summary`

Meaning:

- request a final session summary event or rendered summary view

Rules:

- boolean

#### `output.destination`

Shapes:

```json
{ "kind": "stdout" }
```

```json
{ "kind": "file", "path": "/tmp/debug.ndjson" }
```

Phase 1:

- stdout and file are sufficient

### `resolution`

Meaning:

- metadata about how semantic/source targets were validated

Suggested fields:

- `resolver`: `stan` | `none`
- `status`: `resolved` | `partially_resolved` | `failed`
- `notes`: optional human-readable array
- `artifacts`: optional paths to STAN status/report files used during resolution

Rules:

- this section is primarily for diagnostics, explainability, and editor tooling
- generator/runtime should not depend on long narrative notes

### `build`

Meaning:

- debug-build strategy metadata

Suggested fields:

- `variant`: `debug`
- `cache_key`: stable hash for identical plan reuse
- `instrumentation_scope`: `narrow` | `broad`
- `artifact_root`: optional debug artifact directory

Rules:

- this section exists to make build cost controllable and observable
- normal project build outputs should remain conceptually separate from temporary debug variants

## CLI Mapping Notes

Examples:

### Process mode with source exit

```bash
scpp debug --args '["--env=dev","42"]' --exit 'main.phs:42'
```

Normalizes roughly to:

```json
{
  "mode": "process",
  "inputs": {
    "argv": ["--env=dev", "42"]
  },
  "actions": [
    {
      "kind": "exit",
      "location": {
        "file": "/abs/project/main.phs",
        "line": 42
      }
    }
  ]
}
```

### Function mode with positional JSON args

```bash
scpp debug --call 'sum_values' --call-args '[1,2,3]'
```

Normalizes roughly to:

```json
{
  "mode": "function",
  "target": {
    "entry": {
      "kind": "function",
      "callable": "sum_values"
    }
  },
  "inputs": {
    "call_args": [
      { "encoding": "raw_json", "value": 1 },
      { "encoding": "raw_json", "value": 2 },
      { "encoding": "raw_json", "value": 3 }
    ]
  }
}
```

## Save/Load Session Rule

`--save-session` and `--load-session` should serialize/deserialize the normalized `DebugPlan`, not only the original CLI argument text.

Why:

- avoids CLI-parser drift
- makes editor/adapter reuse easier
- gives one replay artifact format

## Validation Rules

The normalizer should reject invalid combinations early.

Examples:

- `mode = process` with `receiver`
- `mode = function` without callable target
- `dump_before` without a location
- unsupported `output.format`
- duplicate mutually exclusive input modes when policy forbids them

Resolution failures should be expressed as structured CLI/debug errors, not silent fallback.

## Phase 1 Recommendation

The first implementation slice should require only:

- `version`
- `mode = process`
- `target.project_root`
- `inputs.argv`
- `inputs.stdin`
- `inputs.env`
- one `exit` action
- optional one dump action
- `output.format`
- `output.summary`

Everything else can remain schema-reserved until callable-mode work lands.

## Non-Goals

- this note does not define the runtime event payload format
- this note does not define native debugger interop
- this note does not require typed coercion policy for callable inputs
- this note does not force full DAP semantics in Phase 1
