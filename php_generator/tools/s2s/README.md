# Fixture-driven PHP -> Simple C++ generator starter

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
php tools/s2s/bin/transpile_fixture.php samples/01_literals_and_assignments.php build/out
```

Run all sample fixtures:

```bash
php tools/s2s/bin/run_samples.php
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
