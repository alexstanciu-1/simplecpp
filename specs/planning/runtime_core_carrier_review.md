# Runtime-core carrier completion review
Doc Status: planning

## Accepted decisions — 2026-10-28

The user confirmed both decisions: preserve guarded logical operators for
bool/numeric carrier payloads (unavailable payloads throw when evaluated), and keep
source eligibility/formation/boundary validation compiler-owned. The public API
generator consumes prepared facts; no shared semantic-compiler extraction is
approved. Direct-condition truthiness remains separate. Historical references below
to these decisions being pending are superseded; implementation and carrier sign-off
remain open. Non-numeric logical admission still requires its own contract audit.


Review baseline: `0158f5b0b`, 2026-10-27.
Owner: [runtime-core completion](runtime_core_completion.md), batch C.
Result: **the first complete carrier path is not yet ready for sign-off**.
This review does not authorize ownership/callback expansion or alter source rules.

## What the current evidence establishes

The source of truth for physical generation is `runtime/metadata/public_api.json`;
carrier projection, condition and selection planning live in
`runtime/tools/api_generator/{carriers,conditions,selections,operators}.cpp`.
The following is a current acceptance map, not a count of historical checkpoints.

| Requirement | Current evidence | Remaining acceptance work |
| --- | --- | --- |
| Four distinct carrier state models | Nullable, Result and both sentinel recipes; checked payload constructors/extraction, state factories and state-aware take tests | Audit admitted payload domains against formation facts, not just native probes. |
| Conditional storage and unavailable copying | `check_conditional_lifecycle.py`, `check_sentinel_carriers.py`; vector/hash now reuse capability selection | Copy-only native concepts and finer operation requirements remain limitations. Do not advertise arbitrary source-type coverage. |
| One-layer arithmetic, update and compounds | Projection, unary, compound, special and power plan/execution proofs | These establish bounded admitted domains, not arbitrary nested lifting or frontend adoption. |
| Equality/identity/order preserve the agreed distinctions | Carrier equality/order proofs, original-state dispatch and checked ordering projection | Keep literal/sentinel routes and payload admission in the final closure matrix. |
| Required casts versus direct conditions | Conversion/projection tests and state-aware condition resolver | Required bool casts must still require payload; observation must not accidentally introduce copying. |
| No-copy observations | C27 explicit constant leaf facts avoid extraction for inline-record conditions | C28 covers callable payload conditions; C29 composes nested carrier states, including a noncopyable record fixture with explicit facts. Source fact production remains separate. |
| Guarded logical operators | Existing contract/compiler audit in the logical-selection note | Known mismatch: new carrier logical_not delegates to general condition conversion. The guarded contract is now confirmed; correction evidence is tracked in C32. C33 supplies canonical lazy and/or plans; non-numeric admission remains. |
| Lazy usable-value coalescing | C25 exact scalar policy and short-circuit execution proofs | C31 adds exact mixed/dynamic plain pairs and carrier-left/plain-boxed fallbacks, with distinct selected-value validity. Broader joins remain; result_or_bool rejection is intentional. |
| Lazy ternary/Elvis with preserved result state | C26 exact scalar branch joins and short-circuit execution proofs | C30 also covers exact mixed/dynamic joins. Admitted record, ownership and contextual joins still need their own policies/proofs. |
| Source formation and publication | Physical probes, canonical demands, sealed specialization reuse | Source definition/application/boundary facts remain distinct from physical storage facts. Compiler-owned validation with prepared facts is confirmed; consumer implementation remains. |

The review reran the 13 registered carrier/projection/lifecycle/selection/constant
metadata tests on the release generator: **13 passed in 24.21s**, with no compilation.
These CTest invocations omit `--core`: they are planning/shape tests, **not fresh
compiled execution, allocation or sanitizer evidence**. Earlier compiled evidence
remains recorded per slice in `runtime/specs/public_api_boundary_results.md`.
No broad closure claim is based on this rerun.

## Scoped condition observation — first callable slice implemented in C28

The native nullable and Result implementations already expose checked `const T&`
through their internal `value()`/`require_value()` members. Public wrappers instead
return owned values. Keep native access private and generate a named, operation-
specific observation method returning only boolean. Do not expose a payload pointer,
reference, generic visitor, or a templated callback in the public header.

The existing condition function set remains the authority for callable payload
conditions. Recipe metadata should declare the internal checked payload access and
request the condition operation; the generator should resolve that dependency and
emit the ordinary method through its existing operation emitter. It must not add a
family/type-name switch, duplicate the scalar condition table, or invent truthiness
from copy/move traits. Capability absence must omit/reject the observation rather
than force an invalid instantiation.

A caller-side carrier condition still evaluates its source once and observes the
outer state first. Only the payload branch calls the scoped observation method.
Absent/error/sentinel branches retain the current explicit condition values. The
constant condition shortcut remains valid and need not acquire a method or payload
access. Native references stay inside the call; the returned boolean owns no state.
The logical-operator question is separate and must not silently be resolved by this
optimization.

Acceptance proof for the first nonconstant observation path:

- all four carrier families, present long string versus empty/"0" payload;
- absence, structured failure and both sentinels retain direct-condition semantics;
- no allocation or payload copy during repeated observations, setup counted separately;
- source expression executes once; thrown source evaluation remains observable;
- nested delegation is either truthfully implemented or explicitly remains a gap;
- unsupported payload conditions reject even when the outer state is absent;
- public-only compilation, lifetime/sanitizer proof, generated dependency publication
  and application-owned reuse; no unrestricted borrow escapes.

C28 implements the callable-payload portion through the existing condition registry
and operation emitter; see public_api_generation.md for the resulting schema.
C29 implements nested scoped delegation through the same condition/state plan.
The remaining source-fact ownership decision is not resolved by those physical proofs.
If nested delegation requires a separate semantic owner, stop and report that boundary
rather than turning this into another independent type registry.

## Confirmed decisions and remaining review

Both decisions above were accepted on 2026-10-28. C32 corrects canonical bool/numeric
logical negation through checked projection. C33 supplies canonical lazy and/or plans. Non-numeric logical admission still
needs its explicit contract audit and proofs. Source definition/formation/boundary
validation stays compiler-owned; C35 implements prepared-demand
validation for logical plans with compiled/publication proofs. Other operation and
generation boundaries still need adoption. These implementation gaps keep carrier sign-off
open; ownership/callback expansion is not yet approved by this review.
