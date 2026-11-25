# Declared function signatures
Doc Status: supporting

Status: declared return and parameter annotations resolve in the PHP prototype.
Scalar callables, including typed positional parameters, continue through body
checking, lifetime analysis, backend preparation and native lowering.
Signature and [local annotation resolution](local_type_resolution.md) prepare
the fixed inputs for [declared body checking](body_checking.md).
The signature stage itself does not check expressions or control flow. The
[selected implicit entry](program_entry.md) obtains its return contract from
language metadata; supporting-file entries have no execution contract. Lifetime,
layout and native startup/ABI adaptation remain later work.

The [organization and extension points](analysis_handlers.md#type-resolution)
describe shared bound-annotation consumption, method-level materialization dispatch and
the data/utility folders. Signature and local joins retain separate files.

## Authoritative initial definitions

[`language/named_types.json`](../../language/named_types.json)
is the compiler's bundled language catalog for the initial named types:

| Name | Language value representation |
|---|---|
| `void` | No returned value |
| `int` | Signed 64-bit integer |
| `uint32` | Unsigned 32-bit integer |
| `float` | IEEE binary64 floating point |

These are language definitions, not fixture mappings or host-PHP deductions.
They are transcribed from the configured Simple C++ language/runtime authorities:

- [Runtime configuration](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/specs/config.json):
  `types.int_t.cpp_underlying = std::int64_t` and
  `types.float_t.cpp_underlying = double`.
- [Source type mapper](../../../../../simple_cpp_compiler/vendor/simple_cpp/generators/php/src/Lowering/TypeMapper.php):
  `int` maps to `int_t<>`, `uint32` to `int_t<std::uint32_t>`, `float` to
  `float_t`, and `void` to `void`.
- [Compact type specification](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/compact_layout_types.md)
  defines fixed-width integer source names. The runtime
  [default integer representation](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/detail.hpp)
  and [64-bit floating wrapper](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/include/scpp/float_t.hpp)
  corroborate the mapping.

The bundled subset is versioned with this compiler; it does not depend on a
sibling checkout at execution time or silently follow external toolchain edits.
Other scalar names, aliases and type families remain unsupported until their
contracts are added. Recognizing an annotation does not advertise implemented
arithmetic, conversions or a native calling convention.

`Language_Types` reads/validates this catalog. Equivalent content reuses the
previous immutable catalog. Names index shared `named_type_definition` records;
signedness remains a language fact separate from the integer representation.
Definitions also require the explicit shared [lifetime contract](type_storage.md#actions-and-later-boundaries):
scalar value copying with no cleanup, or no value lifetime for void.
Materialization binds that shared definition to its canonical type row. Frontend
and later lowering consumers use `definition_for_type(id)` to retrieve its
complete meaning without another name lookup; same-width signed/unsigned types
may share a representation. Definition invalidation clears both the semantic
binding and representation. A provider signedness edit changes the context even
if the storage shape stays equal.
Materialization dispatches on representation kind, never on concrete type names.
Unsupported kinds/formats/fields and duplicate definitions are rejected.

## Pipeline and ownership

After manifest reading and discovery, the coordinator checks type-cache validity
before any frontend worker starts. Candidate preparation follows incremental
admission, using the final full-selection decision. Configuration identity includes manifest
path, directory and contents; provider identity hashes the exact catalog bytes.
`language_values` explicitly denotes target-independent value descriptions,
not an assumed host architecture. Future target-dependent contracts must supply
their real target identity when introduced.

An initial request, catalog/configuration change or existing full-rebuild flag
starts an empty cache and selects the common stages in full. A compatible update
clones the type store and shares immutable rows. Fresh caches have a new ID
lineage: old typed bindings cannot be carried into them by numeric ID equality.

After project symbol collection, declaration/body name binding and syntax comparison:

`Type_Resolver` owns this phase. After selecting both signature and local
tasks, it materializes the catalog's
default integer-literal type. Annotations reuse it; the later body phase obtains
its ID through `Type_Resolution::integer_literal_type()` without materialization.

1. `Signature_Resolver::select` chooses named functions and the selected entry
   missing a valid result, callables in replaced ASTs, or all participating
   callables when `full_rebuild` is set.
2. Each worker reads fixed symbol/syntax/catalog and accepted binding inputs, follows the explicit
   return and ordered parameter annotations (or reads the entry contract), and
   returns a `signature_request` referring to its symbol, return source node and
   shared authoritative definitions. Unknown names have already been rejected at
   their source span during binding. Workers do not write the global type store.
3. The coordinator validates tasks/results and completeness before populating
   the candidate. In deterministic symbol order, `Type_Cache::materialize`
   completes each missing named representation once. Repeated uses reuse its ID.
   Provenance checks follow accepted annotation bindings and provider membership;
   the join does not rerun the resolver or create another worker result.
4. The join interns the return/ordered-parameter function-signature shape and creates a
   `Callable_Signature` binding associated with the symbol, exact AST and body
   anchor. An implicit entry has zero declaration/annotation IDs because there
   are no such source nodes. Bodies are not copied or checked here. Removed
   functions and unselected file entries contribute no current signature.
5. After the signature and local joins return their associations, `refresh`
   assembles one complete `Type_Resolution`. The body phase reads its fixed
   signatures and type store. The session accepts
   inputs, symbols, call bindings, types and checked bodies together only after
   success. Failure retains the previous accepted baseline.

`Type_Resolution` owns the type snapshot, catalog, entry contract and signature index together.
A signature stores its representation ID; its ordered member range holds
parameter type IDs, without copied names, definitions or actions.
`Type_Resolution::parameter_type_for(symbol_id, position)` reads a one-based
parameter position. The local join copies these small IDs into the binding
prefix and resolves only the body-local suffix independently. Reuse requires exact syntax/declaration provenance and shared current
representation/type rows, including types used only by parameters. Reparsed files refresh anchors even if the signature
shape stays equal. The later body phase tracks consumed signature shapes and
rechecks callers when a callee's annotation changes; signature resolution itself
does not traverse their bodies.

`--debug=json` adds `types.catalog`, `types.types` and `types.signatures` to the
existing output. Signature exports include symbol/source IDs, annotation nodes,
shared signature representation IDs and derived return-type IDs. Normal runs
remain quiet. The program is not executable and no generation is published.

## Proof

[`return_types.php`](../../tests/04_analyze/resolve_types/return_types.php) covers cross-file
sharing, one actual materialization for repeated annotations, reversed workers,
join failures and purity, unchanged reuse, annotation/body edits, precise unknown
type diagnostics, failure/repair, all four initial types, a test-supplied name and
width through the same path, provider removal/change, early full selection,
fresh-build meaning equivalence and exports. Body compatibility, including
failure/repair after a callee annotation edit, is proved in the separate
[body checks](../../tests/04_analyze/check_bodies/body_checking.php).
CLI simulation also verifies exported signatures and the new stopping point.

## Next boundary: body rules

The agreed [literal/return rules](body_checking.md) are now implemented for the
integer/call grammar. Integer literals use the catalog's `int` default; fractional
literals will use `float`. A single conversion resolver supports identity and
[same-family, same-signed integer widening](integer_conversions.md). Other
conversions remain unsupported.

The external [fixed-width integer plan](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/planning/fixed_width_integer_first_slice_2026_06_26.md)
remains planning, not semantic authority. The implemented widening rule follows
the current strict-authoring skill; broader proposals and native C++ coercions
are not adopted implicitly. Entry returns, scalar lifetimes and native lowering
are implemented for the supported subset.

## Parameter boundary and proof

Parameters require value types: unknown annotations and `void` produce errors at
the type-name span, even when unused. No conversion, argument count, runtime
order, lifetime passing or ABI rule is inferred from a resolved signature.
Parameterized functions and calls with arguments now check through the shared
body process, scalar lifetime analysis and the prepared native lowering path.
Parameter annotation/order/count edits are definition changes and retain the
existing full fallback; body-only edits refresh AST associations while sharing
unchanged signature representations.

Debug signature rows now include `parameter_type_ids` in declaration order.
Direct type-stage exports can inspect these facts while the ordinary compiler
still rejects unsupported bodies; failed builds do not publish partial results.
[Parameter type proofs](../../tests/04_analyze/resolve_types/parameter_types.php) cover repeated
and cross-file annotations, provider-defined types, shared local/signature IDs,
reversed independent workers, one materialization per definition, warm reuse,
parameter-only dependency invalidation, edits/removal, stale/malformed joins,
unknown/void diagnostics, exports, wide lists and failure followed by native repair.
