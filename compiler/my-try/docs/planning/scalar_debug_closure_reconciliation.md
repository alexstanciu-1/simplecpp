# Scalar debug and reference-capture reconciliation
Doc Status: planning

Goal completed 2026-10-21: implement bounded scalar `dbg` support (S08),
preserve the borrowed-closure restriction and verify the concrete-closure
adaptation (S05) on dev002 under default template prelowering. No runtime
implementation changes or full matrix rerun. **45/64 issues resolved (70.3125%)**,
19 open: **+3.125 percentage points**. This comprises 44 implemented/repaired
issues and one intentional-contract reconciliation (S05), not two capability fixes.

## Owners and scope

Runtime meaning stays in the existing `scpp::php::dbg_at` implementation and
strict debug contract in `specs/simple_cpp_php_strict_quick_learn.md`.
Callable meaning stays in `specs/language/closures.md`: borrowed environments
cannot cross erased callable boundaries, directly or transitively. Do not change
that rule to make the original v0.1 fixture pass.

S08 covers canonical int, float, bool and string values in value-only,
label/value and label/value/flags calls, with required debug constants, source
location and ordered once-only snapshots. Wider inspection, gated debug helpers
and debugger instrumentation remain separate. Existing runtime flags and error
reporting must be preserved.

S05 retains the original fixture as historical evidence. Removing only the
explicit callable storage annotation produces an inferred concrete closure that
retains its borrow information and observes later writes. Value capture is a
distinct positive control, not a behavior-preserving replacement.

## Initial dev002 evidence

The previously audited curl checkout `/home/alex-ai/curl/20261020-work` was used
without compiler/runtime edits. Probe results:

- `20261021-s08-s05-probe-01`: original and transitively borrowed erased
  closures reject during preparation. The diagnostic is the existing generic
  exact-signature boundary message; it does not precisely explain the borrow.
- The concrete adaptation builds under default prelowering and prints `2\n`.
- Owned-capture explicit `function<int()>` passes preparation but rejects
  publication: `prelower_templates: callable storage requires a concrete callback
  transport`. This independently confirms the previously documented concrete
  callable gap. It is not a borrowing-policy failure or a default-prelowering
  execution pass.
- The first direct runtime probe omitted native bitwise operator support when
  composing flags; preserve that harness failure. Corrected runtime-only probe
  `20261021-s08-runtime-02` composes integer flag values explicitly and passes.
  Original scalar output, bool/float/string values, source file/line and invalid
  depth combinations behave as the existing provider specifies. `dbg` catches
  invalid flags and prints an inspection-failure message; it does not propagate
  that configuration error to the outer program.

## Approved binding implementation

The permanent `tests/s05_reconciliation.py --results FRESH_DIRECTORY` runner now
passes all six checks in `20261021-s05-reconciliation-01`: three concrete programs
execute cold/warm (reference capture, owned capture and shared borrowed mutation),
two borrowed erased boundaries reject, and the separate owned erased backend gap
is explicitly asserted. All warm builds compile/link zero units. The last check
is a known-gap assertion, not a positive callable execution pass.

[Initial evidence](s08-s05-probes.tar.gz) retains these results, original probe
diagnostics and corrected runtime output, excluding native caches/binaries.

Existing C++ free-function metadata has only identity, symbol and header. Calling
`dbg` through it loses source context; `dbg_at` requires file/line arguments absent
from the source signature. Do not hardcode a source function name in the emitter,
pass generated C++ locations, or add hidden parameters to source callable types.

Implemented extension: explicit call-site context on the existing C++ binding,
consumed by the ordinary and compiled-operation call owners. Backend call-site
facts supply the PHS file/line, separately from ordered source arguments. Keep
metadata validation, specialization identity and replay sensitive to those facts.
No new metadata registry or runtime implementation was added. The user explicitly
approved this retained transport extension and keeping callback transport separate.

Keep concrete callback storage separate; report the owned-capture control's
prepared acceptance and backend rejection honestly. S05 reconciliation must not
claim that all erased callable forms execute under default prelowering.

## Final acceptance and accounting

`20261021-s08-final` passes 11 checks: nine default-prelowered native programs
cold/warm, incremental recovery and metadata validation. The unchanged original
fixture matches its exact output. Int/float/bool/string each exercise value-only,
label/value and label/value/flags forms. These 12 source forms use eight metadata
contracts, with the label form's optional flags supplied by ordinary defaults.
All 19 debug constants are exposed. A direct native label/int call was ambiguous
between label/value and value/flags overloads; explicitly supplying the existing
`DBG_DEFAULT` through metadata resolves it without runtime changes. The initial
failed build remains in the evidence.

Checks prove native scalar type/value reporting, label snapshots despite later
mutation, ordered once-only side effects (trace 123), source file/line, CRLF input,
source-function shadowing and invalid-depth inspection-failure reporting with
continued execution. Incremental whitespace moves line 2 to 4, matches a fresh
compile and remains byte-stable on no-op. Three invalid calls reject on edit and
retry, then repair to fresh output. Binding validation rejects invalid context
forms/reference inputs and preserves prior configuration; a renamed source
function uses the same context binding, proving no dbg-name branch.

`20261021-s05-final` re-verifies the six closure checks against the final code:
three concrete programs run cold/warm, direct and nested borrowed erasure reject,
and owned erased storage still reaches its separate backend transport rejection.
The user approved leaving that transport gap separate. The original v0.1 fixture
is unchanged; S05 is reconciled against the current normative restriction, with
the explicit-storage-removal adaptation verified. No erased-callable execution
or lifetime extension is claimed. Every warm native build compiles/links zero units.

`20261021-s08-regressions` passes runtime catalog, inventory, take and unsupported
boundary checks. The catalog fixture now inserts its test constants into the
existing core constants list rather than introducing a duplicate JSON property.
The shared runner uses CLI fallback because installed CLI/FPM versions differ.
No full matrix, runtime implementation change or native compiler rebuild ran.

All **5,352 source inputs** match dev002 with zero mismatches. The
[source delta](scalar_debug_source_delta.json) overlays the frozen tokenizer/IO
manifest. [Final evidence](scalar-debug-acceptance.tar.gz) includes final cases,
commands, exact outputs, generated publications, audit and historical attempts,
excluding native caches/binaries. SHA256:
`57c02976f96353cd53c98624fdc18b13c7c5aba53cf3bfe05d47d4ed5294535e`.

Wider debug values (including fixed-width, null, boxed, wrappers, containers and
objects), value/flags shorthand, gated helpers and debugger instrumentation remain
separate. The binding extension is bounded to free fixed-value calls. Core scalar
support does not claim that the full runtime debug surface is exposed. Concrete
callback storage remains the previously recorded prelowering follow-up.
