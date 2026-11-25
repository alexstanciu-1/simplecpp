# Native C++ argument lowering and source expansion inventory

Doc Status: planning

Scope is the active v0.2 application C++ backend shared by cpp/php/js. The inventory follows the emitter and its generated type handlers, including file-level output. Runtime library implementations, historical generators and unimplemented source features are outside this inventory.

The [C++ emission audit and refactor plan](cpp_emission_refactor.md) records the current ownership assessment, correctness findings and proposed migration sequence.

## Ownership and first correction

Preparation owns source admission, destination type, approved conversion and any proof that a constant is in range. Runtime publication owns member signatures, adapters and scalar input fences. Target layout facts identify which C++ fundamental type implements an exported alias. Generate the target-specific decisions once. Application lowering consumes prepared facts and generated handlers.

For a strict member call, the question is whether the C++ argument selects the intended overload. For a typed initialization, the question is whether ordinary initialization yields the approved value. Those decisions can differ for the same two source types. Preserve checked conversions when native conversion has different failure behavior. Fold only a literal whose conversion preparation has approved. A known value on an arbitrary expression is insufficient authority to discard its evaluation.

The first consumer is the canonical published vector `set(count, element)` row with adaptation `checked_index_move_value`. Its argument identities already exist in the prepared member metadata. Native literals at this boundary use the target layout probe's signed integer identity. Ordinary integer literal rendering elsewhere still uses the configured carrier suffix. Compound integer expressions with an uncertain C++ intermediate can therefore still need normalization. A later migration can unify the literal policy and remove that fallback after auditing expressions with native integral promotions.

| Source with int64 index and element | Current target output, with readable names |
| --- | --- |
| `v[i] = x` with typed locals | `v.set(i, x);` |
| `v[0] = 9.75` | `v.set(0L, 9L);` on a target with long int64 |
| `v[i] = flag` with a bool local | `v.set(i, static_cast<scpp::int64>(flag));` |
| `v[index()] = rhs()` with an unknown float | `v.set(index(), scpp::checked_float64_to_int64(rhs()));` |

Each argument is emitted once. C++ chooses the argument evaluation order. Relative effects and which failing argument is observed first remain evaluation-order debt. Both arguments must succeed before entering the member that checks bounds and writes the slot. [Evaluation-order decision](evaluation_order_debt.md)

## Lowering inventory

These rows group sites by the rule responsible for the extra emitted C++. A candidate needs a native behavior proof before changing it.

