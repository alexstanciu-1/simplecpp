# Small compiler: joint review
Doc Status: planning

This is the review-first intake of the small compiler experiment. The current
compiler rewrite in `../src/` is parked; this intake does not change its files,
proof registration, or tooling.

[model.php](compile/model.php) holds the retained graph exposed through static
fields such as `Model::$modules`. `Compiler::init()` resets it for each new run.
[MODEL.md](MODEL.md) documents its current boundaries, references and mutation
owners. The coordinator retains processing responsibility.

## First slice: source loading

Imported byte-for-byte on 2026-09-24 from:
`/home/alexv/__AI/scpp_compiler_3/prototype/my-try/01_prepare_inputs/`.

- `01_prepare_inputs/file.php`: file metadata and source-byte loading.
- `01_prepare_inputs/module.php`: immediate `.phs` file discovery and loading.

SHA-256 checksums identify the imported content for provenance only:

- `file.php`: `deb851745f5897279e2f58f7aa8c994e3390115bdbe8a244e0c16cd2850e34a4`
- `module.php`: `5a7aef93f2b2ee2452653a1080c4a1db42caada8be50ab945c6a0aa04bc2a3ea`

The checksums above describe the original imports, before local adaptation.
Data now lives in `01_prepare_inputs/structures.php`; `File_Loader::init()` and
`Module_Loader::init()` populate caller-owned records. All use `scpp\compiler`.
The full PHP pipeline is imported: loading, tokenization, parsing/collection,
name/template preparation, LLVM generation and Clang execution. Data records live
in each process's `structures.php`; AST payloads are grouped separately in
`03_parse/structures_specialization.php`. Processing owners use capitalized names.
[Import provenance](import-provenance.json) records the original remaining files.
These components are not yet registered or proved convertible-PHP components.

## Run the compiler

From this folder, run `php main.php`, or open `http://localhost/my-try/`.
The entry loads `samples/01_base`, displays sources/tokens/AST/collection/LLVM,
and builds and runs the sample. Expected executable exit code: **9**.
The browser receives a plain-text report. `boot.php` owns PHP file loading.
The PHP process reports compilation diagnostics separately from the sample's exit.

`main.php`, `boot.php` and `compile/compile.php` are adapted from the experiment,
retaining our local source loaders and public module inspection.
Both sample files
were copied unchanged on 2026-09-24 from the experiment's `samples/01_base/`:

- `main.phs`: SHA-256 `b079eff7b3f830163a220bd66bd0eee0d0ac56a26824be2f2219f47ded55b3e5`
- `helper.phs`: SHA-256 `dcb7c778041dfa31045606a49d447a896f9faa2023d0d70167047c8b26a11d60`

These samples are compiler input, not PHP implementation code; their original
language syntax is preserved.

### Apache alias

The prepared [alias configuration](apache/scpp-my-try.conf) changes the existing
`/my-try` alias to this folder, preserving local-only access and PHP 8.5 FPM.
Install it as the user with sudo access:

```bash
sudo cp /etc/apache2/conf-available/scpp-my-try.conf /etc/apache2/conf-available/scpp-my-try.conf.before-new-location
sudo cp /home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/apache/scpp-my-try.conf /etc/apache2/conf-available/scpp-my-try.conf
sudo apache2ctl configtest && sudo systemctl reload apache2
```

The existing `conf-enabled/scpp-my-try.conf` already enables this configuration.
The alias is installed. CLI and Apache HTTP checks both built and executed the
sample successfully on 2026-09-24; no further Apache change is needed.

## Working agreement

Import the full small compiler, apply the intake rules and preserve PHP behavior.
Then review and improve one file or coherent ownership area at a time together.
Use the existing portability converter and framework. Keep PHP as authored source.
Do not resume the parked rewrite's migration sequence automatically.

Apply compiler 3's process ownership, naming, meaningful comments, method ordering
and formatting conventions when adapting code. Source guidance:

- `/home/alexv/__AI/scpp_compiler_3/AGENTS.md`
- `/home/alexv/__AI/scpp_compiler_3/docs/code_formatting.md`
- `/home/alexv/__AI/scpp_compiler_3/docs/code_organization.md`

Keep identities exact and ownership explicit. Introduce larger lifecycle,
worker/join or incremental machinery only when agreed for a concrete need.
The default intake procedure is recorded in [AGENTS.md](AGENTS.md). Structural
preparation is authorized on import; further representation changes and
optimization remain joint-review decisions.

## First review questions

- Data records and loading operations now have separate owners.
- Token lists own captured source text and tokens; file.tokens is an optional
  convenience backlink. Preserve that boundary during conversion.
- Is `size` filesystem metadata or the length of the retained content? The
  separate stat/read calls do not guarantee the file stayed unchanged.
- Preserve immediate-file discovery and filename order; choose explicit typed
  collection intent during adaptation.
- Replace the global debug dependency with an agreed host reporting boundary.
- Decide partial-failure behavior: module loading currently clears and appends
  its public file list progressively, so a failure can leave partial results.

Non-goals for this import: new language behavior, a native compiler port,
incremental machinery, and speculative storage/ID infrastructure.

## Verification and next review

Run `python3 tests/run.py` for PHP lint, source/rejection tests, the 28 call/native
cases, execution of all 19 baseline LLVM fixtures, and the sample. Use
`--results /tmp/NEW_DIRECTORY` to retain logs, emitted IR and a JSON summary.
The destination must not exist. The configured Clang is in
`06_native/toolchain.json`; PHP and Clang must be available.

These tests run the compiler **in PHP** and execute its generated programs.
They do not prove that the compiler itself converts to native code.
See [the review inventory](REVIEW.md) for known conversion and representation work.

Full-import verification on 2026-09-24 passed: 28 PHP files linted, source and
rejection assertions passed, 19 LLVM fixtures and 28 call cases compiled/executed,
and the sample returned 9 through both CLI and Apache. The repeatable run used
`python3 tests/run.py --results /tmp/scpp-full-import/final-proof`; its logs and
summary remain in that temporary directory. Apache output was separately checked
at `http://localhost/my-try/`.


## Current Storage/AST shape

Storage<T> is a numeric shared object list (capacity-only constructor), not a value
copy list. Keyed_Storage<T> provides exact string-keyed object collections; both
inherit Storage_Abstract. LLVM preparation uses these for ordered records and named
types/fields/targets, while sparse indexes and scalar arrays remain typed arrays.
AST nodes directly own their payloads and nested Storage child lists;
there are no Storage_View helpers or parallel node/payload registries. Parser methods
return nodes directly. Scope stores and collected-entry indexes remain where the
compiler uses them. See MODEL.md and helpers/STORAGE.md for the current contract;
earlier layout/view proposals are marked historical. Native issue/PR reconciliation
is described in helpers/STORAGE_NATIVE_TASK.md.


Current collection-migration verification: 36 PHP files linted, 19 LLVM fixtures
and 28 call cases executed, sample exit 9. Preparation reuse and shared-target
identity checks also passed. The earlier full-import counts above are historical.

Current reading order: [model](MODEL.md), [collection API](helpers/STORAGE.md),
[ownership](docs/ownership.md), [conversion status](docs/conversion_review.md),
[native delivery brief](helpers/STORAGE_NATIVE_TASK.md). Documents marked historical
preserve earlier decisions and are not implementation instructions. The saved GitHub
issue text is a historical snapshot, not a synchronized copy of the local brief.
