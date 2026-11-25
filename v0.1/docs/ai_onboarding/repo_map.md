# Repo Map
Doc Status: supporting

Purpose: tell an AI assistant where to look first for each kind of task.

## 1. Primary Semantic Authority

Read these first when behavior is in doubt:

- `specs/spec_map.md`
- `specs/dynamic_types.md`
- `specs/array_semantics.md`
- `specs/count_empty_isset_contract.md`
- `specs/php/catalog.md`
- relevant contracts under `specs/builtins/`

## 2. Architecture / Anti-Drift Authority

Use for ownership, layering, and dependency direction:

- `specs/architecture/runtime_layering.md`
- `specs/architecture/runtime_design/README.md`
- `specs/architecture/runtime_design/semantic_consistency.md`
- `specs/architecture/runtime_design/structure.md`

These do not override user-visible language semantics.

## 3. Generator Authority

Use when the question is "how should PHP lower to C++?" or "is this syntax supported?"

- `generators/php/specs/rules.md`
- `generators/php/specs/catalog.md`
- `generators/php/specs/rules_catalog.md`
- `generators/php/specs/unsupported.md`
- `generators/php/specs/module_inclusion_model.md`

Key implementation anchors:

- `generators/php/src/Transpiler.php`
- `generators/php/src/Builder/IrBuilder.php`
- `generators/php/src/Generator/Generator.php`
- `generators/php/src/Lowering/TypeMapper.php`

## 4. Runtime Authority

Use when the question is "what runtime surface or helper owns this behavior?"

- `runtime/specs/spec.md`
- `runtime/specs/error_handling.md`
- `runtime/specs/config.json`
- `runtime/specs/operator_generation_flow.md`

Useful code anchors:

- `runtime/include/scpp/runtime.hpp`
- `runtime/include/scpp/lang/php.hpp`
- `runtime/include/scpp/support/mixed_t.hpp`
- `runtime/include/operators/`
- `runtime/include/modules/`
- `runtime/include/lang/php/`

## 5. CLI / Project Build Flow

Use when the task is about project mode, configuration, build outputs, or command shape:

- `specs/project_build_v1.md`
- `docs/getting_started.md`
- `docs/installation.md`
- `bin/scpp.php`
- `bin/project_services.php`

Main commands:

- `scpp init`
- `scpp build`
- `scpp run`
- `scpp usability-harness`

## 6. Tests and Harnesses

Use for regression proof and behavior discovery:

- `tools/usability_harness/README.md`
- `tests/tools/README.md`
- `tests/specs/`
- `tests/generated/`
- `tests/php-matrix/`
- `tests/runtime-matrix/`

The usability harness is especially useful for:

- real project workflow validation
- deterministic output checks
- keeping first-time-user scenarios healthy

## 7. Examples and Reconnaissance Material

Use when you need concrete patterns rather than abstract rules:

- `specs/php/canonical_examples.md`
- `generators/php/samples/README.md`
- `generators/php/samples/stage_01/`
- `generators/php/samples/stage_02/`
- `generators/php/samples/stage_03/`
- `generators/php/samples/know_how/README.md`
- `docs/examples.md`

The `know_how` fixtures are especially useful when the real question is:

- what the exporter emits
- what AST shape a syntax form really has
- what lowering pattern is already established

## 8. Planning / Historical Material

Read carefully and do not treat as current authority by default:

- `specs/AI_WORKFLOW.md`
- `specs/todo.md`
- `specs/todo_vs_php.md`
- `specs/pending_issues.md`
- archived or `_archive` material

These can explain direction and context, but they do not outrank active normative specs.
