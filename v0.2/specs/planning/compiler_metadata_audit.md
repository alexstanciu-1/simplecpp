# Compiler/runtime metadata redundancy audit

Doc Status: planning

Saved from the original audit supplied by the user. Code line references describe the audit-time snapshot.
Findings 1–3 are implemented (registration retains supported ABI/strategy guards).
Finding 4 is implemented: named arithmetic/conversion/storage bindings and copy/lowering safety declarations. Operand analysis remains in the emitter.
Findings 5–8 remain open.

**The main redundancy is in the handwritten API generator, more than in the compiler’s preparation code.** We generate direct handlers successfully, but several generator sections still contain a second, fixed description of which types and operations are supported.

This was a static audit of active **v0.2 compiler code, generator code/templates and runtime metadata**. No code changes or tests were run. The proposals below preserve current capabilities; moving a restriction into metadata should not silently remove it.

Priority here means value for the next refactor—not a claim that current behavior is broken.

| Priority | Finding | Classification |
|---|---|---|
| P1 | Promotion generator repeats operand, execution and result types | Existing metadata underused |
| P1 | Conversion generator repeats the conversion-pair matrix | Existing metadata plus missing strategy constraints |
| P1 | Core type registration repeats module membership, aliases and representations | Existing metadata underused |
| P2 | Native bindings and lowering safety properties are scattered | Missing metadata details |
| P2 | Echo admission and emission contain handwritten type dispatch | Missing compiler/runtime binding metadata |
| P2 | Declaration restrictions are handwritten per-type exceptions | Missing compiler capability metadata |
| P3 | Literal-family registration is partly fixed in templates | Existing metadata plus missing family descriptions |
| P3 | Entry conversion has its own handwritten runtime binding | Missing binding detail; algorithm should remain handwritten |

1. **P1 — Promotion generation is more specialized than its metadata suggests.**

   [`compilerPromotions()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:578) reads rows containing `left`, `right`, `execution_type` and `result`, but then:

   - Accepts only `int64:float64` and `float64:int64`.
   - Requires float64 execution and bool result.
   - Searches specifically for int64 → float64.
   - Emits `type_bool::instance()` and `type_float64::instance()` directly.
   - Chooses the converted operand by checking which side is int64.

   Those choices already appear in [`compiler.promotions`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/runtime/metadata/runtime.json:910).

   **Proposed change:** derive the generated class names and conversion directions from the row:

   ```text
   left conversion  = left type → execution type
   right conversion = right type → execution type
   result identity  = row.result
   ```

   Identity needs no conversion lookup. Other pairs resolve against the existing conversion definitions.

   **Metadata addition:** none is necessary for the current rounding-only slice. Before supporting checked promotions, the conversion strategy needs an explicit indication of its preparation/checking requirements.

   Keep validation that the execution type actually supports the operator and that the result agrees. Replace fixed type-name checks with those consistency checks.

   **Performance:** resolution happens during generation; application preparation can retain the same direct branches.

2. **P1 — Conversion pairs are declared twice.**

   [`compiler.conversions`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/runtime/metadata/runtime.json:795) declares the conversion pairs and strategies. However, [`compilerConversions()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:220) also contains:

   ```php
   $allowed = [
       'bool_t:int64' => 'boolean_integer',
       'int64:bool_t' => 'integer_boolean',
       // ...
       'float64:int64' => 'floating_integer',
   ];
   ```

   There is another operation-to-runtime-policy mapping in [`renderCompiler()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:120).

   The whitelist protects the bounded implementation, but adding a valid metadata row still requires editing the second matrix.

   **Proposed addition:** describe reusable conversion strategies separately from permitted pairs. For example, conceptually:

   ```json
   "numeric_floating": {
     "source_category": "numeric",
     "destination_category": "floating",
     "runtime_check": "none",
     "lowering": "native_cast"
   }
   ```

   The pair rows remain the authority for **which conversions are admitted**. Strategy constraints validate whether the implementation can handle them.

   **Keep handwritten:** the implementation of truncation checks, range checks, constant conversion and C++ cast construction. Metadata should select these algorithms, not encode them as arbitrary executable expressions.

   **Performance:** generated preparation remains specialized. This primarily removes maintenance duplication.

3. **P1 — Type registration reads metadata but also repeats a closed catalog.**

   [`renderCompiler()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:34) duplicates several facts already supplied by metadata:

   - Exact default-runtime membership.
   - Canonical type-to-generated-class mapping.
   - Allowed aliases.
   - Expected C++ representation strings.
   - Selection of literal templates by type identity.

   Relevant metadata already exists under [`compiler.modules` and `compiler.value_types`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/runtime/metadata/runtime.json:732).

   **Proposed change:** consume existing `class`, `source_names`, membership and representation fields directly, while validating supported implementation strategies and target compatibility.

   Where necessary, add a small strategy selector:

   ```json
   "literal_strategy": "decimal_binary64"
   ```

   That is more useful than repeatedly asking whether the identity is exactly `float64`.

   **Preserve:** target assertions such as IEEE binary64 requirements. These are necessary implementation constraints, not redundant metadata knowledge.

   **Performance:** generated registry lookups and singleton classes remain unchanged in shape.

