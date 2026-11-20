# Bootstrap inventory of multitype parameters and returns

Doc Status: planning

Snapshot: `cc0a9bbd7`, 20 November 2026.

Unit of count: one declared parameter or return position. The PHP token scan covered functions, methods and closures in the v0.2 compiler, with generated PHP counted separately. It excluded examples, tests and tools, skipped comments, and counted the type before a parameter name or after a return colon. Reversed arms count as the same contract. Properties, PHPDoc element types, catch clauses and arithmetic operators fall outside this inventory. Calls are verified against parameter contracts rather than counted as additional signatures.

Explicit unions and intersections account for 39 handwritten parameters and 17 handwritten returns, plus 10 generated parameters and 21 generated returns. All explicit compound parameters counted here are required and passed by value. Separately, shorthand `?T` appears on 220 handwritten parameters and 94 returns, plus 57 generated parameters and 82 returns. Those counts establish where presence occurs; native support still depends on the payload and its ownership.

## Declaration census

P/R columns count parameters and returns. Each row links one occurrence. The two diagnostic signatures differ by whether a compiler-produced binding may supply the first location.

| Declared alternatives | Hand P/R | Generated P/R | Example |
|---|---:|---:|---|
| `ast_call_expression\|ast_construction_expression` | 2/0 | 0/0 | [bind_arguments](../../compiler/04_analyze/prepare/prepare.php#L1780) |
| `ast_local_binding\|ast_global_declaration\|ast_constant_declaration\|runtime_integer_constant\|ast_enum_case` | 0/1 | 0/0 | [declaration](../../compiler/03_parse/structures/common.php#L168) |
| `ast_local_declaration\|ast_parameter_declaration\|ast_produced_binding\|ast_function_declaration\|ast_global_declaration\|ast_constant_declaration\|ast_type_declaration` | 1/0 | 0/0 | [duplicate](../../compiler/03_parse/collect/diagnostics.php#L141) |
| `ast_local_declaration\|ast_parameter_declaration\|ast_function_declaration\|ast_global_declaration\|ast_constant_declaration\|ast_type_declaration` | 1/0 | 0/0 | [duplicate](../../compiler/03_parse/collect/diagnostics.php#L141) |
| `ast_name_expression\|ast_field_expression` | 7/0 | 0/0 | [writable_property](../../compiler/04_analyze/prepare/prepare.php#L756) |
| `ast_local_declaration\|ast_foreach_statement` | 1/0 | 0/0 | [generator_destroy](../../compiler/05_backend/cpp/generator.php#L188) |
| `ast_function_declaration\|ast_global_declaration\|ast_type_declaration` | 1/0 | 0/0 | [declaration](../../compiler/05_backend/cpp/emit.php#L90) |
| `ast_function_declaration\|callback_type_definition` | 2/0 | 0/0 | [bind_arguments](../../compiler/04_analyze/prepare/prepare.php#L1780) |
| `ast_function_declaration\|runtime_function` | 0/2 | 0/0 | [function_def](../../compiler/03_parse/structures/functions.php#L221) |
| `ast_integer_expression\|ast_string_expression` | 1/0 | 0/0 | [__construct](../../compiler/03_parse/structures/enums.php#L25) |
| `ast_local_declaration\|ast_storage_write` | 4/0 | 0/0 | [__construct](../../compiler/03_parse/structures/loops.php#L53) |
| `ast_record_declaration\|ast_trait_declaration` | 1/1 | 0/0 | [attach_all](../../compiler/03_parse/structures/structs.php#L79) |
| `int\|float\|bool\|null\|constant_evaluation` | 3/4 | 0/10 | [settle_value](../../compiler/03_parse/structures/abstract.php#L265) |
| `int\|float\|bool\|null` | 3/6 | 10/0 | [constant](../../compiler/03_parse/structures/constants.php#L45) |
| `bool\|null` | 1/0 | 0/0 | [settle_scalar](../../compiler/03_parse/structures/common.php#L61) |
| `string\|bool\|null` | 0/1 | 0/0 | [scalar](../../compiler/host/compilation.php#L289) |
| `symbol_entry\|called_method_instance` | 1/0 | 0/0 | [settle_target](../../compiler/03_parse/structures/functions.php#L65) |
| `shared_record_type_definition\|class_union_type_definition` | 1/0 | 0/0 | [request](../../compiler/05_backend/cpp/object_access.php#L10) |
| `record_type_definition\|enum_type_definition` | 1/0 | 0/0 | [settle_receiver](../../compiler/03_parse/structures/structs.php#L306) |
| `exit_conversion\|int` | 3/1 | 0/0 | [settle_conversion](../../compiler/03_parse/structures/functions.php#L682) |
| `string\|false` | 1/0 | 0/0 | [scan](../../compiler/01_prepare_inputs/changes.php#L178) |
| `runtime_function\|runtime_overloads\|null` | 0/1 | 0/11 | [runtime_method](../../compiler/types/definition.php#L281) |
| `runtime_function\|runtime_overloads` | 1/0 | 0/0 | [runtime_call](../../compiler/04_analyze/prepare/prepare.php#L1230) |
| `type_definition&indexed_collection_type` | 2/0 | 0/0 | [collection](../../compiler/04_analyze/prepare/prepare.php#L831) |
| `type_definition&keyed_initializer_type` | 1/0 | 0/0 | [settle_collection](../../compiler/03_parse/structures/collections.php#L78) |

## Closure backlog in dependency order

Work one verified slice at a time. The census above remains the baseline snapshot. The first three slices establish what ordinary declaration interfaces and the existing compound ABI can express before considering a new representation.

1. **Duplicate diagnostic sites — implemented.** Both original parameters now take `ast_declaration_site`. Its three existing queries supply the name, its token position and the lexical source root. A catch binding and a conflicting local exercise different providers. The regression pins the full diagnostic including both coordinates in every flavor. Cross-unit addition, repeated rejection and deletion were compared with fresh compilation. [Diagnostic](../../compiler/03_parse/collect/diagnostics.php) · [Regression](../../compiler/tests/duplicates.php)
2. **Emitter declaration inputs — implemented.** Header publication and private prototype emission already know which declaration they are rendering. Both now call the same four typed C++ renderers for enums, records, functions and globals. In all three flavors a fixture using all four produced byte-identical headers and sources before and after the split, including after an enum edit and recovery from a deleted function. The header-dependency regression uses a bool-to-int signature edit to isolate a header-only change. [Emitter](../../compiler/05_backend/cpp/emit.php) · [Header publication](../../compiler/05_backend/cpp/headers.php) · [Rebuild proof](../../compiler/tests/unit_headers_native.py)
3. **Existing class and interface ABI against actual port shapes — next.** Eight distinct class/class pairs have a common ancestor. Together they occupy 18 handwritten parameter positions and one return. Three further inputs use two class/interface intersection contracts on collection operations. Translate representative real consumers, including a field write and a returned parser declaration. Verify presence, object identity and lifetime, branch narrowing, cross-unit dispatch and incremental ancestry changes. Shared by-value owners are the proven source ABI. Decide which compiler-internal observations should instead be borrowed at native port time. No intersection result occurs in this snapshot.
4. **Close named-callable and runtime lookup boundaries.** Cover `ast_function_declaration|runtime_function`, `ast_function_declaration|callback_type_definition`, `symbol_entry|called_method_instance`, and runtime function/overload selection with optional absence. Inspection already uses a signature interface. Verify each remaining concrete consumer, keeping source-symbol watches, specialization retirement and exact callback arity. For runtime lookup exercise a single function, an overload set and a miss. Update the generator templates if the return contract changes. Approve any new discriminated representation before implementation.
5. **Resolve the five-way name-binding return.** Inventory consumers of `ast_name_expression::declaration()` by the operation they need. Exercise locals, globals, named constants, runtime integer constants and enum cases. Preserve live symbol lookup and the reset boundary. Decide between narrower queries and an explicit native alternative only after this consumer proof.
6. **Specify scalar fact and sentinel transport.** Model known null, false, integer and float distinctly from evaluation unavailable. Respect the separate known bit and existing v0.3 uint64 debt. Check the smaller `exit_conversion|int`, `bool|null`, `string|false` and inspection-only `string|bool|null` contracts individually. Existing packed conversion codes, nullable/result carriers or the host output representation are candidates where their semantics match. Compare generated scalar handlers with their handwritten base signatures; test false, null, unavailable and the maximum representable known integer without adding size to every expression.
7. **Audit shorthand nullable types by payload, then freeze coverage.** Check written `?T` against existing scalar carriers, optional owners, borrowed references, host values and capability interfaces. Some are optional PHP implementation handles. Inspect input defaults and reference modes separately for this group. For each contract record an executable native proof, a semantics-preserving port adaptation, or an explicitly approved deferral.

## Completion criterion

Run PHP/C++/JS source fixtures for every admitted language shape, with successful calls, rejection and fresh/incremental recovery. Include a cross-file caller and callee, return forwarding, and header/ABI updates after a declaration edit. Verify exact canonical override contracts for unions and distinct slot names when the native owner is shared. Exercise native lifetime where selected alternatives outlive the call. For generator-owned changes regenerate and compare signatures at every override. Bootstrap closure means every census row has a disposition and a proof for the actual consumer. General N-arm syntax, arbitrary heterogeneous unions, union references/defaults, variance, intersection returns, compound callback components and nested union/intersection syntax each require separate evidence and policy before becoming language goals.
