# SimpleC++ Debug CLI Direction

Status: planning draft

Date: 2026-05-26

Purpose:

- define a larger `scpp debug` direction, not only a minimal v1
- evaluate each proposed debugging affordance against the current Simple C++ toolchain
- separate what can be built mostly from current CLI/STAN/runtime pieces from what needs new runtime or compiler support

This note is not a Simple C++ authority.
It is an Open M3 planning note based on current hands-on usage of:

- `scpp 0.1.62`
- current STAN worker/report flow
- current runtime diagnostics
- current `dbg(...)` / `dbg_if(...)` / `dbg_set(...)` support

## Why This Matters

Traditional debugging assumes a human sitting in an interactive debugger.

That is not the best fit for an AI agent.

An AI agent works best when debugging is:

- reproducible
- scriptable
- source-mapped
- structured
- non-interactive by default
- easy to replay with small input changes

So the interesting direction is not “copy gdb into Simple C++”.

The more interesting direction is:

- a programmable debug CLI
- STAN-assisted callable and expression resolution
- source-mapped structured dump events
- optional runtime instrumentation

## Current Building Blocks

These already exist and are directly relevant.

### CLI / orchestration

- [project_services.php](/home/alexv/__AI/simple_cpp/stable/bin/project_services.php:1)
- `scpp build`
- `scpp run`
- `scpp error`
- `scpp full-error`
- `scpp stan`

### Static analysis / semantic resolution

- STAN workspace context/session/report machinery
- project-local STAN files under `.prism/cache/`
- source-mapped diagnostics with file/line/span/code/context

### Runtime/source mapping

- generated `.line.tsv` files
- runtime failure remap into `.prism/last_error.json`

### Existing debug helpers

- `dbg(...)`
- `dbg_if(...)`
- `dbg_set(...)`

These already prove that:

1. source-aware debug output is possible
2. generated code can carry source location into runtime support
3. Simple C++ is already willing to expose debugging-oriented helpers at the language/runtime layer

## Design Goal

The long-term design target should be:

- `scpp debug` becomes a programmable source-level debug harness
- STAN resolves call targets, source locations, and maybe expression context
- the S2S generator injects debug instrumentation into generated code
- the runtime provides small helper APIs for event hooks and value inspection
- the output can be human-readable or machine-readable

## Primary Implementation Direction

The explicit primary implementation direction should be split by target shape.

Current preferred model:

- source-targeted actions should use a temporary source-rewrite path only when they are a simple explicit source-site pattern
- the intended simple pattern is: explicit `file:line` target plus `before` / `after` style helper insertion at that site
- non-source-targeted features must not be implemented by rewriting or patching every source file
- generator/runtime support should stay focused on lowering explicit debug helpers and emitting structured events
- STAN helps resolve and validate targets where needed

Preferred pipeline for source-targeted actions:

1. CLI parses the debug request
2. the system builds one normalized debug plan
3. source-targeted actions are grouped by file and rewritten onto the source surface for that run
4. STAN/front-end/generator consume the rewritten source view
5. the rewritten variant is compiled and run
6. runtime helpers emit structured debug events/results

Preferred pipeline for non-source-targeted features:

1. CLI parses the debug request
2. the system builds one normalized debug plan
3. normal build/run continues without source rewrite unless a specific action explicitly targets `file:line`
4. runtime/build/session layers supply the feature without a project-wide source transformation

This should be treated as the default design posture for the rest of this note.

## Important Boundary Rule

Current required rule:

- only simple explicit source-site actions of the form `file:line` with `before` / `after` semantics may use source rewrite at that target
- any feature that does not explicitly specify `file:line` must not be implemented through project-wide source rewrite or generator-side replacement across all files
- the system must not rely on a “replace in all files” debug strategy for global features such as throw/warning/call tracing controls

This keeps source rewrite narrow, inspectable, and honest.

## Important Design Rule

The normal build path should stay normal.

Current preferred rule:

