# Runtime-core logical and selection boundary audit
Doc Status: planning

## Accepted decisions — 2026-10-28

The user confirmed both decisions: preserve guarded logical operators for
bool/numeric carrier payloads (unavailable payloads throw when evaluated), and keep
source eligibility/formation/boundary validation compiler-owned. The public API
generator consumes prepared facts; no shared semantic-compiler extraction is
approved. Direct-condition truthiness remains separate. Historical references below
to these decisions being pending are superseded; implementation and carrier sign-off
remain open. Non-numeric logical admission still requires its own contract audit.


Checkpoint: 2026-10-27, baseline `09dff7d73`.
Owner: [runtime-core completion](runtime_core_completion.md), batch C.
This records an implementation mismatch and the next proof boundaries. It does not
change language semantics or authorize general frontend admission.

## Four distinct operations

| Operation | Existing authority | Required public planning behavior |
| --- | --- | --- |
| Direct condition | `conditional_expression_matrix.md`, supported condition domain | Dispatch carrier state, then delegate present payload truthiness; absence/error/false are false, true sentinel is true. |
| Guarded logical operators | `runtime/specs/spec.md` sections 6.2 and 6.11; runtime operator config | For admitted bool/numeric carrier operators, require payload at the point that operand actually executes. An unavailable payload throws. |
| Coalescing | `conditional_expression_matrix.md`, homogeneous scalar amendment; `error_values_and_take.md` | Observe usable payload, not truthiness. Preserve successful zero/false/empty string; evaluate fallback only if needed. Reject result_or_bool even when unselected. |
| Ternary/Elvis | `conditional_expression_matrix.md`, exact join tables | Condition conversion and result-branch joining are separate. Preserve admitted wrapper result shape; only the selected branch executes. |

The top-level conditional matrix explicitly warns that condition rules do not
redefine logical-operator admission or guarded unwrapping. The lower runtime spec
likewise requires checked bool/numeric wrapper logical operands. There is no
permission to infer all logical operators from every condition helper.

## Discovered mismatch in the new public resolver

`runtime/tools/api_generator/operators.cpp` routes every carrier `logical_not` with
`condition_operand` through `resolve_condition`. This makes absent nullable numeric
and failed/sentinel numeric Results yield a boolean instead of failing extraction.
C3/C15 tests in `check_carrier_projection.py` and `check_sentinel_carriers.py` encode
that behavior, so their green results do not prove preservation of the existing
logical operator contract. Their direct-condition and cast proofs remain useful.

Read-only compiler evidence supports the distinction: `Operator_Preparation::decide`
tries `Guarded_Operators` before ordinary unary/logical preparation. The guarded
worker admits canonical bool/int/float payloads for logical operations. Its string
exception excludes string logical negation from guarded extraction; ordinary nullable
condition negation follows a separate path. Implementation evidence does not itself
broaden source admission, particularly for nested carriers and other Result payloads.

The user has been asked whether to preserve guarded logical rules (recommended) or
explicitly revise contracts to unify them with direct conditions. The question is
pending. No semantic or implementation change has been made on that assumption.
The generic state-aware condition resolver must remain separate either way.

## Proposed correction and proof sequence

1. Settle the logical policy and record it in the owning contracts if changed.
2. Give logical operation metadata explicit admission/projection policy. Reuse the
   existing one-layer projection worker for guarded operands and condition plans only
   for explicitly admitted condition-negation cases. Do not add a C++ type-name test.
3. Plan lazy conjunction/disjunction with a conditional RHS stage. Validate both
   operand types before execution; a skipped operand may avoid a runtime failure but
   must not evade static admission. Return plain boolean, not either source operand.
4. Prove the selected policy with absent nullable, failed Result, both bool sentinels,
   present false/zero, unsupported RHS type, invalid boxed runtime kind, source effects
   and an RHS that must not execute. Keep runtime conversion inside the executing
   branch rather than passing an eagerly evaluated RHS into a helper.
5. Correct the earlier negation fixtures and completion claims; retain separate tests
   demonstrating direct conditions and explicit bool casts are different operations.

This is a local operator/condition metadata ownership repair, not extraction of the
compiler's general semantic model. New source rules or a cross-owner redesign remain
a discussion gate.

## Independent coalescing work remains available

The normative homogeneous canonical scalar cases are explicit: nullable, result and
result_or_false with the same scalar payload select an owned payload, while
result_or_bool rejects. Both branch types validate even if the left is always usable.
A first implementation can prove those exact rows plus admitted wrapper/plain scalar
fallbacks through one metadata policy, without resolving general record, ownership,
boxed, cross-family or contextual-literal joins. Those wider included contracts remain
backlog; a first scalar proof must not count them complete.

Required cases: zero/false/empty-string success skips fallback; absent/error/false
selects it once; both unavailable throw the documented selected-branch error; result
is independent of later source mutation; valid nested Result-of-nullable states are
not flattened implicitly. No eager fallback arguments or public template callback
API should be introduced merely to express compiler-owned branch execution.

## Scoped observation gap remains real

The condition resolver requires owned extraction for nonconstant carrier payloads.
C27 added explicit constant leaf observations without payload extraction; that closes
the inline-record constant case, not general scoped observation.
The inline-record condition amendment explicitly supports observation without copying
or consuming even noncopyable records. A physical copying trait cannot substitute for
that observation capability. The scoped-observation slice must represent the specific
non-escaping operation and prove lifetime/alias behavior; merely removing the copy
requirement would leave an unusable or unsafe call path. This remains unfinished C/D
work, not a reason to infer generic source argument eligibility from native traits.
