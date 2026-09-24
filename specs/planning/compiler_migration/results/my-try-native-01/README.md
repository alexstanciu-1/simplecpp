# First native compiler checkpoint
Doc Status: planning

2026-09-24. Candidate: PR #244 revision
`d8ddde93b04d0e23d295e30f662c3a81b0d50fd1`, with local checked-cast,
instanceof/interface, and reserved-call STAN overlays. This is not a released or
newly pinned target. `candidate.json` fingerprints the actual tested implementation;
`candidate-overlay.patch` is against that exact upstream tarball.

## Passing proofs

The fresh candidate harness runs normal STAN, native compilation and execution:

```sh
python3 tests/portability/native_collections.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results /tmp/scpp-native-collection-final
```

All three traces match independent expected results and PHP:

- Storage: holes, exact string keys, record mutation and shared membership.
- Object hashes: identity keys, retained old record, replacement and removal.
- Casts: successful narrowing preserves identity; instanceof handles null and mismatch.

Storage aliasing is tested through membership effects. Collection `===` is not
implemented by the native wrapper; the initial fixture exposed that limitation.
Derived records must first be stabilized as interface handles before conversion
to nullable interface parameters (C++ cannot chain both implicit conversions).
Cast rejection through PHP-compatible catch clauses remains unproved; the runtime
currently throws runtime_error, whereas the framework uses its own exception graph.

`commands.json` records elapsed times for the fresh verification builds. Exploratory
attempts preceded these: Storage needed one fixture correction; casts needed STAN
registration and the explicit interface boundary; hashes passed their first C++
compile after runtime assembly. They are not counted as clean first-attempt passes.

## Full compiler: blocked, not a native success

The assembly contains 29 production PHP inputs plus a sample driver. Conversion
completes; framework installation adds four files, giving 34 transpilation inputs.
Source hashes, driver and diagnostics are saved alongside this note.

Normal build stopped at 79 STAN diagnostics, chiefly worker required-field reads
whose initialization occurs in other methods. The saved STAN log is the initial
attempt; its count is not a final count after source adaptations.

Diagnostic-only `build --no-stan` reached C++ compilation after correcting branch
local scope and replacing throw expressions with statement-level guards/dispatch.
This bypass does not establish an accepted build. C++ diagnostics include cascades
and per-file error limits; their number is not a count of independent defects.

Remaining ownership areas:

1. **Source typing / Storage lowering:** nested, cross-file collection receivers
   can lose template metadata, producing `->append` or raw `[]` against a native
   value wrapper. Prove a two-file typed-receiver case before changing all call sites.
2. **Source object boundaries:** nullable interface parameters need explicit base
   handles. Generated local names can shadow record types (`$file = new file()`).
3. **Generator / framework:** enum name/value access, exception type qualification,
   and nullable coalescing need focused reproductions. Fix their actual owners;
   do not patch generated C++ or add broad semantic inference to the generator.
4. **Worker lifecycle / STAN:** Tokenizer, Parser, Template_Checker,
   LLVM_Preparation and LLVM_Generator use populate-then-consume fields. This needs
   an explicit lifecycle decision, not dummy objects or fake nullable fields.

## Proposed next structural slice

First isolate nested Storage lowering in a two-file fixture. Separately, establish
constructor-supplied required inputs on Tokenizer, preserving its documented reuse
and reset behavior. Then review Parser's per-parse state before extending the pattern
to analysis and LLVM workers. Per-invocation contexts should be initialized with real
inputs; no fabricated valid-looking AST/scope/output records.

A simultaneous lifecycle redesign across all five workers would cross ownership
areas and requires confirmation under the repository AGENTS.md. The alternative
is adding interprocedural initialization analysis to STAN, a wider compiler feature
outside this portability slice. The smaller recommendation is one worker at a time,
with PHP reuse/failure tests, normal STAN and native stage parity at each boundary.

PHP AST/model/LLVM tests passed after source adaptations; 19 LLVM outputs matched
the prior PHP checkpoint byte-for-byte. No successful native compiler executable or
native-generated sample output is claimed. No verified-target pin was changed.