- `scpp build` emits the ordinary generated program
- `scpp debug ...` emits a temporary instrumented generated variant for that debug session

This avoids:

- always-on debug overhead
- polluting ordinary generated output with persistent debug machinery
- blurring the difference between ordinary build behavior and debug-session behavior

## Debug Plan Model

The generator should be the main instrumentation owner, but it should not parse ad hoc CLI flags directly.

Preferred layering:

1. CLI owns user-facing flags/options
2. STAN/shared front-end own semantic resolution and validation
3. the system produces one normalized debug plan
4. the generator consumes that plan
5. runtime helpers emit the resulting observations

That keeps the generator central without turning it into the UX/parser layer.

## High-Level Modes

Current likely main modes:

1. process mode
   - run the normal project entrypoint with argv/env/stdin control

2. function mode
   - call a top-level function directly

3. method mode
   - call an instance or static method directly

4. exec mode
   - evaluate one expression inside the project through a dedicated harness path

5. replay mode
   - load a saved debug session and rerun it

6. analysis-assisted mode
   - use STAN to suggest call shapes, local names, or dump candidates

## Proposed CLI Families

Below, each family includes:

- what it means
- why it is useful
- current implementation lane

Implementation lane labels:

- `Now`: mostly buildable with current pieces and modest CLI work
- `Soon`: plausible with current architecture, but needs focused generator/runtime work
- `Later`: valuable, but needs deeper runtime/compiler/debug model support

---

## 1. Entry / Invocation

### `--args`

Example:

```bash
scpp debug --args '["--env=dev","42"]'
```

Meaning:

- run the normal program entrypoint with explicit argv

Why useful:

- simplest bridge from `scpp run`
- immediately useful for agent-driven repro loops

Lane:

- `Now`

Implementation:

- reuse the current `scpp run` path in `project_services.php`
- add one debug wrapper mode that:
  - resolves the project
  - requests an instrumented build variant as needed
  - runs with structured event capture enabled

### `--stdin-file`

Meaning:

- feed stdin from a file

Why useful:

- stable repros for parsers / CLI tools

Lane:

- `Now`

Implementation:

- CLI/runtime wrapper only

### `--exec`

Example:

```bash
scpp debug --exec '1 + 2'
```

Meaning:

- evaluate one expression inside the current project through a dedicated debug harness
- do not rewrite project source files for this mode

Why useful:

- quick agent experiments without introducing a named function first
- useful for checking value shape or helper behavior inside the project/runtime context

Lane:

- `Soon`

Implementation:

- dedicated harness path
- expression only, not arbitrary statement snippets
- should still build and run inside the project and benefit from project composition/includes

### `--env`

Meaning:

- inject environment variables

Why useful:

- many bugs hide in config/env differences

Lane:

- `Now`

Implementation:

- CLI wrapper around process launch

---

## 2. Direct Callable Invocation

### `--call "function"`

Meaning:

- invoke a top-level function directly

Why useful:

- lets the agent debug below full application boot
- ideal for small repro and unit-style investigation

Lane:

- `Soon`

Implementation:

- STAN resolves callable identity and expected parameter count/types
- generator emits or debug build synthesizes a callable harness entry
- CLI loads JSON args and calls the resolved target

Main requirement:

- a stable callable-resolution API from STAN or shared front-end extraction

### `--call "Class:method"`

Meaning:

- invoke an instance method

Lane:

- `Soon`

Implementation:

- same as function mode, but with receiver initialization

### `--call "Class::method"`

Meaning:

- invoke a static method directly

Lane:

- `Soon`

Implementation:

- simpler than instance-method mode because no receiver hydration is needed

### `--construct "Class"` + `--construct-args` + `--then-call`

Meaning:

- create an object, then call a method on it

Lane:

- `Later`

Why later:

- object construction and subsequent method invocation become a small execution script language
- very useful, but wider than the first harness slice

---

## 3. Input Shaping

### `--call-args "json"`

Meaning:

