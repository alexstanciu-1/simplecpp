# Fixture-driven PHP -> Prism++ generator starter

This is a first code pass built against the sample fixtures in `samples/`.

## Why fixture-driven first
- deterministic development
- no dependency on the live `php-ast` extension yet
- easier debugging against known JSON AST/token snapshots

## Current scope
This pass is intentionally small.
It proves the pipeline and emits C++ for a narrow subset covering the current sample data:
- top-level assignments
- free functions
- namespaces
- simple classes with methods
- `new Class()` -> `create<Class>()`
- static calls
- strict local PHPDoc typed locals
- reference signatures in declarations
- simple literals / `+` / returns

## Current limitations
This is not a semantic compiler.
Unsupported or not-yet-cleanly-lowered cases are surfaced as notes/errors rather than guessed.

## Commands
Generate one sample:

```bash
php bin/transpile_fixture.php samples/01_literals_and_assignments.php build/out
```

Run all sample fixtures:

```bash
php bin/run_samples.php
```

## No Composer needed
- The current starter runs without Composer.
- The CLI scripts use `bin/bootstrap.php` with direct `require_once` calls.
- `composer.json` is present only as an optional future convenience.

## Anchored namespace resolution
The current generator now builds a declaration registry per file and resolves class/function names before emission.

Resolution order in the current implementation:
- rooted PHP names -> rooted `::scpp::...`
- exact fully-qualified declarations already known in the file -> rooted `::scpp::...`
- current-namespace exact matches -> rooted `::scpp::...`
- anchored ancestor search for unique descendant matches -> rooted `::scpp::...`
- otherwise -> preserve the previous relative/unqualified fallback emission

This is intentionally an implementation step, not a claim of full PHP namespace parity yet.

## Enum support

Current enum lowering is intentionally narrow:
- unit enums lower to native `enum class` declarations
- int-backed enums with literal integer case values lower to native `enum class` declarations with explicit enumerators
- underlying storage is kept at 1 byte when possible and widened only when the case set or integral values require it

Out of scope in this stage:
- string-backed enums
- enum methods and interfaces
- helper APIs such as `cases()`, `from()`, `tryFrom()` on enums
- pseudo-properties such as `->name` and `->value`


## Enum Support (v1 - constrained subset)

### Status
PARTIAL / INTENTIONAL SUBSET

### Supported
- Enum declaration (unit enums)
- Backed enums (int)
- Case access: Enum::Case
- Assignment / storage
- Identity comparison (===)

### Newly supported
- `->name` property on enum cases

### Lowering rule
PHP:
    Enum::Case->name

C++:
    ::scpp::php::enum_name(Enum::Case)

### Not supported (explicit)
- Enum::cases()
- `->value`
- Enum::from / tryFrom
- Reflection-style enum APIs

### Rationale
Enums are currently lowered as value types (`enum class`). PHP enums are object-like.
Full object semantics are intentionally deferred.

### Design decision
Use helper functions instead of changing enum representation.

Required runtime:
    string_t enum_name(T value);

### Guarantee
No implicit pointer/object semantics for enums in v1.

## Current variable-name normalization

- PHP variable names are preserved unless the raw name is a reserved C++ keyword
- reserved keyword names lower deterministically to `<name>__`
- if that collides in the same function-like scope, the generator continues with `<name>__1`, `<name>__2`, and so on until a free name is found
- the chosen remapped name must be used consistently across declarations, definitions, helper templates, and local uses

## Current foreach by-reference lowering

- `foreach ($items as &$item)` lowers via a hidden-key slot rewrite
- `foreach ($items as $key => &$item)` lowers via an explicit-key slot rewrite
- loop-body uses of the foreach value variable are rewritten to the source slot expression instead of creating a standalone alias local
- this behavior is provisional and subject to future improvement
