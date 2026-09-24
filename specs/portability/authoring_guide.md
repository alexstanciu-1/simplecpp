# Writing convertible PHP
Doc Status: supporting

Use this guide when writing executable `.php` for conversion by
`tools/php_portability`. It summarizes the [portable profile](README.md), not the
full PHP language or the full Simple C++ target. The compiler migration's proof
target is recorded in [portability_target.json](../../compiler/tools/portability_target.json).
Target features do not become portable-PHP features until conversion and framework
support are proved. The feature catalog includes proposals, not just available forms.

For the compiler rewrite, follow the [stage methodology](../planning/compiler_migration/README.md#current-methodology-stage-by-stage-rewrite).
Load this profile's skill and the Simple C++ strict skill, read the prototype stage,
and establish its input/output contract. Iterate through PHP plus cheap conversion;
prove native behavior at new-capability and component checkpoints. Compare meaningful
results, using exact bytes only where the contract needs them. Defer unions and
more elaborate layouts until a concrete need or optimization pass.

## Start from the right source

Keep `<?php` and ordinary PHP syntax. Type comments are for executable PHP input;
generated PHS uses native type syntax, not those type comments. Express extra native intent through supported
comments, for example `$position /** int */ = 0;`. Do not paste PHP++ declarations
such as `$position int = 0;` or `struct` into executable PHP. A PHP class with named
fields is a supported structure; it does not automatically become a native value
struct. Current ordinary classes use shared object identity. Explicit scalar value
records and both `&ref` annotations now have a [separate proved contract](value_records.md);
read it before choosing copy or alias behavior.
Assign constructed objects to locals (or return/use the expression). On the current
9b4b33f target, standalone `new Class(...);` statements were observed to disappear
from generated C++, including constructor validation. Rejection tests must exercise
the constructor through an assigned object; see `results/resource-obligations-01`.
Use descriptive local names instead of C++ keywords (`$case_index`,
`$is_mutable`, `$void_type`). The checker/converter rejects keyword-named local
assignments, annotated declarations, foreach bindings and catch bindings with
source attribution. This is a case-sensitive lexical check, not symbol resolution
or automatic renaming. Declared callable parameters and object fields retain their
existing supported target handling; a parameter exemption does not leak into another
method. String contents and differently cased names are unaffected.

For operations that return either an existing current or previous class object,
pass both shared handles explicitly (for example a static retention helper). On the
pinned target, returning `$this` from an ordinary named-class method produces a raw
receiver pointer that cannot satisfy its shared-handle return type. Do not create a
replacement object to hide that identity mismatch. See
[provider declaration evidence](../planning/compiler_migration/results/provider-declaration-01/summary.json).

Keep field and method names distinct within a class: PHP permits `$selected` plus
`selected()`, but both become the same C++ member name.

On the pinned target, avoid a factory method named `create` when its body constructs
objects: emitted unqualified `create<T>()` calls can bind to that member instead of
the runtime helper. Use a descriptive factory name such as `prepare` or `from_source`.
This is a target lowering limitation, not a PHP restriction; see the instance-join
migration evidence in `specs/planning/compiler_migration/results/instance-join-01`.

Use no namespace or one leading lowercase `namespace name;`, optionally preceded
by `declare(strict_types=1);` and comments. Do not add function imports. The host
bootstrap supplies [global portable helpers](global_functions.md): plain names for
facilities absent from PHP, and `q_` names for existing PHP functions (`q_count`,
`q_strlen`, `q_is_bool`, etc.). Internal scpp/scpp\compat names are implementation
details; do not call them directly from portable source. The fixed
[function map](../../tools/php_portability/function_map.php) owns available names,
arities and target bindings. Arbitrary q_-prefixed PHP builtins are not supported.

## Supported forms and their limits

| Form available now | Limit and existing proof |
| --- | --- |
| Annotated scalar locals | `int`, `uint32`, `bool`, `string`; annotations do not emulate native numeric limits in PHP. [Foundation](first_slice.md). |
| Explicit named locals | Literal class/interface names pass through without resolution; [type-reference proof](compiler_type_references_slice.md). No unions, arbitrary generics or named wrapper payloads. |
| Scalar wrapper locals and `take_nullable`, `take_false`, `take_bool` | Fixed supported wrapper spellings, not arbitrary result types; bool payloads in false/bool-sentinel wrappers are rejected. [Executable fixture](../../tests/portability/fixtures/take.php). |
| Classes, fields, literal construction and fixed member calls | Supported scalar/named/container field forms, including private/protected initialized scalars and separately declared [readonly scalar fields initialized in constructors](compiler_native_project_slice.md); not arbitrary PHP declarations or inheritance. [Context](compiler_context_slice.md), [token vocabulary](compiler_token_slice.md). |
| Explicit public/private/protected instance/static methods | Explicit scalar/named types, concrete nullable returns and `void`, plus [annotated container returns](container_returns.md) on class/trait methods; [explicit nullable parameters with optional null defaults](nullable_parameters.md) are supported; generic/reference parameters and other ordinary-method defaults remain outside this slice. [Signature proof](method_signatures.md). |
| Constructors | Public constructors accept explicit ordinary or promoted parameters and supported statements. Container defaults require nullable null; bare empty-container defaults remain rejected. Named constants as promoted-parameter defaults are also rejected; pass the constant explicitly at construction. [Token storage](compiler_token_store_slice.md). |
| Explicit vectors of supported elements | Annotated PHP arrays; a PHP `array` alone does not select a native container. Nullable list fields/promoted parameters are now supported with explicit element annotations; see [discovery records](compiler_discovery_records_slice.md). Recursive vectors and typed maps now use [explicit container annotations](container_annotations.md); see its native access limitation. [Cursors](compiler_cursor_slice.md), [storage](compiler_storage_slice.md). |
| Unit/integer enums and declaration-only interfaces | Narrow declaration forms; literal class `implements` lists now pass through ([Source_Set proof](compiler_source_set_slice.md)); do not assume enum reflection or general polymorphic bodies follow from declaration support. [Steps](compiler_step_slice.md). |
| Bounded expressions, branches and loops | While loops now have a [production comparison proof](compiler_syntax_comparer_slice.md). Braced by-value `foreach`, bare break/continue, keyed `isset` and single hash-slot `unset` are now [proved](map_iteration.md). Use proved forms; accepting some operators is not complete PHP expression semantics. [Decimal algorithm](compiler_decimal_slice.md). |
| Direct same-namespace method-only traits | No trait composition, adaptations, properties, constants, magic methods or method collisions. Shared direct consumers are allowed. [Trait contract](traits_and_incremental_index.md). |
| Fixed handled exception family | Supported root exceptions and catch dispatch only; no general exception inheritance; `finally` is structurally preserved with [target restrictions](compiler_syntax.md). [Exception contract](compiler_exception_slice.md). |
| Sequence/keyed map and filter | Explicit carrier policy and bounded typed `function` callbacks; [contract and candidate target](collection_helpers.md). |
| Managed text and explicit byte helpers | Text helpers validate UTF-8 and use code points; byte helpers serve source offsets/binary data. [String contract](utf8_text_contract.md). |

File-scope constants currently accept integer literals; class constants are not
converted. For fixed string metadata such as a protocol profile, use an explicit
static accessor returning the string.

Read the linked slice before using its advanced forms. Do not assume that because
the index recognizes a declaration kind its body or interactions are supported.

Ordinary method signatures now preserve explicit scalar and named types, with
`void` returns. This removes a temporary converter limitation; Simple C++ methods
were never restricted to the earlier scalar-only vocabulary. See the
[signature contract](method_signatures.md) for remaining converter boundaries.

Static fields now support the same initialized scalar, named-enum, nullable and
annotated container forms as instance fields, with literal `Type::$field` and
class-local `self::$field` access. See [static fields](static_properties.md) for
construction limits, keyed operations and native proof. Explicit [required fields without initializers](required_fields.md) are also
supported under an assign-before-read/publication contract. Explicit [Storage template bindings](storage_collections.md) now have PHP/conversion coverage; native compiler parity remains separate work.

## Examples anchored in existing proofs

The fragments below belong after the optional file prologue.
No import block is required.

From the [wrapper fixture](../../tests/portability/fixtures/take.php):

```php
$out /** int */ = 9;
$maybe /** nullable<int> */ = 0;
echo take_nullable($out, $maybe) ? "yes\n" : "no\n";
echo $out, "\n";
```

Zero is a present value. Test the absent state too; do not replace extraction with
a truthiness check. PHP uses the approximation implemented by the framework;
conversion emits the corresponding target operation.

From the [trait proof](../../tests/portability/traits.py), in namespace `demo`:

```php
trait Operations {
    private function adjust(int $amount): int {
        $this->value = $this->value + $amount;
        return $this->value;
    }
    public function advance(int $amount): int { return $this->adjust($amount); }
}
class First { use Operations; public int $value = 1; }
```

The writer supplies the fixed field and method relationship. The converter copies
direct trait methods into consumers; it does not infer the receiver's type or prove
the field exists. Same-namespace files can split these declarations without aliases.

## Design discipline: the writer and tests own it

- Prefer named typed structures. Ordinary PHP arrays are for non-hot setup then
  read-only use. Explicitly annotated vectors remain useful typed collections;
  do not confuse their PHP carrier with an invitation to use arbitrary array behavior.
- Make ownership, mutation and copying visible. Test changed state, unchanged
  snapshots and shared/independent identity. Preserve the algorithm, not incidental
  PHP copy-on-write behavior. Readonly container use does not freeze contained objects.
- Treat references carefully. Adapt storage gradually toward records selected by
  index where appropriate; do not silently equate logical IDs with storage offsets.
- Use explicit JSON schemas and checked boundary conversion. `json_quote($text)`
  supplies exact scalar-string encoding with checked UTF-8; see the
  [manifest snapshot proof](compiler_manifest_record_slice.md). Do not use JSON
  roundtrips as an internal object-copy or equality mechanism.
- Use managed text functions for UTF-8 text; use explicit `string_byte_*` operations
  for lexer offsets and binary data. `string_byte_at` returns an integer, not a
  one-byte PHP string. Code-point counts do not count graphemes or normalize text.
- Keep null, false and errors distinct. Test numeric limits and lifetime behavior
  on the native target when PHP cannot model them faithfully.
- Use explicit typed contract comparison when migrating PHP object equality. A
  prototype enum object and its integer-tag replacement do not have identical PHP
  `==` behavior: a nullable tag can collapse null with zero under loose comparison.
  Compare optional states and scalar facts strictly, and retain identity only after
  the complete relevant contract agrees. Use original prototype records as the
  oracle when representation changes affect host equality.
  Keep accepted nominal-owner identity separate from value-contract equality; equal
  fields alone do not authorize replacing the current accepted owner with an old one.

The converter does not enforce hot-path policy, ownership intent or array discipline.
Those are review and behavioral-test responsibilities, not reasons to add inference.

For a concrete tested pattern, see [owned collections and explicit snapshot updates](collection_snapshots.md):
copy membership, explicitly copy the changed row, and share unchanged rows only
while treating them as immutable. The proof also shows how mutating a shared row
breaks snapshot independence. It is an example, not a general container framework.

## When a form is rejected

| Temptation | Authoring response |
| --- | --- |
| Dynamic function/property/class names | Use fixed named operations or an explicit typed owner; preserve intended dispatch behavior. |
| Named arguments requiring remote defaults | Write positional arguments explicitly or use a suitable supported factory; do not ask the converter to inspect callees. |
| `as`/`insteadof`, nested or cross-namespace traits | Restructure direct composition or resolve method naming explicitly in source. |
| Arbitrary PHP builtin or per-file global bypass | Check the central function policy; specify a shared adapter if needed. Never silently substitute PHP behavior. |
| Nullable/generic signature, ordinary constructor, callback form or `finally` outside current coverage | Report a parser capability gap. Do not distort the algorithm to fit scalar-only methods or discard guaranteed cleanup. Add a bounded slice when authorized. |
| NUL/invalid-UTF-8 literal or unsupported Unicode escape | Use supported UTF-8 source text where equivalent; use `string_byte_from_int` for explicit runtime byte construction (0..255). |
| Value semantics, variants or custom diagnostics not yet proved | Establish the representation/contract first; do not guess from PHP execution or flatten meaningful compiler models. |

Unsupported input must fail with source attribution. Converter acceptance is not a
semantic certificate: native analysis/build and behavioral tests still own type
compatibility, identity, lifetime and algorithm correctness.

## Validation with tools available today

For the current ready compiler set, the [validation workflow](validation_workflow.md)
provides one fast command and explicitly selected native proofs. The individual
commands below remain useful for new source folders and focused work.

From the repository root, with a dedicated source directory and a separate output
directory (replace the uppercase paths):

```bash
php tools/php_portability/check.php SOURCE_DIRECTORY
php tools/php_portability/convert.php SOURCE_DIRECTORY OUTPUT_DIRECTORY --stats
php tools/php_portability/install_native_runtime.php OUTPUT_DIRECTORY
```

Lint each authored file with `php -l FILE.php`, and run its PHP behavioral harness
with `tools/php_portability/runtime/bootstrap.php` loaded. PHP text helpers require
mbstring. For multi-file code, PHP loading belongs to the host harness/composition
root; keep bootstrap and test runners outside the converted source directory.
`check.php` checks imports, PHP syntax, declarations/traits and conversion eligibility
without publishing any files. It reports the first failure. Optional
`--cache EXISTING_OUTPUT_DIRECTORY` reuses existing conversion discovery/token data
read-only; lint and structural conversion still run for every file. No source is
executed, and no native type or behavior proof is implied.

Select existing proofs relevant to the change:

- `python3 tests/portability/run.py`: foundation, rejections and incremental behavior.
- `python3 tests/portability/check.py`: read-only checking and rejection parity.
- `python3 tests/portability/traits.py`: traits, imports and incremental declaration cache.
- `python3 tests/portability/utf8.py --results FRESH_RESULTS_DIRECTORY`: PHP text/byte behavior and conversion.
- For traits/UTF-8 native proofs, add `--target-checkout TARGET_CHECKOUT` (traits also
  accepts `--results FRESH_RESULTS_DIRECTORY`). These runners verify the pinned target.
- `tests/portability/compiler_context/run.py` provides the cumulative compiler proof;
  inspect its CLI for required target/results arguments before running it.

Use independent expected results plus PHP/native comparison. Pure output agreement
does not prove correctness. Actual MT, synchronization, process cancellation and
native lifetimes need native tests. A sequential PHP approximation proves only the
algorithm it executes. Record the target revision; do not switch to the workspace
toolchain implicitly or patch generated output to make a proof pass.

## Deferred, not available spellings

The [three runtime requirements](../planning/compiler_migration/simple_cpp_batch.md)
have a native candidate in issue #231. [Collection adapters](collection_helpers.md)
now have PHP/conversion support and candidate proofs. [Process/lock adapters](os_helpers.md)
now pass the prepared PHP/native facade comparison on `2f0d667f`. Read their
Linux, ownership, assembly and PHP-approximation limits before adapting callers.
The `SCPP_NATIVE` native-comment escape hatch remains documentation-only. Broad
container/variant contracts, custom diagnostics and further signature forms
still need their own decisions/proofs. GUI/WebView and full PHP compatibility are
outside this effort. This guide does not resume compiler mining or add functionality
to the adopted compiler.

Concrete class/trait methods accept required array parameters with adjacent explicit
container annotations, as proved by [source-scan reconciliation](compiler_scan_join_slice.md).
Container interface parameters, defaults and reference parameters remain rejected.
The converter also rejects nullable interface returns, including nullable named
objects. Its legacy version-labelled diagnostic is a converter gate, not a fresh
proof about the pinned target. A concrete read-only view handle can preserve a
shared batch-read contract; the instance-registry migration records that adaptation.
On the pinned target, STAN can report a missing return for a helper whose branches
return values and whose final statement throws. If the control flow is otherwise
complete, assign a typed result in the recognized branches, throw in the rejecting
branch, and finish with the typed return. Keep the rejection; do not bypass STAN or
add a success default that changes behavior. See the signature-request migration
proof in `specs/planning/compiler_migration/results/signature-requests-01`.

Explicit fully qualified uppercase constant references pass through without resolution.
Initialize a local in its enclosing block before using it after branches; native
block scope does not inherit PHP's function-wide local-variable behavior.

For empty-container assignment through an object field, declare an explicitly typed
empty local first and assign it. Bare `[]` at that target loses container type in
the current type-blind lowering; see [snapshot acceptance](compiler_snapshot_join_slice.md).

Reject absent nullable objects in a separate guard before dereferencing them. The
selected target failed the compound null-check/dereference proof in
[snapshot acceptance](compiler_snapshot_join_slice.md); PHP short-circuit behavior
must not be assumed for that native expression form.

More generally, use nested `if` statements or early returns whenever the safety,
errors or side effects of a right-hand expression depend on `&&`/`||` skipping it.
This includes optional node IDs, bounds-dependent reads and effectful calls, even
when both expressions return concrete booleans. Independent safe comparisons may
remain compound. This is a migration authoring constraint, not a new converter
rejection or a change to Simple C++ operator semantics. See the
[adaptation record](../planning/compiler_migration/php_adaptation_record.md#explicit-control-flow-for-dependent-guards).

Keep `enum_name($value)` in the enum declaration file, behind a concrete enum-typed
method when other units need names; cross-unit enum-name generation is not supported
by the selected target. Fully qualify compiler
types in container annotations where names overlap runtime types (for example
`\tokenize\Token_Buffer`). See [token storage](compiler_token_store_slice.md).

For filesystem discovery, use the [scanner facade](compiler_source_scanner_slice.md):
size/mtime/scan are false-result adapters with caller-owned diagnostics. Native
assembly requires `--filesystem` and the filesystem runtime module. These helpers
do not supply path/open-handle identity checks for verified snapshot reading.

When a public vector<string> boundary must retain PHP carrier diagnostics,
`sequence_require_strings` provides an explicit check with caller-owned messages;
see [runtime-preparation symbols](compiler_preparation_symbols_slice.md). Native
code relies on the helper's typed vector signature, not dynamic element inspection.

For verified byte ingestion, use [the snapshot facade](verified_source_reads.md).
Keep OS handles and guaranteed cleanup in the framework/runtime owner. PHP cannot
model native no-follow/nonblocking open races; state those limits explicitly rather
than treating ordinary stream reads as full native protocol parity.

For scalar schema values, [Json_View integer/boolean access](scalar_catalog.md) rejects wrong kinds, fractions/exponents and integer overflow; do not coerce provider metadata through strings or floating values.

For nullable constructor containers, unwrap into a concrete typed vector with
`take_nullable` before `foreach`. A null guard alone does not unwrap the native
nullable container. Normalized record catalog input proves this path in
`specs/planning/compiler_migration/results/provider-catalog-01`.

Host-only class/method declarations may use [@scpp-no-export](compiler_syntax.md).
This omits declarations, never callers. Compiler-required match/switch/do-while,
coalescing and finally now have structural conversion coverage; see that contract
for native limitations and pending proofs.

Compiler transient object indexes can use the explicit
[SplObjectStorage-to-hash carrier](object_hashes.md). Its bounded publication and
iteration rules matter: PHP map aliasing is not native hash value semantics.

For interface-held compiler records, use [checked shared-object access](object_casts.md).
Literal instanceof tests do not change the static type of a handle; payload accessors
perform explicit object_cast calls. PHP and generated-C++ inspection are proved;
native compilation remains pending.