- provide positional call arguments from JSON

Lane:

- `Soon`

Implementation:

- parser in CLI
- debug harness converts JSON into raw runtime values

Important design note:

- raw JSON input and typed coercion should stay distinct concepts

### `--call-this "json"`

Meaning:

- provide receiver state for instance method calls

Lane:

- `Soon`

Implementation:

- similar to `--call-args`
- receiver hydration may initially be dynamic/raw rather than fully typed

### `--named-args "json"`

Meaning:

- provide arguments by parameter name

Lane:

- `Soon`

Implementation:

- needs STAN-backed parameter-name resolution
- more ergonomic than positional JSON once callable metadata exists

### `--call-args-file` / `--call-this-file`

Lane:

- `Now` once JSON-call mode exists

Why useful:

- easy replay
- easier diffs

### `--raw-json`

Meaning:

- interpret inputs as raw dynamic/runtime values only

Lane:

- `Soon`

### `--coerce-inputs`

Meaning:

- attempt STAN-assisted typed coercion

Lane:

- `Later`

Why later:

- coercion policy gets subtle quickly
- better as an explicit opt-in, not the default

### `--suggest-call-args`

Meaning:

- use STAN to suggest a JSON skeleton for the target callable

Lane:

- `Later`

Why valuable:

- extremely agent-friendly

---

## 4. Observation / Dumps

### `--dump "file:line:varname"`

Meaning:

- dump one local/visible value at a source location

Lane:

- `Soon`

Implementation:

- STAN resolves the source location and possibly visible locals
- generator injects a dump hook near the mapped source line
- runtime emits source-aware dump event

### `--dump "file:line:exact_expression"`

Meaning:

- dump not only a variable, but a full expression

Lane:

- `Later`

Why later:

- expression capture/instrumentation is much more complex than variable capture
- needs careful lowering so evaluation does not change behavior

### `--dump-before` / `--dump-after`

Meaning:

- disambiguate whether the observation happens before or after the line executes

Lane:

- `Soon`

Implementation:

- instrumentation placement choice in lowered/generated debug harness

### `--dump-type`

Meaning:

- dump only the inferred/runtime type info

Lane:

- `Soon`

Implementation:

- leverage current runtime type-shape support used by `dbg(...)`

### `--dump-shape`

Meaning:

- structured container/object shape dump

Lane:

- `Soon`

Implementation:

- directly aligned with `DBG_SHAPE`, `DBG_DEPTH_*`, `DBG_KEYS`

### `--dump-stack`

Lane:

- `Later`

Why later:

- current runtime error flow has trace data
- but a general stack-dump feature at arbitrary debug points is a bigger runtime feature

### `--dump-locals "file:line"`

Lane:

- `Later`

Why later:

- needs local symbol visibility and runtime capture contract
- STAN may know names, but runtime still needs to expose actual values

---

## 5. Break / Exit / Stop Control

### `--exit "file:line"`

Meaning:

- terminate once execution reaches a source location

Lane:

- `Soon`

Implementation:

- source-mapped instrumentation hook that throws/returns controlled termination once hit

This is one of the most agent-friendly features because it avoids long noisy runs.

### `--break "file:line"`

Meaning:

- pause logically, dump requested observations, then either stop or continue

Lane:

- `Soon`

Implementation:

- probably implemented first as:
  - hit location
  - emit event(s)
  - optionally terminate

True interactive pause can come later.

### `--break-on-call`

Lane:

- `Later`

Why later:

- needs call-entry hooks with callable identity instrumentation

### `--break-on-return`

Lane:

- `Later`

### `--break-on-throw`

Lane:

- `Soon`

Implementation:

- piggyback on current runtime error reporting path
- easier than arbitrary stepping because the throw path already centralizes failure

### `--break-on-warning`

Lane:

- `Later`

Why later:

- depends on which warning families are runtime vs STAN vs compiler

---

## 6. Conditional Debugging

### `--when "expr"`

