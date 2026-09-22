# PHP portability tools
Doc Status: supporting

A PHP-written converter with a small structural AST, central operation policies
and explicit runtime support. A declaration-location index supports restricted
direct trait expansion; no type analysis or compiler frontend dependency.
See the [profile and checkpoints](../../specs/portability/README.md) and
[remaining debt](../../specs/portability/debt.md).
For source authoring, use the [portable-PHP guide](../../specs/portability/authoring_guide.md)
and [project skill](../../.agents/skills/simple-cpp-portable-php/SKILL.md).

For the current ready compiler set, run
`python3 tools/php_portability/validate.py --results FRESH_RESULTS_DIRECTORY`.
Add `--native compiler --target-checkout TARGET_CHECKOUT` for the cumulative native
proof. See the [workflow and other proof selections](../../specs/portability/validation_workflow.md).

```bash
php tools/php_portability/check.php SOURCE_DIRECTORY
php tools/php_portability/check.php SOURCE_DIRECTORY --cache EXISTING_OUTPUT_DIRECTORY
php tools/php_portability/convert.php SOURCE_DIRECTORY OUTPUT_DIRECTORY
php tools/php_portability/convert.php SOURCE_DIRECTORY OUTPUT_DIRECTORY --stats
php tools/php_portability/install_native_runtime.php OUTPUT_DIRECTORY
php -d auto_prepend_file="$PWD/tools/php_portability/runtime/bootstrap.php" tests/portability/fixtures/take.php
python3 tests/portability/run.py
python3 tests/portability/prologues.py
python3 tests/portability/native_runtime.py
python3 tests/portability/check.py
```

`function_map.php` is the authoritative function policy: internal PHP implementation,
native target spelling and arity. Every portable file
uses the same global helper catalog without imports. Manual imports, per-file rebinding and internal-namespace/original-builtin
bypasses are rejected. PHP support is loaded by the
host harness or `auto_prepend_file`; compiler bootstrap loads it explicitly.

`check.php` is read-only: it scans every `.php` using the conversion discovery and
import policy, builds the same declaration/trait index, runs PHP lint without
executing source, and exercises the converter's parser/emitter in memory. It prints
JSON with `checked` and cache counters on success, or the first diagnostic on stderr
and exits nonzero. It does not prove target type compatibility or native behavior.
With no cache argument, discovery and tokens are in memory and nothing is written.
`--cache` reads an existing separate output directory's index/token cache; additions
and removals are discovered but never persisted by checking. Every file is still
linted and structurally converted; this is not cached validation-result reuse.
Checking does not validate or repair generated output/artifacts. A successful
conversion remains required before consuming them.

Text helpers (`strlen`, `substr`, `strpos`, `strrpos`, prefix/suffix checks) now
use `scpp\compat` UTF-8 code-point semantics and reject malformed UTF-8. PHP requires
mbstring; native text operations require framework assembly. Use explicit
`string_byte_*` helpers for binary data and source offsets. See the
[string contract](../../specs/portability/utf8_text_contract.md).

Implemented syntax grows by proved compiler slices: scoped prologues, scalar and
explicit vector fields, promoted constructors, scalar/named method signatures with void returns, integer/unit
enums, interfaces, literal construction/member calls, bounded expressions/loops,
and the [handled-exception subset](../../specs/portability/compiler_exception_slice.md).
The [discovery-record slice](../../specs/portability/compiler_discovery_records_slice.md)
adds nullable fields, nullable promoted lists and integer-literal constants.
The [direct-trait/index checkpoint](../../specs/portability/traits_and_incremental_index.md)
adds same-namespace method-only traits, explicit instance methods, and
persistent PHP token arrays. Traits cannot use traits or use adaptation blocks.
These are bounded contracts, not general PHP conversion. Inheritance in authored
input, arbitrary imports, finally and dynamic names remain outside the subset.

Type annotations in executable PHP emit native PHS type syntax; see the
[map/iteration and emission checkpoint](../../specs/portability/map_iteration.md).

Output trees must be separate from inputs. Each `.php` maps to one `.phs` at the
same relative path; traits expand into their consumers and leave no native trait
declaration in their own output. Source/rule/policy/direct-trait fingerprints control reuse; unchanged bytes
retain timestamps. Conversion stages errors before publication and removes only
owned outputs for removed sources. It publishes files individually, with no
whole-tree atomicity or concurrent-writer claim. Consume output only after success.

The persistent index caches directory membership and declaration locations. Warm
runs check metadata, listing changed directories and reading changed/recent files.
They do not retokenize unchanged files. Token payloads use immutable PHP `return`
files with `.php-cache` suffixes to keep them out of native source discovery.
OPcache is optional; `--stats` exposes actual discovery/read/token counters.

Native framework assembly is a separate step and owner. It installs generated
support under reserved `scpp_framework/` with `.scpp-native-runtime.json`, leaving
`.scpp-portability.json` untouched. It refuses collisions, symlinks and edited or
unowned support files. Re-run after framework changes; unchanged files are reused.
It is required for mapped exceptions, `same_exception`, and text helpers. The native project uses
normal same-project composition; no generated C++ edits or target patches are used.

Owners:

- `src/converter.php`: structural parsing, local rules and emission.
- `src/container_type.php`: explicit recursive vector/hash annotation grammar.
- `src/declaration_index.php`: declaration locations, direct trait dependencies,
  collision checks and token expansion retaining original source locations.
- `src/project_cache.php`: incremental membership, metadata and PHP token artifacts.
- `src/import_policy.php`, `sync_imports.php`: import-free prologues and old-block cleanup.
- `function_map.php`: direct function bindings.
- `src/exception_policy.php`: root exception spellings and native hierarchy.
- `runtime/bootstrap.php`: PHP approximations of target-specific operations.
- `runtime/strings.php`, `runtime/strings.phs`: PHP/native text validation and operations.
- `convert.php`: file correspondence, fingerprints and conversion publication.
- `check.php`: read-only PHP lint and shared conversion validation.
- `validate.py`: host orchestration of current ready-set checks and selected native proofs.
- `install_native_runtime.php`: separately owned native support assembly.

The cumulative compiler proof in `tests/portability/compiler_context/run.py` uses
the selected clean v0.1.76 checkout. `tests/portability/run.py --native` remains a
separate foundation test using the current workspace toolchain.

Current authoring uses [global helpers](../../specs/portability/global_functions.md),
with q_ for PHP builtin names and no function imports. sync_imports.php only cleans
old generated import blocks and validates prologues. Regenerate static PHP facade
with generate_global_functions.php; --check verifies its signatures.

Aggregate lifecycle composition is now proved: 36 shared PHP/native outcomes plus
eight host checks; active coverage is 79 files, including native layout and resource obligations. See `specs/portability/aggregate_lifecycles.md`.
