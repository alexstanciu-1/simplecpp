# Optional lowering diagnostics
Doc Status: supporting

The PHP S2S CLI accepts `--lowering-report PATH` and `--lowering-explain PATH`.
Both are opt-in JSON sidecars; normal artifact transport on stdout is unchanged.
Use distinct, new paths outside source/publication/build directories. The host
exclusively creates the destination and rejects an existing path rather than
replacing source or generated files. Parent directories must already exist.

```bash
php compiler/my-try/main.php --s2s /absolute/source --entry main.phs --optimization 0 \
  --output /absolute/published --lowering-explain /absolute/diagnostics/run-001.json
```

`CPP_Lowering_Report::encode(artifacts, program, policy, explain)` is the backend
report API for completed output. It reads final artifacts and existing fragments;
it performs no preparation, emission, publication, cache validation or native builds.
Call it only after successful generation with the corresponding program and policy.
The host owns file I/O. No report collection/traversal executes when flags are absent.
The [native report checkpoint](../planning/native_lowering_report_20261016.md)
validates the API through a rebuilt O2 compiler and an experiment `request.json`
driver on dev002. It does not add these PHP CLI flags to a production native CLI.

## Schema and interpretation

`scpp-lowering-report-v1` includes:

- Requested optimization and effective lowering policy identity.
- Every artifact's stable identity, path, kind, byte count, direct include metadata
  and direct generated consumers. Includes are null when metadata is unknown.
  Runtime/system transitive includes are not expanded; Clang depfiles own those.
- Existing fragments, named owner where available, declaration/function/file-body
  category, source path and associated implementation path (null if not emitted).
  Method placement uses the same source query as implementation-group assembly.
- Separate fragment, prototype, initializer and type-owned definition byte counts.
  These are retained section sizes, not additive attribution of final artifact bytes:
  declaration fragments usually live in headers; assembly adds wrappers/dispatch.
  Artifact byte counts are the exact final emitted sizes.
- Default-constructor outlining decision and whether shared allocation was selected.
  The decision comes from the actual eligibility predicate, not guessed C++ text.
  Rejection reasons are first-failing gates, not a list of all possible blockers.

Explain mode additionally includes `fragment_headers`, `definition_headers` and
`forward_declarations` per fragment. These show which named body's or declaration's
lowering contributes a dependency. A definition signature can require headers that
its public declaration does not. A forward declaration can be superseded by a
complete include contributed elsewhere during assembly.

The report explicitly declares `explanation_granularity: fragment-contribution`
and `operation_reasons_available: false`. It does **not** yet distinguish field
access, construction, inferred results or other operation-level reasons inside a
fragment. It also does not claim exhaustive provenance for assembly-added includes,
helper expansion or diagnostic locations. Those require further backend-owned
instrumentation if needed; they must not be reconstructed by guessing from text.

To investigate a slow unit, join its Clang command/timing to an artifact `path`,
inspect its exact size and direct includes, then inspect fragments associated with
that implementation path. Use direct generated consumers to follow header fan-out.
Size and include counts are structural clues, not predicted compile times. Profile
Clang separately to distinguish parsing/templates from LLVM optimization.

Reporting uses a reverse include index; it does not scan all artifact pairs.
Normal builds gain no report state and report files are not native dependencies.
The boolean constructor-eligibility query delegates to the same named decision used
by the report, preserving gate order and behavior. No splitting, grouping changes,
semantic incremental/MT changes or new cache ownership are introduced.

## Checkpoint — 2026-10-16

`python3 compiler/my-try/tests/lowering_report.py --results FRESH_DIRECTORY` covers
O0/O1/O2 output equivalence, independently calculated artifact bytes/dependencies,
constructor decisions, named-body header provenance, deterministic reports,
existing-file rejection, retained-generation purity and mismatched-policy rejection.
The shared runner used CLI fallback because sandbox FPM socket binding was unavailable.

All 292 accounting artifacts at each of O0 and O2 match the previous initializer-header
checkpoint byte-for-byte. Portable compiler source conversion passes with the new
report owner. Conversion alone is not native execution proof; the subsequent native checkpoint
is linked above. No new application performance benchmark was run. PHP evidence is in
[the checkpoint directory](../planning/lowering_report_20261016/).
