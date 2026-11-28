# Indexing

Doc Status: normative

- `base[expression]` invokes the one-argument form of `operator[]`.
- `base[]` invokes the zero-argument form of the same operator.

The optional argument is an ordinary expression. Empty brackets supply no argument;
they do not supply a null value, sentinel, implicit index or append instruction.
Multiple comma-separated bracket arguments are outside this slice.

The selected type/operator contract must determine the operation's meaning, result
type, effects and result access properties. The compiler must not infer append,
write-only use, container mutation, integer indexing or writability solely from
bracket syntax. A future overload may give the zero-argument form any supported
meaning. General user-defined operator declarations and resolution are separate work.

For vector, hash and fixed-array reads, preparation applies the provider’s key
rule to the prepared index expression and assigns the provider’s entry value type
to the read. Each reached receiver and key evaluates once. A failed lookup in
a nested read prevents evaluation of the next key.

For a nested write through vectors or hashes, the concrete provider binds a
checked slot to its key and stored value type. It borrows an existing element
from a mutable lvalue for immediate access. Missing positions and keys raise
the usual lookup error. Across key or RHS effects the compiler retains the
root and reevaluates the lookup with saved keys. Writable foreach uses its
own traversal position binding.

## Optional key inputs

A typed optional key expression uses the existing checked by-value argument conversion
into the selected operation's required key type. Its payload must satisfy the existing
key-domain rules, and any object ancestry conversion must be independently admitted.
Absence fails before that indexed step or a write's value expression; it does not
become a null key. This applies to reads, writes, nested paths, probes and removal,
while preserving lazy probe-key evaluation. It does not admit nullable stored key
types or PHP bool/float/null key coercion. The focused nullable_keys.py proof covers
ordinary and prelowered execution, identity upcasts and failure ordering.

## String byte reads in v0.2

For the v0.2 compiler the published string read supersedes the historical
runtime string-index reservation. In every flavor,
`text[i]` takes an int/int64 byte offset and returns a copied byte/uint8 in
0..255. The valid interval is `0 <= i < strlen(text)`. An invalid offset,
including any offset into an empty string, throws `std::out_of_range` with
code `sequence_index_out_of_range`. UTF-8 bytes and embedded NUL are ordinary
payload bytes. The trailing storage terminator is outside this interval.
A writable string local or parameter also supports `text[i] = value`. The key is
evaluated once before the right-hand side. The typed byte destination uses the
ordinary checked conversions. After conversion the same read bounds apply to the
current string; a successful store replaces one byte in place, preserving length
and storage form. A conversion or bounds failure performs no store. Ordinary
value copies remain independent. Append brackets, compound stores and references
to individual bytes are deferred.
