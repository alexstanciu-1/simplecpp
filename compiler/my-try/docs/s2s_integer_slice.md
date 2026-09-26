# First v0.2 integer S2S slice
Doc Status: supporting

This slice implements `LIT-INT-001`: `$a = 10;`, using the existing canonical
PHS-shaped AST. Language meaning follows the Simple C++ contracts; the independent
LLVM experiment does not provide type policy for this path.

## Model and ownership

- `compiler/types/structures.php` defines canonical type records and enum tags.
  `Language_Types` registers the current built-in `int` (signed 64-bit) in code.
  The width field uses the portable `uint32` annotation; enum layout is not claimed
  to be byte-sized. Runtime/library JSON loading is a later feature.
- Model owns `language_scope`; global scope has it as a lexical parent. Scope
  storage is private. Source definition registration, local candidate queries,
  publication and replacement go through methods. `Scope_Lookup::types` walks
  nearest live pools through parents, following the existing file publication link.
- Each scope owns a Storage of type definitions. Source type records point at their
  real collected declaration; built-ins have no fabricated AST. Global publication
  retains the same source definition objects. There is no second global type registry.
- `File_Preparation` uses a fresh transient scope to establish local declarations
  in source order. The first untyped assignment becomes a prepared declaration;
  later reads and assignments retain that occurrence identity. Explicit `int` locals
  resolve through source scopes and converge on the same canonical definition.
- Prepared expressions/bindings are attached to their AST specialization records.
  Source syntax, scopes and the declaration inventory are unchanged; each node
  clears its derived facts during tree cleanup. No reverse syntax references or
  token-keyed fact maps remain. Type objects are shared.
- C++ type spelling, header and enum-tagged literal strategy belong to `05_backend/cpp`.
  Final artifact records contain only names and text.

Reserved-name restrictions remain deferred to validation/STAN. A nearest source
`int` definition shadows the language definition in lookup; its currently unsupported
S2S representation is not silently replaced by the built-in. Literal defaults use
language `int` directly. Shadowing tests exercise lookup without claiming source
record generation or permanent permission to redeclare reserved names.

## Current generation surface

One live source file containing straight-line integer bindings, variable reads,
reassignments and optional top-level returns. Decimal literals from zero through
9223372036854775807 preserve their exact text; the host never parses them through
PHP int/float. Leading-zero numeric forms, negative/unary forms, other literals,
functions, compound types and multi-file program generation remain unsupported.
These are generation coverage boundaries, not a new validation milestone.

The literal card emits, inside a minimal native entry:

```cpp
auto local_0 = static_cast<scpp::int_t<>>(10LL);
```

`local_0` identifies the declaration token position, avoiding C++ keyword collisions.
The native literal suffix provides a signed carrier for the whole supported range.
The `auto` plus typed literal strategy follows the existing lowering approach;
spelling-level compile-time optimization is deferred. Only `scpp/int_t.hpp` is
included. No source-level include is required.

## Entry points

```sh
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY > /tmp/program.cpp
clang++ -std=c++20 -I runtime/include /tmp/program.cpp -o /tmp/program
```

API: `Compiler::init(paths)` followed by `exec_cpp()`, or `update_cpp(changed_paths)`
for subsequent notifications. Standalone stages are `prepare()` on published syntax,
then `cpp()` on completed preparation. Preparation publishes Model::$prepared_files;
emission publishes Model::$cpp_files. Preparation failure leaves both empty. Emission
failure clears only C++ output, permitting retry with the same shared facts.
`reset_cpp()` preserves preparation; `reset_preparation()` clears facts and dependent
C++ output. The explicit `exec_llvm()` / `update_llvm()` path remains for regression callers.

The slice returns final bytes in memory/stdout. Artifact publication, file splitting,
link planning and native build caching are not implemented by this entry.

## Proof

- `tests/s2s.php`: source syntax purity after fact cleanup, node cleanup on failure/reset, canonical integer identity, explicit/inferred locals,
  copies/reassignment, values beyond int32, maximum signed int64, ordinary parent
  lookup/shadowing and tombstones, and unsupported-generation output clearing.
- `tests/s2s_proof.php`: portable PHP/native proof of canonical facts, first assignment,
  exact emitted bytes, repeated generation, update behavior and failure recovery.
- `tests/run.py`: runs PHP regressions plus compiles/executes seven generated C++ cases plus two independent int64 value/type probes.
- `tools/native_validate.py` (only on explicit user request): normal STAN-enabled conversion/build; compares PHP/native
  C++ bytes, compiles/runs the emitted program, and retains existing LLVM regressions.

Saved [PHP/native evidence](../../../specs/planning/results/s2s_integer_01/README.md) records the toolchain fingerprints, outcomes and portability corrections.
