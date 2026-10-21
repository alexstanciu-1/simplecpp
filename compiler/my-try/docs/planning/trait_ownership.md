# Trait lifecycle and ownership selection — V-D03 / V-D04
Doc Status: supporting

Closed 2026-10-21: **53/64 original issues resolved (82.8125%), +3.125 points**.
Both closures implement the agreed bounded language contracts. Eleven original
issues remain open. Historical rejection evidence stays in the issue ledger.

## Contracts and ownership

Concrete trait constructors/destructors use consumer-owned working methods. A local
lifecycle declaration wins over one trait contribution. Distinct trait lifecycle
contributions conflict even with a local override; identity diamonds contribute once.
The shared member inventory selects effective lifecycle bodies for preparation,
lookup, reference-cycle analysis and cleanup visibility. Hidden bodies remain
conflict evidence. Ordinary trait-method collisions are unchanged.

Defaults, `set:`, private/protected access, explicit parent initialization, lexical
provenance and ordinary member/base cleanup retain their existing contracts. No
implicit lifecycle chaining or adaptation syntax is introduced. The normative
contract is [inheritance](../../../../specs/inheritance_v0_2.md).

Unique full ternary, Elvis and coalescing select one supplied same-family type,
including existing safe upcasts. Named owners require `move`; owned call/selection
results transfer directly. Left evaluation and selected branches execute once;
fallbacks stay lazy. Shared preparation records the value boundaries and owned
result origin; emission consumes them through existing move/conversion paths.
Weak full ternary preserves handle/control-block state and permitted upcasts.
Weak shorthand remains rejected under explicit `lock` policy. See the
[selection amendment](../../../../specs/conditional_expression_matrix.md#v02-shared-handle-joins).

The separately approved compiler bridge prerequisites transport existing `weak` and
`lock` operations, consume unique upcasts through a mutable owned input, and carry
construction keys minted at authorized call sites. Observation arguments stay in
the caller's full expression: `lock(weak(make()))` retains the temporary owner until
locking finishes. Constructor helpers do not gain permission to mint restricted keys;
source argument evaluation and defaults keep their order. No runtime implementation
or new metadata registry was added.

## Verification on dev002

The shared 12-slot runner passed **54 tasks** in
`/home/alex-ai/curl/20261021-trait-ownership-final`, using PHP 8.5 and Clang 18.
The runner selected CLI fallback because PHP CLI/FPM versions differ.

- 31 source programs published with default prelowering, built through the production
  builder and produced exact output. Each warm build performed zero compilation and
  zero relinking. Every manifest has `application_templates=forbidden`.
- 17 source rejection controls retain conflicts, abstract lifecycle rejection,
  constructor/destructor access, weak shorthand, missing moves, unsafe unique base
  destruction, sibling inference and cross-family restrictions.
- Three retained edit/reject/repair cycles cover local lifecycle override insertion
  and removal, unique destruction-contract changes and weak ancestry changes. No-op,
  preparation-reset and fresh output agree.
- Four existing regression suites pass: traits, trait instances, shared joins and
  shared upcasts. These supplementary suites retain their existing ordinary-mode
  scope; they do not replace default-prelowered production proofs.
- A focused native runtime control passes ASan/UBSan for unique identity/selection,
  virtual cleanup, weak expiration and empty fallback. It exercises unchanged runtime
  contracts; the compiler itself was not rebuilt natively.

Source cases cover trait diamonds, lexical names, local precedence, inherited/parent
construction, `set:` defaults, member cleanup, private/protected factories, argument
order/defaults and hidden private lifecycle visibility. Ownership cases cover empty
and present Elvis/coalesce, lazy call counts, nested owned selections, consuming
upcasts and weak interface/expired/temporary-owner behavior.

[Acceptance archive](trait-ownership-acceptance.tar.gz), SHA-256:
`93042f0b77cf30fc8add71b8f96e98f4db0707a7e429a0d84fe930a8a75c9ac5`.
It includes the baseline/intermediate failures, final command logs, source fixtures,
prelowering manifests and full source audit: **5,383 inputs, zero mismatches**.
The [25-file delta](trait_ownership_source_delta.json) extends the previous environment
snapshot. A stale remote metadata README was synchronized to the already committed
span/hash documentation; no runtime code changed.

## Remaining limits

Unique/weak method receiver integration, checked downcasts, third-type sibling joins,
unique interfaces, match, trait adaptations and abstract lifecycle contracts remain
separate. Default-prelowering `isset(handle)`/value-probe transport was exposed by an
early control and remains a separate gap; supported explicit handle truth testing
was used here. No full matrix, native compiler rebuild, broad portability claim or
runtime implementation repair is included. Earlier failed runs remain evidence,
including fixture spelling/direct-new-assignment errors corrected before final proof.
