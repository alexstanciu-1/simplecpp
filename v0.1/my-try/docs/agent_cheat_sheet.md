# my-try cheat sheet
Doc Status: supporting
Readiness: draft — not ready for agent use or task routing.

Draft exploration of a possible agent orientation format. Do not rely on it; use the
documentation index and owning guides instead.

## Model tree

```text
Model
├── modules: Keyed_Storage<module>
│   └── module.sources: Keyed_Storage<source_record>
│       └── source_record
│           ├── file
│           ├── tokens: token_list
│           │   ├── tokens: Storage<token>
│           │   └── retained_ranges: Storage<retained_token_range>
│           └── parsed: parsed_file
│               ├── root: file_node
│               ├── scopes: Storage<scope>
│               └── collection: collected_file
│                   ├── entries: Storage<collected_name>
│                   ├── indexes: declaration/reference positions
│                   └── preparation_changes: preparation_owner identities
├── language_scope / global_scope
├── type_catalog: registered_type_catalog
├── cpp_type_bindings: CPP_Type_Bindings
├── prepared_files: Storage<prepared_file>
├── cpp_output_program: cpp_program
│   └── fragments: preparation_owner ↔ cpp_fragment
├── cpp_files: Storage<cpp_module>
└── llvm_files: Storage<llvm_module>  [parked]
```

```text
file_node (typed AST)
├── declarations: function_node, struct_node, field_node, parameter_node
├── executable bodies: function_body_node, block_node
├── statements: if/loop/switch/return/local-declaration/expression
├── expressions: literals/references/calls/operators/assignments/conditional/match
└── types: named/nullable/array/template-application/type-use-modifier

collected identities
├── independently prepared definitions: collected_function, collected_struct
├── member/local declarations: collected_field, collected_parameter, collected_variable
└── unresolved occurrences: collected_*_reference, collected_variable_write

attached preparation facts
├── work: function_signature_work, record_definition_work, function_body_work, file_body_work
└── facts: prepared_function, prepared_record, prepared_storage, prepared_expression, prepared_call
```

## Retained record inventory

### Roots and input

```text
Model: {modules: Keyed_Storage<module>, revision: uint32, modules_ready: bool, full_sync_pending: bool, rebuild_required: bool, global_scope: scope, language_scope: scope, type_catalog: registered_type_catalog, cpp_type_bindings: CPP_Type_Bindings, llvm_files: Storage<llvm_module>, prepared_files: Storage<prepared_file>, cpp_files: Storage<cpp_module>, cpp_output_program: cpp_program}
module: {name: string, declared_path: string, resolved_path: string, position: int, disk_source: bool, changes: change_state, revision: uint32, sources: Keyed_Storage<source_record>}
source_record: {path: string, changes: change_state, revision: uint32, module_reference: weak<module>, file: file, tokens: ?token_list, parsed: ?parsed_file}
file: {changes: int, path: string, mtime: int, size: int, content: string, disk_source: bool, tokens: ?token_list}
token_list: {file: file, content: string, first_token: int, end_token: int, content_offset: int, retained_ranges: Storage<retained_token_range>, tokens: Storage<token>}
token: {offset: uint32, length: uint32, text: string}
retained_token_range: {first: int, end: int, current_first: int}
parsed_file: {complete: bool, tokens: token_list, root: file_node, collection: collected_file, scopes: Storage<scope>}
```

### Scope and collected identities

