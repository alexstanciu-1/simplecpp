# PHP compiler

Doc Status: supporting

The active rebuild is authored in PHP, using the stage layout and reusable compiler
concepts from `compiler/my-try/`. It requires 64-bit PHP 8.5+; this slice
needs no PHP 8.6 features or packages. A future Simple C++ implementation will be
ported manually or with assistance, without imposing converter constraints here.

Before editing, read the local [working rules](AGENTS.md) and
[coding style](docs/code_style.md). They consolidate the applicable donor rules
with the current PHP-first decisions; donor-specific infrastructure is not inherited.

The compiler runs sequentially using ordinary `php`; NTS and ZTS builds are both
supported, and no Parallel extension is required. See [execution guidance](docs/execution.md)
for tool selection, the current flow and retained MT experiment evidence.

See [common scopes](docs/scopes.md) for declaration ownership and shared publication.
See [canonical type definitions](docs/types.md) for core scalar/void identity and generated preparation/conversion/lowering.
See [stable symbols and preparation caches](docs/stable_symbols.md) for the current
in-process incremental preparation API and its lowering boundary.

## Persistent app-local frontend

```sh
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple
# Edit a source, then invoke the same command again.
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple status
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple stop
```

The first request starts a detached service for this app; later CLI processes reuse
its in-memory frontend session. It stops after one idle hour. This path performs
only tokenize, parse/local collect and symbol publication. See the
[service lifecycle and checks](docs/frontend_service.md).

## Local stage inspection panel

Run `python3 v0.2/tools/compiler_panel/server.py` from the repository root and open
http://localhost:8765. The loopback-only panel reports compiler stages, generated
C++, native build output and execution for single or multiple source files. See
[panel setup and limits](../tools/compiler_panel/README.md).

See [metadata-generated native lowering](docs/generated_lowering.md) for the shared
API-generator direction and native int64 slice. Build `scpp_runtime` once before
running the compiler in a fresh checkout to publish its PHP backend traits.

## Current working slice

```sh
php v0.2/compiler/main.php tokens php path/to/source
php v0.2/compiler/main.php tokens cpp path/to/source
php v0.2/compiler/main.php tokens js path/to/source
php v0.2/compiler/main.php parse php path/to/source
python3 -B v0.2/compiler/tools/check_style.py
php v0.2/compiler/tests/tokenizer.php
php v0.2/compiler/tests/parser.php
php v0.2/compiler/tests/frontend.php
php v0.2/compiler/tests/functions.php
python3 -B v0.2/compiler/tests/cli.py
php v0.2/compiler/tests/entry.php
php v0.2/compiler/tests/scalars.php
php v0.2/compiler/tests/floating.php
python3 -B v0.2/compiler/tests/floating_native.py
php v0.2/compiler/tests/strings.php
python3 -B v0.2/compiler/tests/strings_native.py
python3 -B v0.2/compiler/tests/scalars_native.py
python3 -B v0.2/compiler/tests/entry_native.py
python3 -B v0.2/compiler/tests/functions_native.py
```

The CLI requires an explicit flavor and publishes inspection JSON only after the requested stage succeeds.
Token rows contain kind, byte offset, length and lossless hexadecimal spelling.
Errors go to stderr with a nonzero exit status. Input uses Simple C++ flavor syntax;
PHP opening tags, C++ preprocessing and JS template literals are not supported.

## Ownership and layout

- `boot.php`: explicit dependency loading, with no scanning or CLI side effects.
- `main.php`, `host/cli.php`: executable entry and presentation.
- `compiler/flavor.php`: per-source syntax identity and bounded keyword vocabulary.
- `compiler/frontend.php`: stage coordination, reusable across mixed-flavor sources.
- `compiler/diagnostics.php`: errors tied to the retained source and byte position.
- `01_prepare_inputs/`: file loading and immutable source snapshots, LF line indexing.
- `02_tokenize/`: scanner workers and immutable token publications. Trivia stays in
  source bytes; token spelling is sliced on demand. Adjacent angle queries preserve
  generic delimiters and recognize shifts without mutating tokens.
- `03_parse/structures/`: shared AST nodes with token positions and parent ownership.
  `abstract.php` holds AST bases, the declaration trait and syntax enums;
  `source.php` holds the file root and its token/source access;
  `common.php` holds the remaining common concrete expression/statement nodes.
- `03_parse/flavors/`: small declaration/assignment grammars for cpp/php/js.
- `03_parse/cursor.php`, `parser.php`: shared token access, values and syntax publication.
- `03_parse/collect/`: parser-invoked, source-local declaration identities and unresolved read occurrences.
- `04_analyze/prepare/`: source-order integer binding/type facts and checked entry-return decisions.
- `05_backend/cpp/`: rendering completed facts and publishing complete C++ files.
- `06_native/target.php`: explicit target integer limits from native layout facts.
- `tests/`: scanner, parser, semantic, publication, native execution and scaling proofs.

