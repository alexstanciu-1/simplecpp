# SimpleC++ Debug Event Schema
Doc Status: planning

Date: 2026-05-26

Purpose:

- define the structured event model emitted by `scpp debug`
- give CLI text rendering, VS Code integration, and future DAP translation one shared event stream
- make debug output reproducible and machine-consumable by default

This note is planning guidance, not semantic authority.

## Why This Exists

If `scpp debug` emits only ad hoc human text, every downstream tool has to scrape unstable strings.

That creates avoidable drift between:

- CLI output
- saved debug logs
- VS Code command integration
- future debug-adapter behavior
- AI-assisted debugging workflows

So the system should emit structured events first, then derive human presentation from that model where practical.

## Main Design Rule

The preferred internal event model is line-delimited structured records.

Current recommended rule:

- `ndjson` is the canonical streaming event format
- `json` is the canonical single-payload aggregate form
- `text` is a presentation layer over the same structured information when possible

This mirrors the runtime error-handling direction where stable machine-readable structure is favored over message scraping, while keeping debug-only details flexible.

## Design Goals

The event model should be:

- source-first
- machine-readable
- stream-friendly
- replay-friendly
- explicit about stable vs debug-only fields
- narrow enough for Phase 1

## Event Stream Model

Each debug event is one JSON object.

For `ndjson` output:

- one compact JSON object per line

For `json` output:

- one top-level aggregate object containing an ordered `events` array

For `text` output:

- rendered from the same conceptual event sequence
- for the current implementation, dump text output should describe the injected expression and whether it ran `before` or `after` the chosen rewritten-source line

## Top-Level Event Shape

Each event should follow this high-level structure:

```json
{
  "version": 1,
  "event": "hit",
  "seq": 3,
  "session_id": "dbg-2026-05-26T10-15-00Z-7f3c",
  "timestamp": "2026-05-26T10:15:02.412Z",
  "source": {
    "file": "/abs/project/main.phs",
    "line": 42
  },
  "body": {}
}
```

Top-level fields:

- `version`
- `event`
- `seq`
- `session_id`
- `timestamp`
- `source`
- `body`

## Stable vs Debug-Only Contract

Current recommended stability rule:

Stable:

- `version`
- `event`
- `seq`
- `session_id`
- `source.file`
- `source.line`
- event-specific structural keys documented in this note

Not stable:

- rendered text summaries
- human-oriented explanatory strings
- generated C++ detail
- host/runtime-native stack formatting
- incidental debug metadata not explicitly listed as stable

This keeps downstream tools keyed to structure rather than prose.

## Shared Fields

### `version`

Meaning:

- event schema version

Rules:

- required
- integer
- initial value `1`

### `event`

Meaning:

- event type discriminator

Phase 1 recommended event types:

- `session_start`
- `build_error`
- `hit`
- `dump`
- `break`
- `exit`
- `runtime_error`
- `session_summary`

Later possible event types:

- `call_enter`
- `call_return`
- `cast_trace`
- `warning`
- `session_end`

### `seq`

Meaning:

- monotonically increasing event number within one session

Rules:

- required
- integer
- starts at `1`

### `session_id`

Meaning:

- session correlation identifier

Rules:

- required
- should match the `DebugPlan.session.id` when present

### `timestamp`

Meaning:

- event emission time in UTC

Rules:

- required
- ISO-8601 string

### `source`

Meaning:

- primary authoring-source anchor for the event

Shape:

```json
{
  "file": "/abs/project/main.phs",
  "line": 42
}
```

Rules:

- required for source-bound events such as `hit`, `dump`, and `exit`
- may be omitted or null for global/session events if no source location exists

### `body`

Meaning:

- event-specific payload

Rules:

- required
- object

## Phase 1 Event Types

### `session_start`

Meaning:

- the session has started and the normalized plan has been accepted

Shape:

```json
{
  "version": 1,
  "event": "session_start",
  "seq": 1,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:00Z",
  "body": {
    "mode": "process",
    "format": "ndjson",
    "summary_enabled": true
  }
}
```

Suggested body fields:

- `mode`
- `format`
- `summary_enabled`
- optional `plan_label`

### `hit`

Meaning:

- execution reached a requested source stop/observation point

Shape:

```json
{
  "version": 1,
  "event": "hit",
  "seq": 2,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:02.412Z",
  "source": {
    "file": "/abs/project/main.phs",
    "line": 42
  },
  "body": {
    "action_kind": "exit",
    "phase": "before"
  }
}
```

Suggested stable body fields:

- `action_kind`
- `phase`

Allowed `phase` values for the wider design:

- `before`
- `after`
- `at`

### `dump`

Meaning:

- a requested value observation was emitted

Shape:

```json
{
  "version": 1,
  "event": "dump",
  "seq": 3,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:02.413Z",
  "source": {
    "file": "/abs/project/main.phs",
    "line": 42
  },
  "body": {
    "subject": {
      "kind": "local_name",
      "name": "$row"
    },
    "phase": "before",
    "value": {
      "type": "hash",
      "summary": "hash(3)",
      "shape": {
        "size": 3,
        "keys": ["id", "name", "status"]
      }
    }
  }
}
```

Stable body fields:

- `subject.kind`
- `subject.name`
- `phase`
- `value.type`

Recommended but debug-flexible fields:

- `value.summary`
- `value.shape`
- `value.preview`
- `value.identity`

Important rule:

- the event schema should identify the inspected value and basic type/shape
- the exact textual pretty-print representation should not be treated as stable

### `exit`

Meaning:

- the debug run terminated intentionally because an exit action fired

Shape:

