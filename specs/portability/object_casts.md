# Checked shared-object access
Doc Status: supporting

The compiler uses specialized AST nodes. An explicit checked cast selects a typed
view of the same shared object; it does not copy data or alter the AST graph.

```php
public static function variable_declaration(ast_node $node): variable_declaration_node {
    return object_cast($node, variable_declaration_node::class);
}
```

## Contract and placement

`object_cast(value, LiteralClass::class)` is a portability-framework operation,
registered in function_map.php, not a PHP builtin. Its PHP carrier accepts an object
or null and returns that same object when compatible. Null or mismatch throws
RuntimeException. Dynamic class strings, coercion and automatic downcasts at ordinary
typed boundaries are outside this slice.

The converter preserves `object_cast` with the literal marker. The v0.1 generator
also retains `scpp_portability_object_cast` as a compatibility alias. Native
generation reads only that locally supplied marker and emits
`scpp::checked_object_cast<T>(value)`. No inheritance inference or whole-program
semantic analysis is added. The runtime implementation lives in
`runtime/include/scpp/object_cast.hpp`; it retains the shared control block using
checked pointer casts. Null/mismatch raises runtime_error with `invalid_object_cast`.
Native exception transport/parity with PHP catch clauses still needs execution tests.

A present nullable object is accepted; empty nullable fails. The native narrowing
path requires a polymorphic interface/base (or an ordinary statically valid upcast).
Unsupported nonpolymorphic narrowing fails at C++ instantiation rather than guessing
object layout. Generated interfaces now have a virtual destructor, including empty
interfaces such as node_specialization. This adds normal polymorphic-object overhead to
implementations; no compact AST layout claim is made.

PHP `abstract class` alone does not guarantee a polymorphic native base. Preserve
the known concrete type through named accessors instead of widening facts and
casting them back. If consumers genuinely share a narrower family, give that family
a meaningful operation contract rather than adding dummy virtual behavior to an
unrelated root. Chapter 04's `prepared_literal` owns `literal_key()` and default
snapshots; workers still own normalization and equivalence algorithms. Its
[native checkpoint](../../compiler/my-try/docs/portability/conversion_review.md#latest-native-checkpoint--2026-10-02-function-closeout)
proves this bounded use without making all `prepared_expression` facts polymorphic.

## Common-base ternary branches

The [conditional-expression contract](../conditional_expression_matrix.md) determines
the result from the branch types, independently of the destination. An explicitly
typed local or property does not make sibling object branches compatible.

When branches need a common base view, convert both branches to that base inside
the ternary. In portable PHP, use the registered `object_cast` helper:

```php
$declaration->type_syntax = $this->text() === '['
    ? object_cast($this->array_type($type), type_node::class)
    : object_cast($type, type_node::class);
```

Both branches now lower to the same shared base-handle type, satisfying `T / T -> T`.
Only the selected branch is evaluated; object identity and dynamic type remain
unchanged. These casts are intentional upcasts, not nullable extraction or a request
for automatic common-base inference. Casting the whole ternary is too late: its
result type must already be valid. Separate assignments to a typed destination are
also valid. Ordinary single-value upcasts still need no extra cast at typed boundaries.

The parser's named/array-type selection uses this pattern. Focused AST,
parse/collection and object-cast checks pass; conversion and a native compiler
build retry remove the ternary diagnostic. This checkpoint does not establish a
complete native compiler build. Preparation work now uses distinct optional,
required and specialized accessor names under the compiler's
[exact override-return convention](../../compiler/my-try/docs/code_style.md).

## instanceof

Literal-name `instanceof` is preserved by the converter and lowers to
`scpp::object_is<T>`. Null/empty nullable is false. A compatible present handle is
true. This tests identity/type without changing the static type of the source handle;
it does not replace an explicit concrete payload cast.

The native pre-tokenizer previously swallowed `instanceof Binding` as a postfix type
annotation, producing invalid `shared_p<instanceof Binding>` output in a minimal
probe. The scanner now excludes that expression operator from type slots, and the
generator explicitly handles the AST operator. Dynamic class operands are not part
of this portability slice.

## Evidence and limits

The [first native checkpoint](../planning/compiler_migration/results/my-try-native-01/README.md)
proves successful casts, shared identity and null/mismatch instanceof predicates
with normal STAN enabled. `tests/portability/native_collections.py` reproduces it
against an explicitly supplied candidate. The reserved cast call is registered in
STAN; its literal target is still validated by lowering. Native failure/catch parity
is not covered by that passing trace. The current shared-object boundary policy now supports derived-to-base/interface
conversions directly, including nullable sources and destinations; the older
intermediate-interface-local workaround is no longer required. See
[runtime shared-object boundaries](../../runtime/specs/spec.md#shared-object-typed-value-boundaries).

`php tests/portability/object_casts.php` checks PHP identity, field aliasing, null and
mismatch rejection, converter output, and native C++ emission without compilation.
It verifies the checked runtime calls and polymorphic interface declaration.
The pre-tokenizer regression fixtures also pass. Compiler PHP model/AST/LLVM tests
exercise the rewritten payload consumers. No native executable was built or run.

The configured native target is unchanged. These runtime/generator changes must be
included alongside the Storage source-binding delivery before the exported compiler
can undergo native build/behavior validation. This is a completed conversion slice,
not a completed native release or general PHP RTTI compatibility claim.


## Common v0.2 route — 2026-10-21

v0.2 parses the same `object_cast(value, Target::class)` spelling into its existing
object-type operation. Shared preparation owns ancestry, polymorphism, ownership
and result typing. The C++ backend consumes those facts in ordinary and prelowered
modes. Prelowering uses one compiled object-type adapter family for checked casts
and `instanceof`, keyed by operation, source type and target payload. Templates and
runtime type checks remain in middleware; application fragments use concrete calls.
The input is evaluated once and borrowed through the caller's full expression.

Nullable class/interface inputs are admitted: instanceof returns false on absence,
and object_cast fails with invalid_object_cast. The prepared input retains its
optional source identity; both output modes reuse existing shared-handle transport.
Unrelated records, nonpolymorphic narrowing, bare null, dynamic targets and weak/unique
operands remain outside the supported contract. Lock a weak link first. Redundant
optional wrappers on explicit ownership types remain rejected.

`python3 tests/portability/object_cast_common.py --results FRESH` checks common PHP
and v0.1 execution, legacy alias emission, ordinary/concrete v0.2 identity and
single evaluation, interface cross-casts, struct handles, weak-lock inputs, empty
and mismatched failures, and retained semantic rejection tests. It builds generated
programs, not a native compiler.
