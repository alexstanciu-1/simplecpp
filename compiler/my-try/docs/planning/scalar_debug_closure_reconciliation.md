# Scalar debug and reference-capture reconciliation
Doc Status: planning

Goal authorized 2026-10-21: implement bounded scalar `dbg` support (S08),
preserve the borrowed-closure restriction and verify the concrete-closure
adaptation (S05) on dev002 under default template prelowering. No runtime
implementation changes or full matrix rerun. Baseline: 43/64 closed; neither
issue is closed by these initial probes.

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

## Bounded binding prerequisite under discussion

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

Proposed extension: explicit call-site context on the existing C++ binding,
consumed by the ordinary and compiled-operation call owners. Backend call-site
facts supply the PHS file/line, separately from ordered source arguments. Keep
metadata validation, specialization identity and replay sensitive to those facts.
No new metadata registry or runtime implementation is needed. User approval for
this specific retained transport extension was requested after the probes.

Keep concrete callback storage separate; report the owned-capture control's
prepared acceptance and backend rejection honestly. S05 reconciliation must not
claim that all erased callable forms execute under default prelowering.

## Remaining acceptance

Implement the approved binding path and scalar registrations; verify the unchanged
debug fixture and ordered side effects, native scalar type reporting, source
locations, flags and unsupported value rejection. Run cold/warm builds and
rejection/repair/no-op checks through the shared runner on dev002. Complete
concrete-closure mutation/lifetime controls and preserve original borrowed
rejections. Update the ledger only after the bounded scope is verified, separating
one capability repair from one intentional-contract reconciliation.