```json
{
  "version": 1,
  "event": "exit",
  "seq": 4,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:02.414Z",
  "source": {
    "file": "/abs/project/main.phs",
    "line": 42
  },
  "body": {
    "reason": "action_exit",
    "action_kind": "exit"
  }
}
```

Stable body fields:

- `reason`
- `action_kind`

### `runtime_error`

Meaning:

- the instrumented run failed with a runtime/build-side execution error after launch

Shape:

```json
{
  "version": 1,
  "event": "runtime_error",
  "seq": 5,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:02.600Z",
  "source": {
    "file": "/abs/project/main.phs",
    "line": 44
  },
  "body": {
    "code": "coalesce_selected_branch_has_no_usable_value_domain",
    "component": "php::coalesce_eval",
    "message": "operator ?? selected a branch with no usable value"
  }
}
```

Rules:

- this should align with existing structured runtime error output where practical
- stable downstream consumers should prefer `code` and `component`
- `message` is useful but should be treated as presentation-friendly, not strongly stable

Recommended stable fields:

- `code`
- `component`
- optional `operator`
- optional `category`
- optional `subcategory`

Recommended debug-flexible fields:

- `message`
- `details`
- `trace`
- `source_trace`

### `session_summary`

Meaning:

- final summary of what happened in the session

Shape:

```json
{
  "version": 1,
  "event": "session_summary",
  "seq": 6,
  "session_id": "dbg-...",
  "timestamp": "2026-05-26T10:15:02.700Z",
  "body": {
    "status": "completed",
    "event_count": 6,
    "hit_count": 1,
    "dump_count": 1,
    "error_count": 0,
    "duration_ms": 288
  }
}
```

Stable body fields:

- `status`
- `event_count`
- `hit_count`
- `dump_count`
- `error_count`
- `duration_ms`

Allowed `status` values for the wider design:

- `completed`
- `exited`
- `failed`
- `aborted`

## Aggregate JSON Form

When `--format json` is used, the recommended top-level shape is:

```json
{
  "version": 1,
  "session_id": "dbg-...",
  "status": "completed",
  "events": [
    { "version": 1, "event": "session_start", "seq": 1, "session_id": "dbg-...", "timestamp": "..." , "body": {} }
  ]
}
```

Rules:

- preserve event ordering exactly
- do not create a second incompatible object model for non-streaming mode

## Value Payload Guidance

The first event schema should stay modest about value rendering.

Recommended common fields under `body.value`:

- `type`: compact runtime/value kind
- `summary`: short human-readable summary
- `preview`: optional scalar/string preview
- `shape`: optional structured shape info
- `identity`: optional debug identity token

Examples:

Scalar:

```json
{
  "type": "int",
  "preview": 42
}
```

String:

```json
{
  "type": "string",
  "summary": "string(5)",
  "preview": "hello"
}
```

Hash/container:

```json
{
  "type": "hash",
  "summary": "hash(3)",
  "shape": {
    "size": 3,
    "keys": ["id", "name", "status"]
  }
}
```

Important recommendation:

- keep rich shape/object/container rendering optional
- Phase 1 should not block on perfect universal value introspection

## Source Mapping Rule

Debug events should report locations on the rewritten debug source surface for the active run.

For the current source-rewrite-based debug implementation, this means:

- `source.file` still names the logical source file under debug
- `source.line` refers to the rewritten debug-source line numbering after injected debug statements have been inserted
- events do not attempt to remap those line numbers back to the original pre-rewrite source for this version

This is intentional for the initial version because it keeps the runtime, STAN, AST, and emitted debug events on one consistent source surface.

Generated C++ detail, if preserved, should be secondary metadata rather than the primary event anchor.

If secondary generated detail is included, recommended shape is:

```json
{
  "generated": {
    "file": "/abs/project/.prism/generated/main.cpp",
    "line": 188
  }
}
```

This field should be treated as debug-only unless a later note promotes it.

## Event Ordering Rule

Recommended ordering for a normal successful narrow session:

1. `session_start`
2. zero or more `hit`
3. zero or more `dump`
4. optional `break`
5. optional `exit`
6. optional `runtime_error`
7. optional `session_summary`

Not every session needs every event type.

## Error Categories

The wider system will likely need more than one failure family.

Current practical distinction:

- plan/validation failures: reject before run, handled by CLI/request layer
- build failures: compile/generation problems before launch
- runtime failures: instrumented program launched, then failed

Implementation note for the current slice:

- `build_error` may carry `category`, `subcategory`, and `guidance`
- `runtime_error` may carry `category`, `subcategory`, and `guidance`
- these fields are intended to help downstream tools branch on failure family without scraping stderr text

Future notes may add:

- `plan_error`
- `build_error`

if those deserve first-class event forms instead of only command failure exits.

## VS Code / DAP Guidance

This event model is intentionally suitable for:

- VS Code command integration that parses NDJSON and renders panels/output
- future DAP translation where `hit`, `dump`, `exit`, and `runtime_error` are mapped into debugger concepts

Examples:

- `hit` -> stop notification
- `dump` -> variable/inspection surface update
- `exit` -> terminated event
- `runtime_error` -> stopped/exception-style event or surfaced diagnostic

## Phase 1 Recommendation

The first implementation slice should emit only:

- `session_start`
- `hit`
- `dump`
- `exit`
- `runtime_error`
- `session_summary`

Anything richer should be added only after the narrow path is proven useful and affordable.

## Non-Goals

- this note does not define the CLI flag syntax
- this note does not define the full `DebugPlan` request schema
- this note does not require live interactive stepping
- this note does not require complete runtime stack/frame introspection in Phase 1
