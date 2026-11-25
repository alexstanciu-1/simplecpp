# Concrete physical arguments through existing backend bindings
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `1d397d8e` plus
this slice's type-binding, lifecycle, layout-ordering and native-cache changes.

The architectural decision and plan now select recursive concrete type substitution
with ordinary C++ source fields/member operations. Cleanup is proof-gated: retain
registry/preparation, engine, lifecycle, publication and cache owners; retire old
source-call/property transport and handwritten recipes when replacements cover them.

## Implemented foundation

`CPP_Types::implementation_representation` reuses registered native spellings while
recursively selecting concrete physical arguments. `request_composed` uses the
existing specialization engine and lifecycle worker. No template-family algorithm,
semantic type, retained registry, AST or public lowering option was added.

Physical dependencies live in existing C++ binding/entry records. The engine emits
layout probes by dependency depth; the native builder resolves them in manifest
order through its existing cache, forcing prior headers into later probes. Names
use bounded readable labels plus stable digests, avoiding recursive filename growth.

The fixture obtains real canonical types from prepared calls. It requests integer,
vector, nested-vector and independently registered `parcel` representations. Native
implementations contain the selected concrete arguments, including actual mutable
and const nested-vector references. The fixture supplies its source record and
operation adapters as C++; ordinary source-field/member emission is **not** integrated
by this checkpoint. Source payload arguments explicitly reject on this new API path.
Existing experimental expression emission remains available and unchanged.

## Validation

```bash
python3 compiler/my-try/tests/prelower_substitution.py --results FRESH_DIRECTORY
python3 compiler/my-try/tests/prelower_layout.py --results FRESH_DIRECTORY
```

Direct runtime types and production-generated concrete bindings are separately
compiled at `-O0`, `-O2`, and ASan/UBSan `-O1 -fno-elide-constructors`; no PCH or debug
symbols. LeakSanitizer is disabled. Exact counter equality covers record copy/move,
nested mutation, native references, and throwing-copy cleanup after earlier fields
have been copied. Live-payload assertions check cleanup. Counter columns are
allocation count, allocated bytes, frees, constructions, copies, moves, destructions:

```
workload     12 784 12 2 4 1 7
references    0   0  0 0 0 0 0
copy-failure  4 312  4 0 1 0 1
```

Reversed and repeated request order produces identical artifacts without changing
preparation version. Three dependent layout batches are reused on warm builds and
body-only edits. Provider alignment changes invalidate affected native/layout work;
restoring original content reuses the original objects, layout headers and executable.
This is request replay and native edit/cache proof, not full incremental frontend
fragment integration of the composed path.

Focused PHP suites: type catalog, runtime types/catalog, constructors and ownership.
The portable-PHP checker passes for changed production files; it is not native compiler
validation. Shared runner: 12 slots, FPM attempted, CLI fallback because sandbox
socket creation is unavailable. Existing layout regression covers the original API,
cache repair, body/alignment edits, recovery and sanitizer execution.

Initial attempts exposed fixture aggregate/alignment mistakes, an unsupported PHP
constructor container default, recursive filename growth, and a duplicate runner
command label at restoration. These were corrected before the final proof. No
runtime behavior was changed to satisfy counters. The separate composition experiment
contains the copy-insertion negative control demonstrating counter sensitivity.

Raw final evidence: `/tmp/prelower-substitution-final`,
`/tmp/prelower-substitution-layout-final`, `/tmp/prelower-substitution-final-checks`,
and the earlier focused checks in `/tmp/prelower-substitution-checks*`.
[summary.json](summary.json) preserves exact counters and cache results. These are
correctness/cache measurements, not a new compile-time speed benchmark.

## Remaining boundary

Next integrate source declaration fields/signatures and their dependency graphs,
then ordinary member/operation interfaces from existing bindings and prepared facts.
Provider compatibility, recursive source graphs and whole-accounting coverage remain
open. Prior-batch layout headers conservatively couple cache actions; exact per-entry
partitioning is deferred. No extra boundary allocations/copies were observed for
these tested types and operations; this does not establish coverage of all templates.