```text
scope: {enclosing: weak<?scope>, publication: weak<?scope>, function_boundary: bool, preparation_lookups: Key_Storage_List<preparation_lookup>, template_parameters: hash<int>, variables: Key_Storage_List<collected_name>, functions: Key_Storage_List<collected_function>, types: Key_Storage_List<type_definition_i>, constants: Key_Storage_List<constant_definition>}
collected_file: {parse_complete: bool, deleted: bool, prepared: ?prepared_file, preparation_changes: SplObjectStorage<preparation_owner>, revision: uint32, tokens: token_list, root: file_node, entries: Storage<collected_name>, defined_elements: vector<int>, variable_references: vector<int>, constant_references: vector<int>, function_references: vector<int>, type_references: vector<int>, field_references: vector<int>, pending_bindings: vector<int>}
collected_name: {revision: uint32, change_status: change_state, changes: int, collection: weak<collected_file>, local_index: int, name: string, scope: weak<scope>, token_index: int}
collected_declaration: {+ collected_name, retained_symbol: bool, exported: bool}
collected_definition: {+ collected_declaration, preparation: ?declaration_work}
collected_function: {+ collected_definition, syntax_node: function_node}
collected_struct: {+ collected_definition, syntax_node: struct_node}
collected_field: {+ collected_declaration, syntax_node: field_node}
collected_parameter: {+ collected_declaration, syntax_node: parameter_node}
collected_variable: {+ collected_declaration, syntax_node: variable_declaration_node}
collected_function_reference: {+ collected_name, syntax_node: call_node}
collected_field_reference: {+ collected_name, syntax_node: field_access_node}
collected_variable_reference: {+ collected_name, syntax_node: variable_reference_node}
collected_constant_reference: {+ collected_name, syntax_node: constant_reference_node}
collected_type_reference: {+ collected_name, syntax_node: named_type_node}
collected_variable_write: {+ collected_name, syntax_node: variable_reference_node}
```

### Preparation and C++ output

```text
preparation_owner: {state: preparation_state, change_status: change_state, failed: bool, failure_message: string, source: collected_file, version: int, dependencies: SplObjectStorage<declaration_work>, dependents: SplObjectStorage<preparation_owner>, lookups: SplObjectStorage<preparation_lookup>}
prepared_file: {source: collected_file}
prepared_function: {return_type: canonical_type_use, parameters: Storage<prepared_parameter>}
prepared_record: {capabilities: vector<generic_contract>, fields: Key_Storage_List<prepared_field>}
prepared_storage: {declaration: weak<collected_name>, type: canonical_type_use}
prepared_binding: {+ prepared_storage, resolved_kind: binding_kind, conversion: ?conversion_decision}
prepared_parameter: {+ prepared_storage, mode: passing_mode, element_type: ?canonical_type_use, default_value: ?prepared_parameter_default}
prepared_expression: {type: canonical_type_use, addressable: bool}
prepared_call: {+ prepared_expression, fixed_count: int, variadic_parameter: ?prepared_parameter, declaration: weak<collected_function>, signature: prepared_function, arguments: Storage<prepared_call_argument>}
cpp_program: {fragments: SplObjectStorage<cpp_fragment keyed by preparation_owner>}
cpp_fragment: {change_status: change_state, version: int, text: string, prototype_text: string, headers: hash<bool>, records: Storage<collected_struct>}
cpp_module: {file_name: string, text: string}
```

`+ base` means inherited properties. Private fields are listed when they are part of
the model shape; access remains through their class API.

## Main file → class map

```text
compiler/: model.php Model; compile.php Compiler; lifecycle.php Compiler_Lifecycle;
            frontend.php Source_Frontend; work_queue.php Source_Work_Queue;
            publication.php Source_Publication; sources.php Source_Registry

01_prepare_inputs/: structures.php file, module, source_record, module_input;
                    module.php Module_Loader; file.php File_Loader
02_tokenize/: structures.php token, retained_token_range, token_list;
              tokens.php Tokenizer; buffer.php Token_Buffer; text.php Source_Text

03_parse/: parser.php Parser, Parser_Run; syntax.php Syntax_Nodes;
           scopes/structures.php scope; scopes/lookup.php Scope_Lookup;
           types/model.php type definitions, canonical types, Type_Registry;
           types/catalog.php registered_type_catalog and type-provider catalogs
03_parse/structures/: abstractions.php AST bases; structures.php parsed_file;
                      structures_specialization.php core typed nodes;
                      structures_loops.php loops/transfers;
                      structures_switch.php switch nodes;
                      structures_match.php match nodes

04_analyze/collect/: structures.php collected_* records; collect.php Symbol_Collector
04_analyze/prepare/: worker.php Preparation_Worker; file.php File_Preparation;
                     data/work_records.php preparation work; data/structures.php prepared facts;
                     semantics/*.php declaration/body/expression/type/control-flow workers

05_backend/cpp/: structures.php cpp_* records; generate.php CPP_Generator;
                 syntax.php CPP_Syntax; declarations.php CPP_Declarations;
                 types.php CPP_Types; type_bindings_catalog.php CPP_Type_Bindings
```

