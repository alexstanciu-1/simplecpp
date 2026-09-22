# Typed collection map and filter

Doc Status: normative

Strict PHS exposes `collection_map(input, callback)` and
`collection_filter(input, predicate)`. Native entry points are
`scpp::collections::map` and `scpp::collections::filter` in
`scpp/collections.hpp`. These are core runtime helpers.

Strict also exposes `sequence_map`, `sequence_filter`, `keyed_map` and
`keyed_filter`, with native names in `scpp::collections`. These are constrained
adapters over the same algorithms:

- Sequence adapters accept only `vector<T>` and `fixed_array<T,N>`, returning dense
  vectors. Hashes are rejected even when their integer keys are dense.
- Keyed adapters accept `hash<T,K>`, `hash<mixed>` and table-valued `mixed`, preserving
  keys/order and the result rules below. Vectors/fixed arrays are rejected.
- Dynamic handles are excluded from both adapter families; use the generic helpers
  for the existing dynamic contract. No portable dynamic expansion is implied.

STAN checks the registered carrier constraint before callback/result instantiation.
C++ constrains the native overloads as well, including when STAN is bypassed.
The generator only remaps names; it does not infer a portable receiver policy.

Both traverse the input through the runtime `foreach_range` protocol, in its
iteration order. They accept exactly one collection and one synchronous callable.
The callback receives one value, never a key, and is invoked once per visited
entry. Empty inputs produce empty outputs without invoking the callback.

## Types and result construction

Here `T` is the input element type and `U` the callback's declared result type.

| Input | Map output | Filter output |
| --- | --- | --- |
| `vector<T>` | `vector<U>` | `vector<T>` |
| `fixed_array<T,N>` | `vector<U>` | `vector<T>` |
| typed `hash<T,K>` | `hash<U,K>` | `hash<T,K>` |
| `hash<mixed>` | `hash<mixed>` | `hash<mixed>` |
| table-valued `mixed` | table-valued `mixed` | table-valued `mixed` |
| `dynamic` | `dynamic` | `dynamic` |

The default typed hash key is string. Sequence results are densely indexed from
zero. Hash/table results preserve actual keys and their iteration order; integer
keys and numeric-looking string keys remain distinct. Mixed carriers box map
results through existing `mixed_t` constructors; the callback result must be
constructible as mixed. This does not add boxing for arbitrary native containers
or class objects. Native compilation checks that storage requirement. A result must be storable in its destination: in particular the existing
typed hash implementation does not support `hash<mixed,string>` or
`hash<mixed,int>`. Use a mixed-key table input when a map must produce mixed values.

Native `dynamic_t<T,K>` follows the underlying hash policy, allocating a new
shared table. Generic `dynamic<T,K>` is not currently accepted PHS source syntax.
Arbitrary user iterables and result/wrapper carriers are excluded; unwrap a result
before passing its collection value.

A scalar, null, or non-table `mixed` input throws `scpp::runtime_error` with code
`type_error`. A null `dynamic` handle also throws. This validation runs even when
no callback could be invoked. Existing ordinary foreach behavior for scalar
mixed is unchanged; the stricter input requirement belongs to these helpers.
The dynamic foreach adapter rejects null and otherwise exposes the table entries.

## Callback discipline

Use a closure with explicit parameter and return types, or a local with a concrete
`function<U(T)>` type. Exactly one by-value parameter of type `T` is required;
implicit scalar/mixed conversions do not repair callback signature mismatches.
A filter predicate must return `bool` (`scpp::bool_t` natively). Variadic/defaulted
parameters, source reference parameters, reference returns, and void map results
are unsupported. Native callbacks accept exactly `T` or `const T&`: the latter
accommodates existing lowering of string/container value parameters. Both receive
a copied entry value; mutable references and implicit element conversions remain
rejected. A callback must not retain a reference to that temporary value. Helpers invoke the supplied callable directly and do not retain it
or add a `std::function` conversion; existing callable-local lowering may use
`std::function` storage.

Callbacks must not mutate the input, its aliases, shared elements, captured state,
or objects reachable through captured state. By-value captures may be read;
reference captures are outside this contract. Fresh output objects may be
allocated and populated. This is application discipline, not a new global purity
or capture-effects analysis.

Exceptions propagate. Partial output is destroyed through ordinary runtime
ownership. Every output has fresh outer storage/identity; nested values retain
their ordinary copy/shared-handle semantics. Filtering copies the selected input
value, and does not replace it with a callback result.

## PHS examples and analysis

```php
$numbers vector<int> = [1, 2, 3];
$suffix string = "!";
$labels = collection_map($numbers, function (int $n) use ($suffix): string {
    return "item" . $suffix;
});
$predicate = function (int $n): bool {
    return $n > 1;
};
$selected = collection_filter($numbers, $predicate);

$state dynamic = ["count" => 1];
$copy dynamic = collection_filter($state, function (mixed $value): bool {
    return true;
});
```

STAN derives `vector<string>` for `$labels` and checks callback compatibility and
result boundaries using the instantiated runtime call contract. Function calls
inside arguments, returns and element expressions follow the same resolution.
The S2S generator remains structural. Declare dynamic result locals explicitly
(as `$copy dynamic` above) when using their `[]` surface: generator lowering needs
that authored carrier annotation to select shared-table access. Use explicit
types at other boundaries that require structural knowledge of the carrier.

These names do not implement PHP `array_map`/`array_filter` compatibility.
Portable PHP framework bindings and compiler migration are separate work.

## Builtin reference entries

- [collection_map](collections/collection_map.md)
- [collection_filter](collections/collection_filter.md)

- [sequence_map](collections/sequence_map.md)
- [sequence_filter](collections/sequence_filter.md)
- [keyed_map](collections/keyed_map.md)
- [keyed_filter](collections/keyed_filter.md)
