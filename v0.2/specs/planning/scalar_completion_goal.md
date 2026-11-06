# Active scalar completion goal

Doc Status: planning
Date: 2026-11-06
Status: implementation and acceptance complete; user selected active scalar types only. Deferred features remain excluded. Work is uncommitted.

## Baseline

Canonical int64/int and uint8/byte identities, family numeric conversions, integer
arithmetic/comparisons/compounds, mixed integer/float operations, unary signs,
discarded-value updates, truthiness and bitwise/shifts are implemented. The latest
bitwise/shift slice is tested but not committed. Preserve unrelated work.

The family model is recorded in [the agreed plan](scalar_families_and_conversions.md).
Its intentionally deferred work is not automatically part of this goal.

## Recommended first goal: complete agreed operations for active scalars

1. Freeze the admission checklist for null, bool, int64, uint8, float64 and string.
   Mine owning specifications and donor evidence for each pending operation. Classify
   every proposed cell as implement, intentionally reject, or deferred with a reason.
   Check strict identity, ordinary null equality, eager logical xor, numeric spaceship
   and exponentiation explicitly; do not overlook them by calling numeric work complete.
2. Complete string/string comparison and concatenation groups, including supported
   compound writes. Preserve byte-length/NUL semantics. Decide flavor spellings before
   changing parser admission. Broader scalar-to-text conversion remains a separate
   sub-slice: the specification distinguishes concatenation formatting from explicit casts.
3. Complete the approved plain-scalar equality/identity and remaining logical/numeric
   groups from the checklist. Null equality is not permission to introduce nullable
   types, null truthiness or implicit null conversions. No default adoption of every
   donor overload or its mixed-value/carrier rules.
4. Implement only explicitly included text conversion contexts. If excluded, keep
   string-only operations bounded and document the rejected cross-family cases.
5. Consolidate metadata ownership, current documentation and admission coverage.
   Run all-flavor semantic/native and incremental recovery proofs, then benchmark the
   capability samples including Clang frontend, LLVM and linker. Report deliberate
   exclusions and structure changes. Do not claim entire catalog completion.

## Separate follow-up goals

- Optional integer representations/module activation: full-domain constants and
  conversions, uint64/signed exact comparisons and checked shift-count transport.
  Current PHP constant storage and native int64 count ABI cannot simply be assumed
  to cover uint64. Assess the owning models before activation.
- Optional floating formats: representation versus execution precision, rounding,
  finite overflow, subnormal/signed-zero behavior and conversion boundaries.
- Value-producing prefix/postfix updates: new expression results, old/new value
  snapshots, addressability and evaluation-order interactions.
- Nullable types, user-defined overloads and conversion ranking: separate semantic
  models, not additional cells in the current plain-scalar table.
- Char semantics, collections, global writes/string globals and general new writable
  locations remain independent work unless explicitly brought into scope.

## Decisions and blockers

- Scope selected: active scalar types only.
- Text conversions excluded. String-only concatenation: PHP . and .=; C++/JS + and += (user approved).
- Strict identity uses canonical representation identity; aliases are identical (user approved).
- Eager logical xor accepts existing truthiness types independently (user approved),
  superseding the older donor restriction.
- User confirmed null/null ordinary equality only, byte-inclusive family promotion for spaceship,
  and xor in every flavor with grouped initializer/assignment RHSs. Power uses current
  standard exceptions and retains structured error fields as explicit debt.
- Preserve existing expression-order debt. If an operation needs stronger sequencing
  for correct failure/store behavior than the current lowering can express, identify
  the concrete case and discuss the ownership change before implementing it.

## Refactoring and execution boundaries

No broad redesign is proposed. Small local changes to metadata normalization,
generated handlers, parser operator recognition and public runtime bindings are
expected. A promise of zero refactoring would be misleading. Do not add another
handwritten admission matrix or force new behavior into an untruthful owner.

A goal may continue autonomously through approved slices, but a newly discovered
cross-owner redesign or semantic conflict requires discussion before dependent work.
Keep PHP sequential, AST-owned facts, stable symbol handles and existing native join
gates. Reuse unchanged syntax; invalidate only observed contracts/bodies.

## Acceptance for each implementation slice

- Metadata removal/subset and malformed-policy tests; generated handlers do not
  interpret metadata during source preparation.
- All three flavors: positive and rejection cases, relevant boundary/error behavior,
  one operand preparation/evaluation where promised, and lowering purity.
- Retained-session body/signature changes, failures and no-edit retries, deletion,
  restoration and comparison with a fresh compilation; no-op reuse.
- Native execution and Clang stage timings with configuration/cache state recorded.
- Consolidate stale assertions/docs; report structure changes. Commit policy is
  separately controlled by user authorization, not implied by goal completion.

## Admission checklist and evidence

| Group | Active scope / rule | Authority / decision | State |
| --- | --- | --- | --- |
| String ordering | string/string < <= > >=; unsigned-byte lexicographic, NUL-aware; bool result | Runtime string operations + donor V-L03 reviewed; scalar contract checkpoint recorded | Implemented; all-flavor native/incremental proofs pass |
| String concatenation | string/string only; PHP ./.=, C++/JS +/+=; string result; existing write positions | User flavor decision; expression_evaluation.md bounded text context, narrowed by deferred text conversions | Implemented; all-flavor native/incremental proofs pass |
| Strict identity | ===/!== for active scalar pairs; distinct canonical representations differ; aliases share identity; same-type value comparison; NaN remains unequal | User canonical-identity decision; expression_evaluation.md strict identity amendments | Implemented; all-flavor native/incremental proofs pass |
| Logical xor | Eager bool result via independent existing truthiness; no null conditions | User truthiness decision; expression_evaluation.md XOR evaluation contract | Implemented: xor all flavors; grouped assignment RHS; native/incremental proofs pass |
| Numeric spaceship | integer/float64 same/mixed including byte; -1/0/1, NaN throws unordered_comparison | expression_evaluation.md numeric amendment; compact_layout_types.md specialized-domain boundary | Implemented; user-approved byte promotion, all-flavor native/incremental proofs pass |
| String spaceship | string/string; -1/0/1 by complete unsigned bytes | Runtime legacy comparison configuration + donor V-L03 | Implemented; byte/NUL native oracle passes |
| Integer power | int64 ** int64, **= at current write positions; exact checked result, negative exponent/overflow errors; right-associative above unary | specs/integer_power.md | Implemented; byte/float power excluded; native/incremental proof passes; structured-error debt approved |
| Null ordinary equality | null/null only; heterogeneous ordinary equality rejected; no nullable work | User confirmed null/null only | Implemented; all-flavor native/incremental proof passes |

Previously completed numeric groups remain covered by their existing proofs. Any
new byte-domain agreement must be recorded explicitly; general family direction alone
does not authorize changing a separately bounded specialized operator contract.


## Consolidation progress

Generator snapshots have been reviewed and refreshed. Runtime snapshots change only
the minimal public arithmetic header (float comparison and integer power declarations);
scalar artifact multisets are otherwise identical, and CLI differences are limited to
that header and metadata input hashes. Captures retain all prior acceptance/rejection
counts. Header-list generation now unions all required bindings per operator.

Final broader regressions and the unchanged capability benchmark baseline have passed.
New operator behavior is proved by dedicated all-flavor native/incremental suites;
the older benchmark workload is not presented as exhaustive new-feature coverage.

The requirement-by-requirement [completion audit](../../compiler/docs/scalar_completion.md)
records evidence, structure changes, timings, scope exclusions and the socket-test environment restriction.
