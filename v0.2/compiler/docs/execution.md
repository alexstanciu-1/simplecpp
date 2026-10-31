# PHP execution

Doc Status: supporting

The PHP compiler is a sequential bootstrap implementation for the later Simple C++
compiler. It requires 64-bit PHP 8.5 or newer. Ordinary NTS PHP works; ZTS is also
accepted, but neither ZTS nor the Parallel extension is required. There is one
production execution path and no MT/serial mode selector.

## Current flow

1. `Program_Loader` validates the explicit inventory without loading source contents.
2. `Program_Frontend` runs each source-local frontend operation in order: read the
   file, tokenize, then parse while collecting symbols locally. The parser returns
   a complete `ast_file_node` owning tokens, keyed declarations and body-local collections; a failed
   parse publishes no partial result. The source result does not retain its parser
   or collector.
3. Parser discovery notifications build a coordinator-owned pending function index.
   After all collection succeeds, it is published and every function signature settles.
4. Function bodies, including the selected implicit private `main`, prepare through
   the same body path against the frozen signatures.
   Secondary sources may define functions but cannot execute top-level statements.
5. `Cpp_Program` renders the successful prepared program and assembles its units.
6. The CLI publishes the completed bundle through `Cpp_Bundle`.

Syntax, collection and prepared facts remain ordinary in-process objects. No
transport encoding, worker-local replicas or export/import protocol is needed.
Function return type facts live directly on declaration nodes and function body facts
to their body nodes. Completed handles gate emission. Calls and variable references link directly to declarations. Specialized expression
facts still retain additional results such as constant values and decoded bytes. The entry completion
handle shares statement facts with its AST body; it does not own separate syntax. Classes, inheritance and
incremental reuse remain future slices; see the [function guide](functions_slice.md).

The [agreed native ownership plan](../../specs/planning/two_traversal_preparation.md)
covers the frontend, declaration preparation, body preparation and private lowering.
Shared lowering needs will be reviewed individually and routed through the coordinator.
The implementation still needs review against that plan;
sequential execution does not establish native thread safety.

## Tools and checks

```sh
php v0.2/compiler/tests/tokenizer.php
php v0.2/compiler/tests/parser.php
php v0.2/compiler/tests/frontend.php
php v0.2/compiler/tests/ast_file.php
php v0.2/compiler/tests/functions.php
php v0.2/compiler/tests/entry.php
php v0.2/compiler/tests/program.php
python3 -B v0.2/compiler/tests/cli.py
python3 -B v0.2/compiler/tests/entry_native.py
python3 -B v0.2/compiler/tests/program_native.py
python3 -B v0.2/compiler/tests/functions_native.py
```

Python test drivers use `php` from PATH, or the `PHP_EXECUTABLE` environment variable
when set to a binary path. CMake independently uses its `PHP_EXECUTABLE` cache
setting and verifies version/width. Reconfigure existing builds with
`cmake --preset default` from `v0.2/` to regenerate launchers and retire MT tests.
No system PHP configuration or installed ZTS toolchain is removed or changed.

## Retired PHP MT experiment

On 2026-10-30 the user chose to stop maintaining PHP-specific threading machinery.
The resident runtimes, transport interface, serialized receipts, MT tests and
benchmark drivers were removed from the active source tree. Export/import of
attached semantic facts had only been discussed; it was never implemented.

Retained local evidence (build artifacts, not active compiler dependencies):

- `v0.2/build/php-parallel-retired-20261030.tar.gz`: compiler, launcher and CMake
  snapshot immediately before retirement, including benchmark drivers.
- `v0.2/build/source-modes/`: single-source serial/MT comparison results.
- `v0.2/build/dev002-24-units-20261030/`: remote compiler snapshot, 24 generated
  sources, raw samples, logs and report. Its `completed-benchmark.tar.gz` is a
  self-contained copy of the completed remote experiment.

The remote experiment is also retained at
`/home/alex-ai/php-8.5.7-zts-parallel-1.2.15/benchmarks/compiler-24-units.LstyT8nF`
on dev002. Historical results apply to those snapshots, not the current pipeline.
The 50 KiB source fixture remains in `examples/benchmarks/50k.scpp`.
