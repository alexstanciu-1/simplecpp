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


Original review baseline: `0158f5b0b`, 2026-10-27.
Current first-path review: C38, 2026-10-28, after `7ffb0ab21`.
Owner: [runtime-core completion](runtime_core_completion.md), batch C.
Result: **the canonical scalar carrier path passes the first-path checkpoint**.
The planned D application-record ownership prototype can start. This is not full C
completion, blanket payload admission or a change to source rules. Callback expansion
still depends on the ownership/transport evidence it needs.

## What the current evidence establishes

The source of truth for physical generation is `runtime/metadata/public_api.json`;
carrier projection, condition and selection planning live in
`runtime/tools/api_generator/{carriers,conditions,selections,operators}.cpp`.
The following is a current acceptance map, not a count of historical checkpoints.

| Requirement | Current evidence | Remaining acceptance work |
| --- | --- | --- |
| Four distinct carrier state models | Nullable, Result and both sentinel recipes; checked payload constructors/extraction, state factories and state-aware take tests | C38 combines prepared generation and operation facts for canonical bool/int64/float64. Other payload domains retain their own admission/proof requirements. |
| Conditional storage and unavailable copying | `check_conditional_lifecycle.py`, `check_sentinel_carriers.py`; vector/hash now reuse capability selection | Copy-only native concepts and finer operation requirements remain limitations. Do not advertise arbitrary source-type coverage. |
| One-layer arithmetic, update and compounds | Projection, unary, compound, special and power plan/execution proofs | These establish bounded admitted domains, not arbitrary nested lifting or frontend adoption. |
| Equality/identity/order preserve the agreed distinctions | Carrier equality/order proofs, original-state dispatch and checked ordering projection | Keep literal/sentinel routes and payload admission in the final closure matrix. |
| Required casts versus direct conditions | Conversion/projection tests and state-aware condition resolver | Required bool casts must still require payload; observation must not accidentally introduce copying. |
| No-copy observations | C27 explicit constant leaf facts avoid extraction for inline-record conditions | C28 covers callable payload conditions; C29 composes nested carrier states, including a noncopyable record fixture with explicit facts. Source fact production remains separate. |
| Guarded logical operators | Existing contract/compiler audit in the logical-selection note | C32 fixes canonical guarded negation; C33/C34 provide canonical and boxed lazy plans. C38 combines canonical prepared generation and execution. Other non-numeric carrier admission remains separate. |
| Lazy usable-value coalescing | C25 exact scalar policy and short-circuit execution proofs | C31 adds exact mixed/dynamic plain pairs and carrier-left/plain-boxed fallbacks, with distinct selected-value validity. Broader joins remain; result_or_bool rejection is intentional. |
| Lazy ternary/Elvis with preserved result state | C26 exact scalar branch joins and short-circuit execution proofs | C30 also covers exact mixed/dynamic joins. Admitted record, ownership and contextual joins still need their own policies/proofs. |
| Source formation and publication | Physical probes, canonical demands, sealed specialization reuse | Source definition/application/boundary facts remain distinct from physical storage facts. C35–C37 implement the consumer; C38 combines prepared carrier generation/operations and sealed reuse. Frontend fact production remains excluded. |

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
Source-fact ownership is settled; C35–C38 implement and test its public consumer.
If nested delegation requires a separate semantic owner, stop and report that boundary
rather than turning this into another independent type registry.

## C38 first-path review and expansion decision

The combined proof uses fixture-prepared source declarations for twelve concrete
carrier types (four families × boolean/int64/float64), with explicit primitive
source dependency facts. The same run resolves source-mode operations, generates
storage through the source-mode probe driver, compiles public-only consumers,
executes every state path, seals the application and requests source-mode reuse.
Removing source facts from that reuse request rejects before publication.

Release and ASan/UBSan with leak detection pass: 36 consumers, 132 success cases,
54 failure/skip groups, zero successful-path allocations and zero regenerated units
on sealed reuse. The compiler-side producer is a fixture, explicitly allowed for
this public-runtime goal; no source frontend support is claimed. Existing historical
arithmetic, conversion, comparison, take and conditional-lifecycle proofs remain
scoped evidence, not newly executed tests from this review.

| First-path checkpoint | Review finding |
| --- | --- |
| Source versus physical ownership | Compiler facts are explicit; native traits/imports cannot fill missing semantic evidence. |
| Storage and operation composition | Both use the same generation, naming, publication and prepared-request mechanisms. |
| State behavior | Required payload failures differ from direct conditions; lazy RHS extraction stays conditional. |
| Public boundary/lifetime | Public-only consumers pass sanitizers; no new public templates or interior references were added. |
| Allocation/build cost | Successful canonical operations allocate zero; only application proof units compiled, runtime archives reused. |
| Publication | Sealed specialization reuse emits zero units and still requires fresh request evidence. |

This meets the requested **first complete carrier path review**, allowing the
planned D prototype with one application-defined record. It does not shrink the
completion objective to this path. C still includes broader admitted joins,
non-numeric/source-role admission reconciliation and payload-specific integration;
D/F/E/G retain every unfinished item in runtime_core_completion.md. Ownership is a
separate handle concept, not payload lifting. Its first proof must cover shared
retention, explicit weak lock, unique transfer and destruction once without requiring
pointee copying; casts and inheritance need their own prepared target facts.

Single-run storage/caller compile observations: release 13.7637s/0.9627s; sanitizer
18.5086s/2.0954s. Runs overlapped, so these are not controlled performance comparisons.
