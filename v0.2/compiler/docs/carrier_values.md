# Packed carrier conversions and payload operations

Doc Status: supporting

## Implemented boundary

The four published families share one transport path: nullable<T>, result<T>,
result_or_false<T> and result_or_bool<T>. Construction, same-identity copying,
assignment, by-value arguments/defaults/returns and checked one-layer extraction
use canonical published types. Exact carrier reference arguments bind their original
storage; extracted payload copies cannot bind a mutable payload reference.

`?T` normalizes to the existing nullable<T> type-reference tree. PHP locals retain
this compiler's existing name-first grammar (`$item ?int = 12;`); parameters use
`?int $item`. The generic spelling works in every flavor.

```php
$item ?int = 12.75; // Existing checked typed-destination conversion, then wrapping.
echo $item + 5;     // Checked extraction, then ordinary int arithmetic: 17.
$absent ?int = null;
if (isset($absent)) { echo "present"; }
```

Explicit `false_sentinel` and `true_sentinel` literals select only metadata-supported
alternate states. `result_or_false<bool>` constructed from ordinary `false` contains
a successful false payload. Null constructs nullable absence only. Same-carrier copying
preserves the complete state; converting between carrier identities extracts and rewraps
an admitted payload, and does not silently propagate absence or failure.

Nonlogical binary operators and unary signs/bitwise negation select the existing
payload type handlers. There is no carrier promotion or operator matrix. Both operand
orders and two-carrier operations work, producing the ordinary payload operation result.
This implements the agreed payload-based operator model, not a separate state-aware
carrier equality/identity scheme. No recursive carrier peeling is performed.

`isset(carrier)` is true for a successful value or true sentinel. A successful false
payload is present. Conditions and logical operators preserve alternate-state behavior:
empty/error/false sentinel is false, true sentinel is true, and successful values use
T's existing generated truthiness. Queries evaluate their operand once; logical AND/OR
retain short-circuiting. A value-only operator on an empty/sentinel carrier throws through
the runtime's checked value binding.

## Packed representation and ownership

`value_conversion` is now a constants/helper class, not a PHP enum instance. AST fields
store an integer with native uint16 intent:

| Bits | Meaning |
| --- | --- |
| 0..7 | Existing scalar operation, or explicit empty/sentinel construction operation |
| 8 (0x0100) | Checked extraction of the source carrier's payload |
| 9 (0x0200) | Wrap the converted payload in the destination carrier |

A float-to-nullable<int> conversion records floating_integer_checked/proven plus WRAP.
A nullable<int>-to-float conversion records numeric_floating plus EXTRACT. Transport
can combine both flags. Ordinary scalar conversion IDs remain unchanged. There is no
per-conversion allocation, descriptor list, second AST, or copied source/destination type.
The original expression and destination declaration already provide these identities.

`type_definition` owns shared transport entry points and delegates scalar work to the
existing generated handlers. Lowering consumes the code and original source type; it
does not repeat admission or range analysis. `ast_expression::type_def()` keeps the actual
type, while `value_type()` exposes one payload layer to operator/conversion planning.
Binary conversion facts retain extraction flags. Unary lowering uses its already-approved
operand's carrier binding. Native copying/reference rules remain explicit.

New `ast_isset_expression` owns one operand and only its normal prepared bool result.
Collection, reset and inspection traverse that operand. `carrier_sentinel_type_definition`
provides shared compiler-only literal identities; generated singleton subclasses do not
allocate per literal occurrence. Source sentinels have no standalone variable storage.

Carrier states are deliberately excluded from scalar constant propagation. A known null
scalar and an absent carrier are not interchangeable facts. Existing body watchers,
preparation queues and generation cleanup reset all affected facts, including nested
presence operands; no new retained session state or cleanup owner was added.

## Metadata and generated code

Runtime metadata supplies family states/defaults, extraction policy, payload access,
presence bindings and sentinel spellings/factories. Generator validation currently accepts
the reviewed ABI methods (value, has_value, is_true, from_false/from_true), rejecting unknown
bindings before output publication. The generated carrier definitions consume those bindings
and the existing provider-selected C++ names/headers. Compiler inventory version is 22.

Scalar handler algorithms, conversion permissions and operator promotion tables remain the
same. Their signatures now consume packed integers at the common boundary and plain scalar
operation codes internally. Application C++ remains template-free. Runtime storage, public
ABI and implementation algorithms are unchanged; reviewed runtime/scalar/CLI captures show
identical emitted native artifacts, with metadata plan/input digests updated.

Preparation retains one traversal, uses canonical type references and constant-time flag
operations, and does not inspect metadata or foreign bodies at source compilation time.
The scalar path has additional small shared method boundaries; native inlining can remove
them. No claim of measured native compiler performance follows from the PHP implementation.

## Deliberate departures and remaining work

Two accepted departures from the donor are explicit:

- Typed destinations authorize the existing checked payload conversions before wrapping;
  construction is not limited to an already-exact payload type.
- Null does not act as a false sentinel. Use false_sentinel explicitly.

Source construction/inspection of structured errors (including error::make), take and
automatic error propagation remain deferred. Existing runtime result error storage and
checked extraction are unchanged. Carrier compound/update writes, delegated members,
carrier text conversions/direct echo, nested carriers and further specializations are
also deferred. Vector payload copies through a carrier are supported; this does not enable
arbitrary ordinary vector copying/arguments or new vector operations.

Presence/condition semantics are separate from native C++ implicit conversion behavior.
The generated source compiler explicitly lowers them; it does not depend on `if (carrier)`.
The existing evaluation-order debt remains in force; this slice uses the current emitter's
sequencing machinery and introduces no new general sequencing framework.

## Verification

`tests/carrier_values.php` checks all three flavors, packed codes, constant-fact isolation,
checked numeric conversions, explicit sentinels, nullable shorthand, both operand positions,
string byte/NUL preservation, vector payload copying, arguments/defaults/returns, reference
rejection, conditions/presence and short-circuiting. Separate native fixtures check empty/
sentinel extraction and dynamic conversion overflow.

The incremental sequence changes a used function's carrier family/payload, fails preparation,
retries without edits, recovers, deletes and re-adds it. It retains the unchanged consumer
AST and stable symbol entry, compares each successful emission with a fresh compilation,
and verifies no-op body reuse. Every successful native checkpoint is executed. Runtime
provider tests also cover extraction-policy generation and invalid binding rejection.

Final verification: 80 selected compiler CTests pass (3.70 s), style checks cover 164
compiler PHP files, and all 33 native fixtures pass. Carrier/vector/provider tests pass.
Generator captures retain 130 accepted/247 rejected runtime cases, 19/537 scalar cases
(with native scalar consumer compilation) and 17/37 CLI cases. Emitted native artifact
sets are identical to the preceding captures; only metadata plans/input digests changed.

Representative main-fixture timings, in milliseconds:

| Flavor | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| cpp | 192.98 | 40.81 | 28.62 | 69 |
| php | 153.52 | 27.93 | 21.68 | 60 |
| js | 160.89 | 26.99 | 24.84 | 64 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime and fresh instrumented application
objects. These are single verification measurements, not statistical flavor comparisons.
Frontend/LLVM trace totals and link-command work are distinct from wall time. PHP fixture
generation (including all flavor/incremental/fresh checks) took 145.08 ms; that is harness
work, not one application's compile time. Full results and generated fixtures are retained
in `/tmp/scpp-packed-carrier-native-complete/results.json` and its adjacent directories.
