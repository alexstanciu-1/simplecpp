Doc Status: planning

# Runtime Diagnostics Main-Catch Remap Plan

## Summary

This note records the intended replacement for helper-based generated-location runtime diagnostics.

The target design is:

- generated code must not emit helper-specific location wrappers such as `cast_with_generated_location(...)`
- generated line maps must cover every generated line
- generated line maps must carry relation semantics, not only a bare source line number
- generated `main(...)` catch handling must recover the generated C++ file/line at catch time
- `scpp` report processing must remap that generated location back to the authoring `.phs` source line using `.line.tsv`

## Problem Statement

The repository previously relied on partial helper-based location capture for some runtime failures.

That model is incomplete because:

- only some generated call paths passed through the helper
- other real runtime failures reached the catch path without `generated_file` / `generated_line`
- source attribution then failed even when valid `.line.tsv` data existed for the generated file

The Open M3 repro demonstrated that gap clearly:

- the generated `.cpp` file and `.line.tsv` map existed
- the runtime error JSON still had `generated_file = null` and `generated_line = null`
- therefore PHP-side remapping never had a generated location to remap

## Design Goals

1. Remove helper-based runtime location capture permanently.
2. Guarantee that every generated line has a source relationship.
3. Preserve a single remap authority path through generated file/line plus `.line.tsv`.
4. Support real runtime failures, not only selected helper-mediated failures.
5. Avoid workarounds that depend on brittle message parsing.

## Generator Mapping Model

The generator currently stores one source line per emitted generated line.

That is too weak for synthetic lines such as:

- braces
- `else`
- `catch`
- temporary locals
- lambda wrappers
- synthetic scaffolding around expressions or statements

The new model should give every generated line an origin with a relation:

- `exact`
- `above`
- `below`
- `around`

Meaning:

- `exact`: the generated line directly corresponds to the source line
- `above`: the generated line is synthetic scaffolding that belongs immediately before the source line
- `below`: the generated line is synthetic scaffolding that belongs immediately after the source line
- `around`: the generated line belongs to a synthetic wrapper region around the source line

## Proposed `.line.tsv` Schema

Current:

```text
generated_line\toriginal_line
```

Proposed:

```text
generated_line\toriginal_line\trelation
```

Examples:

```text
1\t10\texact
2\t10\tabove
3\t10\taround
4\t11\texact
```

The generator must stop emitting `0` as a normal unmapped state for generated source lines.

If a generated line is emitted, it must be attached to the nearest valid source anchor using one of the supported relations.

## Catch-Time Runtime Location Recovery

The generated `main(...)` catch path should become the sole place where runtime failures are enriched with generated location data.

Required behavior:

1. catch the thrown exception in generated `main(...)`
2. inspect the native call stack
3. identify the first frame that belongs to the generated project source
4. attach:
   - `generated_file`
   - `generated_line`
   - optional `generated_column` if available
5. print the enriched structured JSON

This keeps generated-location discovery centralized and helper-free.

## PHP-Side Remap

`project_services.php` already owns the remap from generated file/line to original authoring line.

That layer should be extended to understand the richer `.line.tsv` row shape:

- `original_line`
- `relation`

Diagnostic presentation should use the relation:

- `exact`: report the normal source location
- `above` / `below`: report as near the source location
- `around`: report as around the source location

The saved report should preserve the relation explicitly so later tools do not need to guess.

## Build Requirements

Catch-time frame recovery requires native line information in debug builds.

Current debug builds use `-O0 -g0`, which disables debug line tables.

Debug builds used for runtime diagnostics should provide line information, for example with:

- `-g1`
- or `-g`

No release-mode requirement is introduced by this note.

## Implementation Areas

### Generator

- extend `CodeBlock` with line-map relation metadata
- update `flattenCodeLineMap(...)` into a richer line-map structure
- assign relation defaults consistently for synthetic lines
- update `.line.tsv` writing

### Runtime

- add catch-time generated-frame recovery support
- enrich structured runtime JSON with generated file/line at catch time
- keep helper-free cast generation

### CLI / Report Processing

- parse the richer `.line.tsv` format
- remap generated runtime locations to source using the relation-aware rows
- surface relation in saved diagnostics and short error rendering

### Tests

- update runtime diagnostics regression coverage
- forbid helper-based generated location wrappers
- verify relation-aware line map output
- verify the Open M3 repro now remaps to source successfully

## Non-Goals

- no fallback to helper-based location wrappers
- no source-level workaround in Open M3
- no concept change that bypasses the generated-file to `.line.tsv` remap model