Meaning:

- gate a dump/break/exit on a condition

Lane:

- `Later`

Why later:

- requires safe evaluation context for arbitrary expressions

### `--break-when-changed "expr"`

Lane:

- `Later`

### `--break-when-equals "expr:json"`

Lane:

- `Later`

### `--sample-every N`

Meaning:

- reduce event volume in loops/hot paths

Lane:

- `Soon`

Implementation:

- mostly runtime event throttling once trace/dump hooks exist

---

## 7. Trace Families

### `--trace-calls`

Meaning:

- emit call entry/exit events

Lane:

- `Later`

Main requirement:

- generated hook insertion or runtime call wrappers that preserve source-call identity

### `--trace-returns`

Lane:

- `Later`

### `--trace-lines`

Lane:

- `Later`

Why later:

- source-line stepping/tracing can get very expensive/noisy

### `--trace-runtime-casts`

Meaning:

- emit events at strict typed-boundary cast sites

Lane:

- `Soon`

Why useful:

- extremely aligned with common Simple C++ debugging pain

Implementation:

- generator/runtime already know where typed boundaries exist
- lower small debug hooks around cast/check boundaries

### `--trace-json`

Meaning:

- emit decode/encode boundary events

Lane:

- `Soon`

### `--trace-mysqli`

Meaning:

- emit DB-call and maybe result-shape events

Lane:

- `Later`

### `--trace-sql`

Meaning:

- emit SQL statement text and maybe row counts

Lane:

- `Later`

Very valuable for ORM work, but needs a stable DB wrapper interception contract.

### `--trace-filesystem`

Lane:

- `Later`

---

## 8. Output Format

### `--format text|json|ndjson`

Lane:

- `Soon`

Why important:

- `text` for humans
- `json` for structured single-result output
- `ndjson` for streaming event consumers and AI agents

### `--output file`

Lane:

- `Now`

### `--summary`

Lane:

- `Now`

### `--timeline`

Lane:

- `Soon`

Implementation:

- postprocess structured event sequence

### `--redact "pattern"`

Lane:

- `Later`

Useful, but not required for the first debug loop.

---

## 9. Replay / Session Artifacts

### `--save-session`

Meaning:

- save the current debug configuration

Lane:

- `Now`

Implementation:

- this is mostly CLI serialization

### `--load-session`

Lane:

- `Now`

### `--save-inputs`

Lane:

- `Now`

### `--record-run`

Meaning:

- save emitted debug events

Lane:

- `Soon`

### `--replay-run`

Lane:

- `Later`

Why later:

- replay semantics can mean either:
  - rerun with the same inputs
  - fully replay event log without running

Those should be separated carefully.

### `--snapshot` / `--restore-snapshot`

Lane:

- `Later`

Why later:

- true heap/object graph snapshots are much deeper runtime work

---

## 10. STAN-Assisted Discovery

This is one of the most exciting families because current STAN already has symbol and source context machinery.

### `--list-callables`

Lane:

- `Soon`

Implementation:

- expose callable index from STAN/session metadata

### `--explain-call "target"`

Meaning:

- show callable signature, receiver requirement, parameter list, maybe resolved file

Lane:

- `Soon`

### `--validate-dump "file:line:expr"`

Meaning:

- preflight whether a requested dump target is even meaningful

Lane:

- `Soon`

### `--list-locals "file:line"`

Lane:

- `Later`

Why later:

- STAN may be able to approximate visible names, but this likely needs richer local-scope export

### `--suggest-this-shape`

Lane:

- `Later`

### `--complete "prefix"`

Lane:

- `Later`

This starts to overlap with editor experience, but it could still help CLI-driven agents.

---

## 11. AI-Native Features

These are more speculative, but still worth naming explicitly.

### `--goal "why is X null?"`

Meaning:

- give the tool a user intent, not only mechanics

Lane:

- `Later`

Why:

- this becomes a meta-debug assistant, not just an event emitter

### `--suggest-next-dump`

