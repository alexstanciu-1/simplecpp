# Canonical type definitions

Doc Status: supporting

The int64 migration replaces the former two-case `type_definition` enum with an
abstract definition and canonical `type_int64` / `type_void` objects. These represent
types, not runtime values or per-expression preparation records. `instance()` returns
one shared identity; ordinary construction and cloning are unavailable to consumers.
The readonly `name` property preserves existing inspection names.

The current contract exposes only implemented behavior:

- `accepts_name(string)`: admit a source name for this canonical definition.
- `prepare_literal(ast_expression)`: validate supported syntax and attach facts to
  that occurrence. Unsupported literal kinds produce a preparation diagnostic.
- `cpp_type()`: return the settled type's native spelling.
- `lower_cpp_literal(ast_expression)`: consume prepared facts without semantic
  resolution or AST mutation. Unprepared/unsupported input is an internal error.

`type_int64` is generated in full: canonical construction, source-name admission,
MINIMUM/MAXIMUM constants, signed decimal literal preparation and C++ spelling.
There is no handwritten int64 definition or type-specific preparation helper.
The handwritten `type_definition` contract and existing `type_void` remain shared
compiler machinery; void is not newly registered as a runtime value type.

## Metadata and registration

`runtime/metadata/runtime.json` supplies the first bounded compiler contribution:

- `compiler.modules.default-runtime.always_available`: the module is always active.
- That module's `types`: currently only int64 for this slice, not a complete catalog
  of every runtime API that exists. Other runtime types are not enabled implicitly.
- `types.int64`: signedness and width, already validated against native target facts.
- `source_types`: canonical source aliases, including int -> int64.
- `compiler.integer_literals`: decimal radix, reject-on-overflow policy and signed
  C++ literal carrier suffix. Unsupported policies fail during generation.

The generator computes exact decimal bounds using shared generation algorithms,
without floating-point arithmetic or extra host packages. It derives safe C++ minimum
spelling from those bounds and the carrier suffix, checking that native target facts
provide a sufficiently wide signed carrier. No boundary digits live in the template.
The syntax adapter still decides where signed literals are admitted; this change
preserves the existing source grammar and diagnostics.

Generated `type_registry::resolve(name)` directly matches source names to the canonical
instance. `integer()` selects the metadata-defined default integer identity for literals
and the existing entry contract. Compilation performs no JSON parsing, module scanning
or generic capability interpretation for these operations. Module membership determines
registration; no independent per-type activation flags are introduced.

Preparation workers still own traversal, declaration admission and sequencing.
Types own no AST nodes, context, mutable occurrence caches or dependency tables.
AST-owned facts retain their type reference and occurrence-specific value facts.
The emitter dispatches through the resolved type instead of a scalar enum switch.

No operators, overloads, casts, additional types, optional-module loading, container
instantiation or persistent type-ID scheme are introduced. Generic generation algorithms
and templates remain handwritten tooling; they generate the type-specific implementation.

Generation publishes `compiler/generated/types/int64.php` and `types/registry.php`.
Inventory version 3 and extension-scoped cleanup retire obsolete generated artifacts.
The `signed_integer.php.tpl` and `type_registry.php.tpl` templates own source shape;
metadata and validated native facts supply identities, limits, aliases and representations.
`boot.php` loads the common definition, generated class/registry and existing void definition.

## Verification — 2026-11-04

27 compiler CTests passed (excluding service/native suites), including a focused
canonical identity, alias, cloning, readiness and lowering-purity proof. Generator
tests exercise freshly generated code, registration identity, exact generated limits,
module deactivation and invalid-policy rejection, and extension-scoped cleanup.
Runtime freshness and generator input/cleanup checks also passed. All three native
global fixtures printed the same values and exited 8; all 12 emitted C++ files were
byte-identical to the pre-refactor global fixtures.

Native proof: O2/g0, Ninja -j8, mold, libc++, no PCH/LTO, fresh object files and an
existing runtime archive. Single-run correctness timings, not performance comparisons:

| Flavor | Native elapsed ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 396.37 | 21.05 | 21.79 | 96 |
| php | 109.75 | 15.73 | 22.41 | 52 |
| js | 114.02 | 25.77 | 29.52 | 54 |

Frontend/LLVM totals are cumulative across units from separate traced rebuilds;
they are not additive components of parallel elapsed time. LLVM includes optimization
and code generation. Raw results/logs: `v0.2/build/generated-int64-proof-2026-11-04/`.
The slower first run does not imply a flavor or generated-code difference.