The first [end-to-end entry slice](docs/entry_slice.md) compiles explicitly typed
integer declarations and a top-level return across all three flavors. The example
`$ret int = 8; return $ret;` exits with status 8. The guide shows stage inspection,
C++ publication/build commands and the agreed verification checklist.

The [multifile slice](docs/multifile_slice.md) adds an explicit source inventory,
one selected entry and an exact native link manifest. Secondary files currently
may contain comments/whitespace and no-argument void function definitions. Source execution is sequential; basic
incremental reuse and declaration dependency scheduling remain future work.

The [void-function slice](docs/functions_slice.md) adds global discovery, settled
signatures, body-owned preparation and cross-file calls.

Future slices will add richer scopes, richer types/conversions/operators and other working
functionality as needed. There is no empty scaffolding for future stages.

The parser supports no-argument void functions and calls, initialized local declarations,
simple-name assignments and value/bare returns,
named type annotations, and integer/name values. `parse` outputs inspectable JSON
with normalized names, original spans, declaration annotations and assignment intent.
Integer spellings remain strings; parsing does not convert them to PHP integers.
Arithmetic, grouping, blocks, generic types and other expressions are later slices.

There is one PHP syntax flavor. Composed profiles selected in PRJ configurations
will control compiler policy; common analysis will enforce them across all flavors.
The bounded semantic/backend slice does not introduce profile loading/enforcement
or incremental reuse. Inferred declarations and assignments still parse but are
not yet prepared. Successful parsing alone is not semantic validity. See [the flavor contract](../specs/compiler_frontends.md).

## Migration boundary

The donor supplies stage ownership, source retention, trivia/span handling and
adjacent-angle queries. The retired Rust implementation supplied the bounded flavor
behavior: identifiers, keywords, comments, numbers and punctuation. The PHP code uses
ordinary arrays, readonly records and an integer-backed token-kind enum with explicit IDs and separate readable CLI labels; numeric Rust tags,
packed storage and allocation machinery are not PHP implementation requirements.
The byte source/token size bounds remain checked without claiming a packed layout.

Rust sources, tests and Cargo configuration have been removed.
The remaining parser ideas and proof cases are in
[parser migration notes](docs/parser_migration_notes.md). The donor remains unchanged.

Token kinds use explicit integer IDs in 1..127 (zero reserved). All keywords share
`token_kind::keyword`; consumers read their spelling through `token_list::text_at()`.
Recognition remains flavor-specific. CLI keyword rows use kind `keyword`, with the
exact spelling in `text_hex`. Adding a keyword does not consume another kind ID.

## PHP authoring style

The [coding style](docs/code_style.md) owns naming, explicit property declarations,
types, formatting, purpose comments and the conventions for a later handwritten
C++ port. In particular, declare properties in the class body and assign them
explicitly in constructors; do not use constructor property promotion.

