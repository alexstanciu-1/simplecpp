# Simple C++ Debug Slot Workspace
Doc Status: planning

Status: Proposed
Date: 2026-05-26

## Purpose

Replace the fixed shared debug workspace:

- `.prism/debug/session/`

with a bounded slot-based workspace under:

- `.prism/debug/slots/`

This keeps debug artifacts inspectable while bounding disk usage and giving the CLI a predictable cleanup policy.

## Workspace Shape

Each active debug run uses exactly one slot:

- `.prism/debug/slots/slot-01/`
- `.prism/debug/slots/slot-02/`
- ...
- `.prism/debug/slots/slot-N/`

Each slot owns the same artifact families that the current single-session workspace owns:

- `source/`
- `generated/`
- `cache/`
- `native_cpp/`
- `build/`
- `plan.json`
- `events.ndjson`
- `events.json`
- `session.json`

## Allocation Rules

Default policy:

- `slot_count = 5`
- `slot_ttl_minutes = 120`

Allocation order:

1. remove any slot whose recorded last-use time is older than the TTL
2. choose the first empty slot
3. if no slot is empty, choose the oldest slot
4. fully clean that slot
5. reuse it for the new debug run

## Metadata

Each slot writes:

- `session.json`

Minimum fields:

- `version`
- `session_id`
- `slot`
- `created_at`
- `last_used_at`
- `status`

The first implementation only needs enough metadata to support TTL cleanup and oldest-slot selection.

## Config

Project-local config lives under `prism.json`:

```json
{
  "debug": {
    "slot_count": 5,
    "slot_ttl_minutes": 120
  }
}
```

If omitted, the defaults above apply.

## Cleanup Semantics

Slot reuse must be full cleanup, not partial overwrite.

Before a slot is reused, remove:

- `source/`
- `generated/`
- `cache/`
- `native_cpp/`
- `build/`
- `plan.json`
- `events.ndjson`
- `events.json`
- `session.json`

This keeps the next run deterministic and avoids stale build/debug artifacts leaking across runs.

## Concurrency

The initial slot implementation may remain serialized at the project debug-command level if needed.

Even if runs stay serialized at first, the slot model is still useful because it:

- bounds disk growth
- makes cleanup explicit
- keeps a small amount of recent debug state available

Later work may relax serialization by introducing allocator and per-slot locks if needed.

## Migration Rule

The slot model replaces the current fixed debug-session path model for active debug runs.

The old path:

- `.prism/debug/session/`

is no longer the authoritative live workspace path once slot allocation is implemented.
