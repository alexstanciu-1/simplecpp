# Concrete indexed compound updates
Doc Status: planning

## Scope and ownership

`CPP_Index_Write` retains sequencing ownership and consumes the existing prepared
path, key conversions, binary decision and write-back conversion. Concrete mode
uses demand-driven overloads of one-step `index_existing_probe`, existing mutable
descent and store helpers. It snapshots the leaf before source RHS evaluation,
then revalidates every saved segment before any mutable descent. No interior
reference survives the RHS. Runtime storage/COW and operator algorithms remain
unchanged; there is no family-specific engine dispatch or second operator table.
Ordinary emission retains its existing variadic runtime read/store helpers.

The runtime vector existing-element helper now accepts the established integer
capability, matching ordinary indexed reads/writes, instead of requiring an exact
`int_t<>` parameter. This admits concrete integer representations without a temporary
native key. No source admission changes, AST changes or `_02` edits are involved.
Boxed stored-kind write-back and broader provider interfaces are separate work.

## Reproduction

```sh
python3 compiler/my-try/tests/prelower_substitution.py --compound --results /tmp/prelower-compound-FRESH
python3 compiler/my-try/tests/index_compound.py --results /tmp/prelower-compound-regression-FRESH
```

The compound mode includes the cast, binary, initializer, indexing and nested-reference
proofs. New cases check exact key/RHS order, vector growth during the RHS, nested
container replacement with a valid saved path, independent copied containers, integer
hash updates and long-string concatenation. Missing entries suppress RHS effects;
RHS deletion fails without recreating the entry; division failure leaves the old leaf
unchanged. Exception diagnostics are outside allocation measurement because runtime
stack capture may allocate differently with bridge frames.

Matched direct/concrete O0/O2 and ASan/UBSan builds disable debug/PCH; the sanitizer
build also disables optional elision. The shared runner uses CLI fallback because
sandbox FPM sockets are unavailable. Generated-program execution is not a native
compiler rebuild or a compile-speed benchmark.

## Accounting and limits

Both indexed compound failures disappear. Independent fragment probes emit 282/285;
a subsequent container-operation failure is exposed in GeneralLedger::validate.
The other remaining failures are SalesService::total_outstanding (container operation)
and PurchasingBook::bill_key (strlen). Whole accounting still stops before Clang;
nominal shared-template exposure remains. No complete concrete build is claimed.

During proof development, one expected effect marker was corrected (the existing
inner-key fixture records 4). The portability checker rejected implode; portable
key-string assembly replaced it. The fixture also exposed independent count(hash),
count(vector) and scalar increment interface gaps. Presence/index-value checks and
ordinary addition keep this compound proof within established surrounding interfaces;
those production gaps were not patched or hidden by family-specific adapters.

Full logs: `/tmp/prelower-concrete-compound-5/logs`,
`/tmp/prelower-compound-regression-1/logs`, `/tmp/prelower-concrete-compound-checks/logs`.
Accounting uses the existing coverage probe scripts against unchanged source.

## Allocation observability and optimized limitation

O0 and sanitizer compound counters match: 47 allocations / 3127 bytes / 47 frees,
with no tracked payload lifecycle activity. Stock O2 differs: direct uses 44 / 3048 /
44 while concrete uses 47 / 3127 / 47. Thus the optimized concrete workload performs
three additional allocations (79 bytes); this is a real measured limitation.

Diagnostic direct O2 rebuilds with either `-fno-builtin` or
`-fno-assume-sane-operator-new` restore 47 / 3127 / 47. This supports allocation
elision, rather than added transport construction, as the mechanism. The proof now
also builds both variants at O2 with `-fno-assume-sane-operator-new` and optional
elision disabled, requiring exact boundary counter parity. Stock O2 remains a normal
build/run, with exact payload lifecycle and inherited counter comparison; its compound
allocation delta is recorded explicitly instead of treating total parity as achieved.
This flag is test instrumentation only, not a production build setting or a fix.

The original full-parity assertion correctly failed after all behavior/sanitizer
executions passed (126.56 s). Diagnostic logs are under
`/tmp/prelower-compound-optimizer/logs`. Optimized allocation parity is still open;
future ThinLTO may help, but this slice makes no such claim.

## Final verification

All direct/concrete behavior, sanitizer and controlled boundary comparisons pass.
The ordinary indexed-compound/indexing regression passes, including mixed/dynamic
semantics, and both changed production PHP files pass portability checking.
Demand removal/restoration, discovery-order replay and option switching pass.
Body edit rebuilds one unit, reuses five and all three layout batches. Layout edit
rebuilds six units and one layout batch, reusing two. Restore rebuilds/links nothing
and reuses all three layout batches.

The last build phase took 158.05 s and stopped at an overly broad cross-configuration
assertion: disabling optional elision changes an inherited tracked move count.
The harness was corrected to compare matched configurations fully, and the compound
counter row across those configurations. All saved executable outputs were rechecked;
the remaining incremental work then passed in 37.20 s via the shared runner
(`/tmp/prelower-concrete-compound-5/finish.py`, `continuation-logs/`). No executable
was patched and no behavioral assertion was removed. The reproduction command above
uses the corrected comparison from the outset.

[Summary](summary.json) retains full-output hashes, compact counters, native cache
results and accounting failures. Stock O2 allocation parity is explicitly not achieved.