Lane:

- `Later`

### `--minimize-repro`

Meaning:

- try to shrink a failing call/input/session to a smaller reproducer

Lane:

- `Later`

Very powerful if ever built well.

### `--produce-issue-draft`

Lane:

- `Later`

Could be extremely useful in a toolchain with good structured diagnostics.

---

## What Looks Most Buildable With Current Pieces

These are the strongest current candidates because they align well with the existing architecture.

### Best `Now` candidates

1. `scpp debug --args ...`
2. `--env`
3. `--stdin-file`
4. `--output`
5. `--summary`
6. `--save-session`
7. `--load-session`
8. `--save-inputs`

These are mostly orchestration and artifact-shaping work.

### Best `Soon` candidates

1. `--exit "file:line"`
2. `--dump-before`
3. `--dump-after`
4. `--dump-type`
5. `--dump-shape`
6. `--trace-runtime-casts`
7. `--call "function"`
8. `--call "Class::method"`
9. `--call-args`
10. `--format json|ndjson`
11. `--list-callables`
12. `--explain-call`
13. `--validate-dump`

Why these are promising:

- STAN already has semantic indexing
- runtime/source mapping already exists
- `dbg(...)` proves source-aware runtime observation is viable
- `project_services.php` already owns orchestration concerns
- the generator already owns the source structure where instrumentation wants to live

## What Likely Needs New Runtime Support

These feel meaningfully bigger:

1. arbitrary expression dumping
2. local-variable enumeration at a source point
3. stack dump at arbitrary observation points
4. line-by-line tracing
5. call-entry/call-return tracing
6. heap/object snapshots
7. watchpoints on writes
8. full replay semantics

These are not bad ideas.
They just need more than current CLI/STAN glue.

## Main Implementation Owners By Layer

### CLI / command ownership

Likely owner:

- `bin/project_services.php`

Responsibilities:

- parse debug command/config
- load project
- coordinate STAN/build/run
- save session artifacts
- choose output format

### STAN ownership

Likely responsibilities:

- resolve callable identity
- resolve source file/line targets
- validate request shapes
- expose callable metadata
- maybe later expose local-scope/debug candidate metadata

### Generator ownership

Likely responsibilities:

- primary heavy-lifting owner for debug implementation
- inject source-aware debug instrumentation
- preserve source maps for instrumented code
- lower selected hooks around lines, casts, or debug targets
- emit temporary debug harness entrypoints
- emit structured event calls into runtime helpers

### Runtime ownership

Likely responsibilities:

- provide small helper APIs for structured debug events
- inspect runtime values
- maybe later expose stack/frame/object state
- maybe later enforce conditional/watchpoint logic

## A Useful First Design Principle

The debug system should be:

- explicit
- opt-in
- reproducible
- source-first
- structured

And it should avoid hiding too much magic behind “smart” input coercion in the first iterations.

Current preferred principle:

- generator-first instrumentation
- temporary instrumented builds for debug sessions
- source-first reporting through structured events
- raw JSON inputs first
- STAN-assisted explanation and validation early
- typed coercion later and explicitly

## Open Questions

1. Should the instrumented debug variant live under a separate debug build/cache area, or as a temporary overlay inside the usual project-local generated/build tree?
2. How much of callable resolution should be STAN-owned versus generator-owned?
3. Should dump targets be line-based only, or should statement ids / spans become first-class later?
4. Should JSON/NDJSON event output become the canonical internal debug event model even for text mode?
5. When `--call` is used, should the debug harness live as:
   - an ephemeral generated entrypoint
   - a runtime reflection-like dispatch table
   - a temporary generated project-side wrapper

## Current Recommended Next Step

The best next planning slice is probably not “implement all of this”.

The best next slice is:

1. define the debug event model
2. define callable resolution contract from STAN
3. define one source-location instrumentation contract
4. define one harness contract for `--call`

That would let later feature work stack cleanly instead of becoming ad-hoc.