## Main processing calls

```text
Compiler::exec_cpp / update_cpp
├── sync
│   ├── discover/notify changed sources
│   ├── tokenize
│   ├── parse → Parser → Symbol_Collector
│   └── remove deleted collection/dependency memberships
├── prepare → Preparation_Worker
│   ├── declaration work
│   ├── function-body work
│   └── file-body work
└── cpp → CPP_Generator
    ├── select dirty fragments
    ├── render from attached prepared facts
    └── assemble main.cpp

Compiler::exec_llvm / update_llvm
└── sync → parked LLVM preparation/emission
```

## Invariants

```text
1. Model owns retained roots; Compiler_Lifecycle owns reset.
2. Modules own source records; source records own file/token/parse state.
3. parsed_file owns AST/scopes; collected_file owns occurrence membership.
4. AST owns named children only: no generic payloads, parent/sibling graph, or compiler traversal through children().
5. Parser collects directly; collected identities are canonical; no second symbol graph.
6. Definitions own declaration work; bodies own body work; a body is not a symbol.
7. Facts attach to typed syntax; workers and contexts are never retained.
8. Shared preparation owns semantics; C++ consumes facts and owns output representation only.
9. Preserve identity, source order, and collection positions; Model snapshots are derived, not indexes.
10. Required fields must be populated before read/publication; ?T means real absence.
11. Failed parse/preparation stays pending for retry; failed stages do not publish completed output.
12. LLVM does not define shared semantics.
```

## Test router

| Change area | Focused evidence | Evidence boundary |
| --- | --- | --- |
| Module/sync | `module_sync.php`, `file_scan.php`, `combined_sync.php` | Reconciliation and frontend joins |
| Tokens/parse/AST | `tokenizer.php`, `token_generations.php`, `token_cleanup.php`, `parse_collection.php`, `ast_invariants.php` | Retained parsing and AST/collection invariants |
| Scope/collection | `collected_roles.php`, `indexing.php`, `publication.php`, `dependency_cleanup.php` | Occurrence roles, indexes, cleanup |
| Preparation lifecycle | `preparation_work.php`, `incremental_preparation.php`, `preparation_recovery.php` | Scheduling, facts, invalidation, retry |
| Types/conversions/operators | `type_catalog.php`, `constructed_type.php`, `conversions.php`, `operators.php`, `logical.php`, `mutations.php` | Semantic decisions |
| Functions/calls | `functions.py`, `calls.php` | Signatures, bodies, call/default/variadic facts |
| Control flow | `control_flow.py`, `control_flow.php`, `s2s_proof.php` | Completion/transfers and S2S behavior |
| C++ output | `s2s.php`, `s2s_proof.php`, `incremental_cpp.php` | Lowering, fragments, assembly |
| Broad PHP | `run.py --php-only --results FRESH_DIRECTORY` | PHP-hosted regression evidence |
| Native compiler | `native_validate.py ... --no-stan --types-only` | Native compiler portability; on demand |

Generated-program success is not native compiler proof.

## Terminology

```text
syntax node: typed source structure; owns named syntax children and receives prepared facts.
collected identity: canonical declaration/reference/write occurrence linked to syntax and scope.
preparation work: retained scheduling/dependency identity for one definition or executable body.
prepared fact: backend-neutral semantic result attached to a typed syntax node.
preparation context: invocation-local semantic state; never retained.
C++ fragment: retained rendered output keyed by existing preparation work.
C++ module: final generated file name plus text; no source/preparation links.
scope: lexical/publication indexes of existing identities/definitions; not a second semantic model.
```

## References

- [Documentation index](README.md)
- [Retained model](architecture/MODEL.md)
- [Ownership](architecture/ownership.md)
- [Call map](../compiler/calls.md)
- [Preparation](../04_analyze/prepare/README.md)
- [Incremental lifecycle](lifecycle/incremental.md)
- [Catalog](catalog/README.md)