4. **P2 — Runtime bindings and lowering safety properties need a clearer owner.**

   Several important details are currently handwritten:

   - [`compilerBinary()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:397) constructs helper names as `scpp::{identity}_{operator}` and supplies `scpp/arithmetic.hpp`.
   - Conversion generation hardcodes checked-conversion expressions and headers in [`compilerConversions()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:260).
   - The string template embeds [`scpp/string.hpp`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/templates/string_literal_methods.php.tpl:20).
   - A fixed type list determines [`cpp_trivial_value()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/php/render_compiler.php:138).
   - Native binary lowering is classified as reorderable by its selected strategy.

   **Proposed metadata details:**

   ```json
   {
     "binding": "float64_divide",
     "header": "scpp/arithmetic.hpp",
     "symbol": "scpp::float64_divide",
     "may_throw": true
   }
   ```

   For representations, a reviewed property such as `copy_semantics: "trivial"` could replace the fixed scalar list.

   I would prefer named bindings and strategies over free-form C++ snippets in JSON.

   **Important boundary:** an operation being nonthrowing does not make its operands reorderable. The emitter must continue combining operation properties with operand-effect analysis.

   **Performance:** this could improve classification as the type set grows, but there is no measured speedup to claim now.

5. **P2 — Echo has duplicated handwritten admission and lowering.**

   Preparation explicitly accepts string or requires int64 in [`Preparation`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/prepare.php:156).

   The emitter independently selects:

   - String-literal bytes.
   - String-object `.data()`/`.size()`.
   - Integer output.

   See [`Cpp_Emitter`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/05_backend/cpp/emit.php:257).

   There is no corresponding compiler output-capability description in the current metadata.

   **Proposed addition:**

   ```json
   "output": {
     "int64": { "strategy": "integer", "binding": "echo_int" },
     "string": { "strategy": "bytes", "binding": "echo_bytes" }
   }
   ```

   This could generate type-owned output admission and lowering handlers.

   **Keep handwritten:** evaluate-once handling, temporary lifetime management and the literal-byte optimization. Those are compiler algorithms.

   This would also provide a controlled place to add float output later, rather than expanding two handwritten type switches.

6. **P2 — Some declaration policies are missing from metadata.**

   Two concrete cases:

   - String globals are rejected by a direct type check in [`Literal_Declaration_Preparation`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/literals.php:26).
   - Inferring a local from null is rejected by another direct type check in [`Preparation`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/prepare.php:327).

   These are **current compiler support policies**, not fundamental runtime representation properties.

   **Proposed additions under the compiler metadata**, for example:

   ```json
   "inferred_local": false
   ```

   for null, and:

   ```json
   "global_storage": false
   ```

   for string.

   Generate small capability queries while keeping declaration traversal and diagnostics in preparation.

   This is useful if these exceptions grow. With only two today, it is lower priority than the generator duplication above. Moving the flags would preserve the current rejection behavior.

7. **P3 — Literal registration is only partly metadata-driven.**

   [`type_registry.php.tpl`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/tools/api_generator/templates/type_registry.php.tpl:35) hardcodes AST-family mappings for integer, float and string literals. The floating accessor is fixed to `type_float64`; other parts of registration are generated.

   **Proposed addition:** a literal-family identifier associated with the canonical type:

   ```json
   "literal_family": "floating"
   ```

   The generator can expand this into the existing direct AST-family dispatch.

   **Keep handwritten:** token recognition, cast/grouping lookahead, string decoding and source diagnostics. In particular, [`String_Literals::decode()`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/strings.php:6) implements source-language syntax. It should not be moved into runtime metadata merely because it handles strings.

8. **P3 — Entry conversion could share binding descriptions, but its algorithm is legitimate compiler code.**

   [`Cpp_Exit`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/05_backend/cpp/emit.php:6) hardcodes `scpp::entry_exit_checked`, while the emitter supplies the entry header.

   A generated runtime binding descriptor could own that symbol/header pair.

   However, [`Exit_Preparation`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/prepare.php:6) and [`entry_target`](/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/compiler/04_analyze/prepare/structures.php:38) deal with the executable’s native exit boundary and target range. I would keep that reasoning handwritten and target-aware rather than forcing it into ordinary source-type casts.

I would **not** treat the following as redundancy to eliminate:

- AST ownership, traversal, scope collection and symbol resolution.
- Watch registration, incremental queues, publication and cleanup.
- The call sites selecting `explicit_cast`, `typed_destination`, `assignment` or `inference`: syntax determines those contexts.
- Exact-type reference binding.
- Compound snapshot/evaluate/compute/store orchestration.
- Operator precedence, flavor syntax and reserved-word classification.
- Implementations of named conversion/lowering strategies.
- Validation that rejects unsupported or inconsistent metadata.

**My recommended first slice is findings 1 and 2: remove the second promotion/conversion matrix while preserving all current admissions and diagnostics.** They are closely related, recent, well-tested, and can improve the model without changing generated application behavior. Then address type registration, followed by named runtime bindings and echo.