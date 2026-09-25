# Checked shared-object access
Doc Status: supporting

The compiler retains `ast_node.specialization` as an optional `node_specialization`
handle. Syntax_Nodes owns concrete payload accessors. Each returns the same object
through an explicit checked cast; it does not copy data or alter the AST graph.

```php
public static function binding_data(ast_node $node): binding_specialization {
    return object_cast($node->specialization, binding_specialization::class);
}
```

## Contract and placement

`object_cast(value, LiteralClass::class)` is a portability-framework operation,
registered in function_map.php, not a PHP builtin. Its PHP carrier accepts an object
or null and returns that same object when compatible. Null or mismatch throws
RuntimeException. Dynamic class strings, coercion and automatic downcasts at ordinary
typed boundaries are outside this slice.

The converter emits the reserved `scpp_portability_object_cast` binding with the
literal marker. Native generation reads only that locally supplied marker and emits
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
is not covered by that passing trace. Stabilize a derived object as an explicit
interface local before passing it to a nullable-interface parameter.

`php tests/portability/object_casts.php` checks PHP identity, field aliasing, null and
mismatch rejection, converter output, and native C++ emission without compilation.
It verifies the checked runtime calls and polymorphic interface declaration.
The pre-tokenizer regression fixtures also pass. Compiler PHP model/AST/LLVM tests
exercise the rewritten payload consumers. No native executable was built or run.

The configured native target is unchanged. These runtime/generator changes must be
included alongside the Storage source-binding delivery before the exported compiler
can undergo native build/behavior validation. This is a completed conversion slice,
not a completed native release or general PHP RTTI compatibility claim.