| Area and representative expansion | Correction or reason to retain | Owner |
| --- | --- | --- |
| Vector set, append and indexed reads. Casts to the count and element types, plus two store temporaries. | Set corrected. Next apply exact-argument emission to append and at. Retain overload selection, element conversion and runtime bounds checks. | [Vector binding template](../../tools/api_generator/templates/collections/vector_type.php.tpl) |
| Hash reads/writes and collection cardinality. Key/value casts, ordered temporaries, parentheses around receivers. | Audit casts for exact typed operands. Keep key identity, hash replacement semantics and existing sequencing pending review. Count and empty are already one member call. | [Hash bindings](../../tools/api_generator/templates/collections/hash_type.php.tpl), [cardinality](../../tools/api_generator/templates/collections/collection_cardinality.php.tpl) |
| Boxed indexing, scalar boxing/extraction, operators and conversions. Key wrappers and runtime calls. | Dynamic tag dispatch, missing-key behavior, conversion errors and single evaluation are required. Review already-exact scalar arguments separately with the boxed owner. | [Boxed type](../../compiler/types/boxed.php), [generated bindings](../../tools/api_generator/templates/boxed/compiler_boxed.php.tpl) |
| Local/global initialization, assignment, returns, inferred locals and casts. Explicit destination casts or checked helper arguments. | Prioritize native initialization and return sites. Keep range checks, exact storage, global constant initialization, explicit cast meaning and warning-clean code. Float64 checked helpers now accept the emitted double directly. Integer helper normalization remains to review. | [Declarations and returns](../../compiler/05_backend/cpp/emit.php), [conversion generator](../../tools/api_generator/php/render_compiler.php) |
| Integer, floating and string literals, constant references. Wide suffixes, minimum-value expression, hexadecimal floats, octal escapes and repeated inlined initializers. | Review general native suffixes and printable string bytes. Preserve minimum signed values, exact binary64 bits, signed zero, embedded NUL and escape boundaries. Audit repeated large constants separately. | [Integer template](../../tools/api_generator/templates/scalars/signed_integer.php.tpl), [floating template](../../tools/api_generator/templates/scalars/floating_literal_methods.php.tpl), [string spelling](../../compiler/05_backend/cpp/strings.php) |
| Unary and binary arithmetic, comparisons, strict identity, three-way comparison and promotion. Operand/result casts, parenthesized expressions, sequencing lambdas. | Keep narrow-integer promotion/write-back rules, exact mixed comparisons, checked division, shifts and power. Review redundant parentheses/casts and binary snapshots with two harmless operands. | [Value emitter](../../compiler/05_backend/cpp/emit.php), [operator strategies](../../tools/api_generator/php/render_compiler.php) |
| Conditions, negation, eager and lazy logical expressions. Truth conversions, nested grouping and eager XOR temporaries. | Native bool conditions and lazy short-circuit paths can be direct. Preserve operand effects, carrier truth behavior and short circuiting. Review eager sequencing separately. | [Conditions and logical emission](../../compiler/05_backend/cpp/emit.php) |
| Compound assignment, prefix/postfix updates and for-loop headers. Prior-value temporary, explicit binary operation, checked write-back and occasionally an extra block. | Consider native compound syntax where execution type, overflow and write-back agree. Check reference aliasing and both error and loop-continue paths before removing a snapshot. Simple branch, loop and transfer statements already map directly. | [Statement and binding emitter](../../compiler/05_backend/cpp/emit.php) |
| Function calls, omitted defaults and references. Individual argument bindings, value copies, rvalue casts and an immediately invoked lambda. | Existing trivial by-value calls are direct. Review single-argument cases first. Keep reference binding, default expression behavior, object ownership and required failing conversions. Runtime-function calls already have a separate direct path. | [Call emitter](../../compiler/05_backend/cpp/emit.php), [existing direct-lowering proofs](../../compiler/docs/direct_lowering.md) |
| Nullable/result construction, extraction, presence and truth. Factory calls, payload casts, state tests and lambdas retaining a carrier. | Preserve sentinel distinctions, checked extraction, lifetime and one evaluation. Review direct stable local reads and redundant payload casts. | [Carrier template](../../tools/api_generator/templates/carriers/carrier_type.php.tpl), [conversion transport](../../compiler/types/definition.php) |
| Echo, string operations and temporaries. Byte-span helper, retained string reference, length and data queries, concatenation/comparison helpers. | Literal output already avoids constructing a string. A computed string needs a lifetime covering both queries. Review direct output of stable locals. Preserve complete byte sequences and single evaluation. | [Output emitter](../../compiler/05_backend/cpp/output.php), [generated lowering](../../tools/api_generator/php/render_compiler.php) |
| Entry return and project entry wrapper. Cast or checked exit helper, target assertion and forwarding main. | Keep process exit range policy, target agreement and separate compilation. Review proven constant returns for direct spelling. | [Entry emitter](../../compiler/05_backend/cpp/emit.php), [program assembly](../../compiler/05_backend/cpp/program.php) |
| File-level declarations and headers. Per-unit prototypes, externs, runtime includes, generated names, annotations and empty secondary units. | Keep declaration visibility, stable linkage, warning cleanliness and incremental rebuild boundaries. Measure redundant prototypes and header parse costs separately from expression text. | [Unit builder](../../compiler/05_backend/cpp/emit.php), [header selection](../../compiler/05_backend/cpp/headers.php) |

## Verification and sequence

1. Verify the four set forms in emitted output across cpp/php/js, native behavior, unchanged contents after failed conversion or bounds checks, and a build against a relocated provider. Record a source-size baseline separately from any compiler timing.
2. Extend the same strict argument treatment to vector append/read, then typed hashes. Test alternate native representations and published native adapters before changing shared literal defaults.
3. Review ordinary typed destinations and runtime helper arguments. Preserve the distinction between a conversion that native C++ implements exactly and a conversion that requires a guard.
4. Take one remaining inventory family at a time. For a build-time claim, compare identical generated applications with Clang and GCC, fixed flags, compiler versions, include trees and cache state. Report frontend, code generation and link time separately where available.

The generator regression checks both long and long-long int64 literal spellings,
including the signed minimum. The four emitted forms are exercised in all three
flavors. Focused Clang native and provider tests include conversion failures,
incremental recovery and relocated headers. GCC also syntax-checked the emitted
cpp-flavor exact-argument fixture against the published headers.

The cpp replacement fixture’s four store statements occupy 226 bytes after this change, down from 808. This counts stripped statement lines, including their argument spellings, on the current LP64 target. It measures emitted text only.