Maintain the [native size and collection intent](docs/code_style.md#native-size-and-collection-intent)
comments on properties and signatures when changing their contracts. They guide
the future native port without changing PHP storage or introducing conversion tooling.

## Bounded echo output

See [output support and checks](docs/output_slice.md) and the
[echo follow-up debt](../specs/planning/echo_debt.md).

## AST ownership

`ast_file_node` is the sole frontend file result and retains the source tokens. Its keyed
`declarations` collection owns function nodes (`func:name`), with an implicit,
file-private `func:main` first when top-level statements exist. Bodies own their
statements, local collection and attached preparation. Global symbols reference
public AST nodes directly; there is no collected-function identity wrapper.
Parsing and local collection complete together; no separate file wrapper is retained.
The lasting [AST and preparation decisions](docs/ast_model.md) govern further simplification.

The parser keeps the first declaration under each key and links duplicates through
`duplicate_element()`, retaining every body. The completed root carries a simple
diagnostic list. Preparation reports all duplicates together before resolving names. `main` is reserved for the
implicit entry; explicit `main` declarations are not implemented. Constants and
class-like declarations are not introduced by this structural slice. CLI syntax
inspection keeps its existing statements/functions presentation as a derived view.

Check ownership with `php -n v0.2/compiler/tests/ast_file.php`.

## AST locations

Every syntax node derives from abstract `ast_node`, with inclusive `start` and
exclusive `end` token indexes and a parent link assigned once. Only the file root
has a null parent. Expressions, statements, bodies and declarations use this model. Names and
written type names are plain fields on their owners. There is no retained span/range object or per-node source pointer.

`root()` follows syntax ownership to the file's tokens/source. `byte_start()`,
`byte_end()` and `source_text()` recover original bytes from token offsets/lengths;
interior comments and whitespace are preserved. Empty ranges anchor at the next
token or source EOF. Synthetic entry name/type fields have no authored token.
Ordinary analysis reads fields directly; workers can retain source context locally.

Function headers are discoverable before their bodies complete: their parent,
start and header-end token boundary are settled, while full `end` is initialized
once at body completion. All nodes are fully attached before parsed-file publication.
Semantic links and symbol indexes do not change syntax parents. PHP uses ordinary
object references; native parent links are intended to be non-owning. Incremental
retokenization/reuse remains deferred.

The cursor returns consumed token indexes directly; no `token_range` class is
needed. CLI inspection still reports derived byte spans for compatibility.
Run `php -n v0.2/compiler/tests/ast_locations.php` for the location/ownership proof.

## Declaration facts

All AST subclasses use the `ast_` prefix. The `ast_declaration` trait shares only
name identity between function and local declarations; it owns no preparation logic.
`syntax_name` and `named_type_syntax` wrappers are gone. Existing simple grammar
positions recover precise name/type diagnostic locations without stored token fields.

- `ast_local_declaration`: optional written `$type_name`, protected resolved
  `$type_def`, checked `type_def()`, body-local ID and known initializer constant.
- `ast_function_declaration`: optional written `$return_type_name`, protected
  resolved `$return_type_def`, checked `return_type_def()` and its body.
- `ast_call_statement`: protected stable `$function_symbol` and checked
  `function_def()`, following the current declaration. No prepared-call/signature
  wrapper is needed. Bodies consume settled return facts without visiting callees.

The current builtin type definitions are shared `type_int64` and `type_void` objects
under the common `type_definition` contract, not per-use wrappers.
`int` and `int64` resolve to the same identity.
This representation covers the existing builtin subset; user-defined types remain
future work. Missing written types remain parseable where previously allowed but
local inference is still rejected during preparation; explicit globals admit integer-literal inference.

Collection indexes and prepared local references point directly to local AST
declarations; global reads retain stable symbol entries. Specialized facts remain for results such as decoded strings,
constant values, exit conversions and completed body output. Sequential reanalysis
resets owned facts; older handles must not be treated as immutable declaration
snapshots across reset. Incremental/concurrent reader lifetimes remain deferred.

Check phase boundaries with `php -n v0.2/compiler/tests/declaration_facts.php`.

## Next: modules and incremental compilation

See the [common module plan](../specs/planning/common_modules_and_incremental.md)
for the shared application/runtime model, initial full-rebuild rule and agreed
minimal records. The initial ownership/loading foundation is implemented; incremental reuse and
runtime metadata ingestion remain deferred.

## Module manifests

Use `project <module-dir|module.json> <facts.json> <output-dir>` for the common
manifest loader, discovery, runtime defaults and additive submodule activation.
See [the module format and native boundary](docs/modules.md). Dependency artifact paths are listed as `link_inputs`; dependency sources are not
loaded or scanned. Version/artifact checks and declaration import remain deferred.

The manifest command supports whole-project change detection: any changed input
rebuilds all compiler units; an unchanged invocation reuses a verified bundle.
Its JSON report includes `rebuilt`. The existing explicit-file and panel paths
remain full runs; native compilation/linking is still performed by the caller.

`Project_Compiler` is the reusable orchestration owner; CLI adapters only parse and
present. Unchanged application source inventories are not rewritten. See the module
guide for the application-only incremental boundary and manually built dependencies.

## Incremental frontend

`Project_Compiler::frontend($path, $previous)` performs tokenize/parse/collection
and symbol indexing only, reusing unchanged AST/token objects in memory. See
[incremental frontend generations](docs/incremental_frontend.md). Executable
compilation still runs full preparation/emission; incremental facts are deferred.

Dependency registration/dispatch uses the session-owned `Dependency_Watches` and
`dependency_watcher` interface. See [stable symbols and watches](docs/stable_symbols.md).
Run `php -n v0.2/compiler/tests/dependency_watches.php` from the repository root
for the generic batch/retirement contract.

## Namespaces

The [first namespace slice](docs/namespaces.md) supports one optional namespace
body per file, exact qualified function calls, and file-local `use`/`use function`
imports for cpp/php/js. Symbol renaming and homogeneous groups are supported;
namespace aliases and type/constant imports are not. Run
`php -n v0.2/compiler/tests/namespaces.php` and
`python3 -B v0.2/compiler/tests/namespaces_native.py` from the repository root.
The native proof retains Clang frontend/LLVM/linker traces and checks output/exit status.
For imports, run `php -n v0.2/compiler/tests/imports.php` and
`python3 -B v0.2/compiler/tests/namespaces_native.py --imports`.

Explicit [integer globals](docs/globals.md) now support literal initialization and
namespace-qualified reads across files and functions. Ordinary top-level locals
remain private to implicit main. Writes and configurable shadowing are deferred.
Run `php -n v0.2/compiler/tests/globals.php` and
`python3 -B v0.2/compiler/tests/namespaces_native.py --globals` for the proof.
