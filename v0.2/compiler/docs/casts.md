# Explicit scalar casts

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#explicit-scalar-cast-expressions--2026-11-05)
defines `(Type) operand` in cpp/php/js. The existing generated conversion table is
unchanged: written casts request the same explicit intent as typed destinations.
String and null identity are supported; text conversions, nullable/container types,
void casts and alternative cast spellings remain deferred. This is Simple C++
conversion syntax in JS flavor, not a TypeScript assertion.

## Ownership and efficient preparation

`ast_cast_expression` owns its written `type_name` and operand, plus one prepared
`value_conversion` decision. The inherited expression facts retain the canonical
result type and any converted scalar payload. The operand owns its own facts;
string bytes are not copied into a second preparation record.

Parser lookahead recognizes registered scalar names followed by an operand and
leaves ordinary grouping intact. It uses the existing generated name registry and
retains only syntax. Casts share unary precedence with negation; general numeric
unary operators remain outside this slice. No speculative declaration collection
or parser rollback is needed.

Preparation visits the operand once, resolves the destination through the generated
registry, and calls `prepare_conversion(operand, conversion_context::explicit_cast)`. It then attaches the approved
decision and converted constant facts. No extra traversal, conversion matrix,
watcher category, interpreter or work queue is introduced. Existing collection and
reset traversals follow the operand; inspection reports syntax and conversion facts.

Lowering consumes that decision through `lower_cpp_conversion`, requests public
representation/conversion headers, and emits the operand once. This adds no
cast-specific sequencing lambda. Existing surrounding expression sequencing remains
unchanged. An identity scalar cast can retain its operand's safe lowering property;
other conversions remain conservative. Casts never qualify as mutable-reference
arguments, even when their lowered identity expression happens to name storage.

Incremental invalidation remains on the enclosing body's existing symbol watches.
Callee signature changes and constant type/value/removal changes reset cast facts
with the body. Unchanged AST/tokens are reused; a callee body-only edit does not
invalidate callers. Incremental native emission remains outside this slice.

## Mining and verification

Reviewed `specs/dynamic_types.md` explicit typed boundaries, the scalar contract,
and donor `03_parse/parser.php` cast/group lookahead plus
`04_analyze/prepare/semantics/expressions.php::prepare_cast`. Retained the concepts
of unary binding, explicit destination admission and one operand visit. Did not
adopt the donor's separate prepared-cast object or general type-syntax parser.

`tests/casts.php` checks all 36 source/destination pairs for the six current type
identities, including supported pairs in native fixtures. It also covers nesting,
precedence/grouping, aliases, string bytes, typed arguments/returns, conditions,
assignment, range failures, void/unsupported pairs, reference rejection, self-read,
AST ownership, exact expression visit counts, inspection and emission purity.
Reset/reprepare and incremental signature/value/target edits, failure/recovery,
constant removal and no-op reuse are checked for all three flavors.

`tests/casts_native.py` uses the existing instrumented Clang harness. The twelve
programs cover ordinary casts, single operand evaluation, unknown int-to-byte and
float-to-int runtime failures, and multifile recovery in each flavor. The exception
catch is test-only C++, not new source exception syntax.


## Recorded proof — 2026-11-05

All 45 selected compiler CTest checks passed (`^compiler_`, excluding native suites
and the persistent-service harness); compiler style checks passed for 114 PHP files.
The twelve cast native programs passed. Raw native inputs, generated C++, traces,
logs and the regression report are retained locally under
`v0.2/build/casts-proof-2026-11-05/` (ignored build output).

An initial native run overlapped the regression suite; the table uses a fresh
repeat without that competing command. These are single instrumented builds,
not medians or a before/after speedup claim. O2/g0, libc++, Ninja8, mold, no PCH/LTO,
prebuilt runtime. Trace durations are not additive wall time; optimizer/codegen
are parts of LLVM work. Link time measures the link command.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 152.47 | 16.18 | 14.45 | 4.61 | 8.06 | 70.00 |
| php | 139.86 | 20.07 | 11.75 | 4.33 | 5.80 | 63.00 |
| js | 132.90 | 14.97 | 9.95 | 3.52 | 5.09 | 62.00 |

Full PHP fixture generation and assertions took 114.65 ms. This covers multiple
compilations, all conversion pairs and incremental failure/recovery; it is not an
isolated preparation-stage benchmark.
