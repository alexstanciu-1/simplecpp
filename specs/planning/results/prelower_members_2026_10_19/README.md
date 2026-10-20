# Ordinary concrete runtime member forwarding
Doc Status: planning

Date: 2026-10-19. Candidate: `efd93bba` plus this commit's member-forwarding changes.

`CPP_Concrete_Members` consumes existing prepared signatures, argument boundaries
and runtime callable bindings. Existing fragment operation requests drive demanded
member declarations in concrete classes and definitions in support output. No new
AST, semantic registry or container-family branches were added. The core registry
now exposes the runtime's existing `vector::append` member.

Proof commands:

```sh
python3 compiler/my-try/tests/prelower_substitution.py --members --results /tmp/prelower-members-verified
python3 compiler/my-try/tests/prelower_layout.py --results /tmp/prelower-members-legacy-layout
```

Both passed (74.41s and 63.83s respectively). The shared runner used CLI fallback
because this sandbox disallows PHP-FPM Unix socket binding. The portability checker
also passed for the six touched backend PHP files through the shared runner
(`/tmp/prelower-member-portable-final`). This is generated-program native evidence,
not a native rebuild of the compiler.

The compiler emits ordinary calls on `vector<int>`, `vector<vector<int>>`,
`vector<receipt>` and independently registered `parcel<vector<int>>`. The fixture
uses both copied lvalue snapshots and consumed source-function results. Forwarders
preserve those categories with reference parameters; native overload selection and
native by-value parameter work remain unchanged.

All direct/concrete O0, O2 and ASan/UBSan runs (optional elision disabled in the
sanitizer build) produced these identical counters:

| Scenario | Allocations | Bytes | Frees | Constructed | Copied | Moved | Destroyed |
|---|---:|---:|---:|---:|---:|---:|---:|
| Members | 11 | 520 | 11 | 1 | 4 | 1 | 6 |
| Existing record workload | 15 | 832 | 15 | 2 | 4 | 1 | 7 |
| Nested references | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| Existing throwing-copy cleanup | 5 | 328 | 5 | 0 | 1 | 0 | 1 |

Thus the tested forwarding boundary adds zero measured allocations, copies or moves.
Assertions also check integer append results, nested contents, parcel replacement,
tracked-vector size and final payload lifetime balance. Existing nested-reference
identity and record-copy failure proofs remain active; this does not claim new
member-specific exception or multiargument side-effect coverage.

No-op fragment replay, changed request removal/restoration, lowering-option switching,
and reversed type-demand order preserve the expected artifacts. Warm builds compile
and link nothing. A body-only edit compiles one application object, reuses five
objects and all three layout batches. A provider alignment edit rebuilds affected
layouts; restoration reuses every object and layout without linking. Full counters
and build-cache results are in [summary.json](summary.json).

Scope: void-returning members with physically compatible substituted parameters.
Result/reference-return and variadic transport explicitly reject. Unrelated fixed
native parameter adaptation, const receivers, ownership-wrapper interfaces, general
indexing/operators/initialization and whole-accounting coverage remain open.
The previous comparison paths are retained. ThinLTO is future release-build work;
this proof uses no PCH or LTO and establishes behavior/counters, not a new speed ratio.
