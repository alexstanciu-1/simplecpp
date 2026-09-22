# Runtime byte literals and output
Doc Status: supporting

Status: implemented for quoted literals, inline local construction/copying and
`echo`, through the runtime preparation tool and ordinary compiler call pipeline.
This is the first part of **Runtime strings and console**.
[Named integer-to-string conversion](conversion_selection.md) and the remaining
[bounded console surface](runtime_console.md) are now implemented in follow-up
slices, completing that foundation.

## Provider contracts, not type-name dispatch

The default [definitions](../../src-runtime-preparation/definitions/strings.json)
select `scpp::string_t` and the real Simple C++ `scpp::php::echo_one` implementation.
Those names occur in provider configuration, not compiler processing. Adding a
C++ type with the same supported contracts requires only additional definitions
and preparation. Unsupported capabilities require their own implementation.

The [native proof](../../tests/integration/runtime_strings.php) adds a
second, over-aligned type with independent heap-owned contents and different output
behavior. Both types coexist in one package; the second type uses the same source,
ABI, copy and cleanup paths without adding compiler cases.

### Definition vocabulary

- A `byte_span` type declares an alias of `std::string_view`. Preparation verifies
  that C++ primitive and measures its facts; the compiler represents a borrowed byte
  sequence, independently of native C++ object layout. It is currently a staging
  value for literal call operands, not general source storage or aggregate passing.
- A `void` type declares a C++ void alias and binds the existing language void
  definition. It has no value storage, result value or cleanup obligation.
- `construct_from_bytes` may declare `parameter_type` referencing the span type.
  Its semantic input is one call-scoped borrowed span. Its physical ABI is
  `void(destination, bytes, length)`, with unsigned length width measured from Clang.
- `free_function` parameters retain integer type-ID shorthand. An object parameter
  uses `{"type":"item","passing":"const_address","borrow_scope":"call"}`.
  Results may be declared integers or void. The tool compiles an exact C++ function
  pointer signature; borrowing adapts an ABI pointer to a const C++ reference.
- `language_binding: "byte_literal"` attaches literal construction to an exposed
  span constructor. `default_literal: true` selects the unique uncontextualized
  literal constructor. A type may have only one binding for a given language role.
- `language_binding: "echo"` attaches output to an exposed function taking one
  borrowed object and returning void. Ordinary calls to that function use the same
  signature and physical ABI as the `echo` syntax.

`expose_as` provides actual source callable declarations. Language bindings refer
to these same declarations; there are no fabricated helper names or a second ABI
pipeline. The adapter rejects malformed ownership, passing, role/result shape and
ambiguous bindings before exposing a package. The type stage indexes accepted
bindings by language role and canonical type ID.

## Source behavior

```text
$message string = "hello\n";
$copy string = $message;
echo $message, $copy;
```

A quoted literal uses the declared default constructor when no expected type is
available. A local initializer or call argument can select that expected type's
explicit byte-literal binding. This is provider permission for literal construction,
not an implicit conversion between existing objects. A parameter requiring a raw
byte span receives the decoded literal bytes directly.

Single quotes support escaped quote/backslash and preserve other backslashes.
Double quotes support the standard simple byte escapes, octal and hexadecimal byte
escapes. Literal UTF-8 bytes, embedded zero bytes and empty contents are preserved.
Interpolation and `\u{...}` escapes produce explicit source diagnostics in this
slice. Integers are not automatically converted for echo; an unsupported operand
has no output binding and is rejected.

Simple C++ generation rules lower comma-separated echo operands into sequential
`echo_one` calls. Each operand therefore becomes its own checked call/full-expression
boundary. Output order and temporary cleanup follow that sequence; no newline is
added implicitly. The ordinary lifetime stage owns temporary and local destruction.
Bridge failures retain the terminate policy.

## Owners and representation

| Owner | Responsibility |
|---|---|
| `src-runtime-preparation/definitions.php`, `bridge.php`, `metadata.php` | Validate supported roles, generate exact C++ adaptations and verify physical signatures across ordinary/full/ThinLTO modules. |
| `load_runtime/Package_Adapter` | Normalize result, parameter expansion and language bindings into shared compiler contracts. Validate whole-package binding uniqueness. |
| `tokenize/File_Tokenizer`, `parse/File_Parser` | Preserve quoted source spans and ordered echo operands in the flat frontend. No runtime type names or helper lookups. |
| `resolve_types/Type_Resolution` | Index language-bound callables by role/type after signature acceptance. Workers query this fixed index without shared writes. |
| `check_bodies/Byte_Literals`, `Body_Worker` | Decode bytes, select an authorized constructor/output declaration and produce ordinary checked calls with exact signature dependencies. |
| `analyze_lifetimes` | End span/object access at the consuming call; schedule object cleanup through the existing full-expression/scope rules. No string-specific lifetime path. |
| `lower/Callable_Preparer`, `LLVM_Types` | Expand one semantic span parameter into pointer plus measured length. Existing scalar and object-address arguments keep their own modes. |
| `emit_llvm/Emission_Worker` | Emit immutable byte constants and pass address/length through the common ABI call formatter. No runtime names or hand-coded string layouts. |

Checked and lowered byte literals share one byte payload. JSON debug output encodes
it as hexadecimal on demand so arbitrary bytes do not corrupt exports. Token/AST
rows retain source offsets, not copied literal contents. LLVM constant names encode
the existing callable ID and body-local value ID exactly; no hashes allocate
identities. Constants belong to the function's emitted output and its file module,
so a body edit replaces them within existing incremental boundaries. No shared
literal interning table or mutable worker-global allocator was added.

## Proof and limitations

The integration fixture compares stdout, lifecycle diagnostics and status against
native C++ using the same provider types. It covers real strings and a second type,
empty/binary/UTF-8/escaped contents, contextual construction, explicit calls,
copying, nested temporaries, loops and both return paths. A full build followed by
one literal-body edit replaces the affected plans and native object while retaining
unchanged caller and unrelated literal modules and prior snapshots. Reversed worker
completion reproduces exports; common lifecycle preparation joins retain their
existing purity and invalidation proofs. Negative tests cover missing output
capabilities, unsupported syntax and malformed/ambiguous provider contracts.

Preparation remains a runtime-version/configuration activity. Applications consume
the package; they do not regenerate bridges per source file. Production linking
still uses ordinary bitcode. Existing full/ThinLTO tests prove compatible artifacts
and compiler consumer flows, not production LTO scheduling or performance.
