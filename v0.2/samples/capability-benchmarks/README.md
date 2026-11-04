# Current-capability benchmark apps

Doc Status: supporting

Three stable, executable Simple C++ applications for the current v0.2 subset.
Existing `my-try-simple`, the 50K source and panel integer/echo samples are retained
unchanged. These samples supplement their historical timing baselines.

| App | Source files | Source size | Purpose |
| --- | ---: | ---: | --- |
| small | 4 | 2,742 bytes | Readable feature tour with one invoice calculation. |
| medium | 15 | 27,753 bytes | Invoice-like multifile calculation with 48 separately callable line-pricing functions. |
| large | 67 | 274,274 bytes | The same supported workload scaled to 512 line-pricing functions. |

Each project mixes PHP, C++ and JS syntax flavors, uses a real `module.json`, and
selects `main.phs` as its entry. `expected.json` specifies exact stdout, exit code 8,
and the controlled incremental mutations. The C++-flavor `.scpp` files are Simple
C++ source, not generated C++ or native runtime sources.

Original baseline: [2026-11-04 report](benchmark-2026-11-04.md).
Latest comparison: [direct lowering and PCH](benchmark-2026-11-04-direct.md).

## Capability coverage

- int/int64, bool, byte, float/float64, null and owning strings: locals, typed
  parameters and returns, plus supported generated typed-destination conversions.
- Explicit and inferred locals, assignments, lexical shadowing and string copies.
- Multiple positional arguments, nested calls, trailing literal defaults, mutable
  references, value and void functions, explicit/implicit void return.
- Namespaces, qualified cross-file calls, grouped imports and a renamed function
  import, plus a cross-file explicit integer global.
- All six int/float comparisons, bool/byte equality, int arithmetic `+ - * / %`,
  grouping and operator precedence.
- Bool/int/float/string conditions, braced if/else-if/else, while and do-while.
- Integer/string output and the checked integer executable-entry boundary.

This is capability coverage, not every syntax permutation or error path. Dedicated
compiler tests retain overflow-boundary, zero-divisor, rejection and recovery
proofs. The samples do not invent unsupported classes, containers, exceptions,
string operations, unary integer operators or floating arithmetic.

`support/core.scpp` is the compact feature tour. `support/labels.jss` supplies
string/default/void functions and is the incremental-edit target. `billing/`
contains the pricing work; main consumes every result in an output checksum.
The medium/large apps are synthetic approximations of invoice work, not production
accounting programs. Cross-translation-unit calls and no LTO prevent whole-program
constant folding; Clang may still inline/fold local work, particularly the small
feature tour. The large sample repeats a deterministic workload to measure scaling.

## Reproduction

From the repository root, with the current generated API and runtime archive built:

```sh
XDEBUG_MODE=off cmake --build v0.2/build/clang --target scpp_runtime -j8
XDEBUG_MODE=off python3 -B v0.2/samples/capability-benchmarks/benchmark.py \
  --output v0.2/build/capability-benchmarks-my-run
```

The output directory must be new. The runner copies only source/manifests into it;
all compiler-generated inventories, C++, objects, executables and source mutations
remain there. It never edits the checked-in sample sources. Build artifacts are
untracked; a compact report and raw timing summary may be retained alongside this
README after review.

`generate.py` deterministically recreates the checked-in source samples. It is a
sample authoring utility, not compiler generation. Keep sample size/shape stable
when comparing revisions; intentional workload changes require a new baseline.

## Measurement boundaries

- Five PHP stage samples after one warm-up: module load, source read/snapshot,
  tokenize, parse/local collect, symbol publication, prepare and C++ text emission.
  Total includes orchestration; excludes writing C++ and PHP process startup.
- Three native samples after a warm-up: every application object and executable
  is rebuilt. O2/g0, libc++, mold, Ninja -j8, no PCH/LTO/ccache, prebuilt runtime.
  Native wall and linker command ranges are reported separately.
- One separate instrumented build records Clang frontend, LLVM backend, optimizer
  and code-generation times. Categories overlap and sum across parallel TUs;
  their sum is not elapsed build time. Instrumentation is outside native medians.
- Every measured/traced executable checks exact stdout and exit status; debug
  sections are rejected. Generated includes must use existing runtime public APIs.
- OS/tool versions, CPU topology, exact source/compiler hashes, runtime archive
  hash, logs, raw samples and Clang traces are retained in the output directory.
  Memory reports are PHP process peaks, not isolated per-stage allocations.

## Incremental checks

Five independent retained-session sequences per app, with no compiler restart
within each sequence:

1. Full frontend and preparation from a null prior session.
2. No-op: zero source reads, identical AST/token objects, zero prepared bodies.
3. Body edit: change the label function's returned string without changing its
   signature. Only that source is read/reparsed; its two bodies are prepared,
   while the caller and other bodies remain ready.
4. Signature edit: change that function's return type to int with a valid integer
   body. The unchanged caller must be invalidated and reject the int-to-string
   use. This is a deliberate diagnostic, not a failed benchmark run.
5. Recovery: restore the original function. Only its source is reread; both source
   bodies and the affected caller prepare successfully, retaining other ASTs.
6. Final no-op: all bodies and source ASTs are reused again.

File edits change byte size so the current mtime+size detector sees them without
sleeps. Counts are asserted, not inferred from timing. Both frontend and preparation
wall times are retained. Incremental emission/native compilation is not implemented;
these measurements do not claim incremental executable builds. Artifact correctness
is verified on the unedited full-build samples.

For the separate reused-PCH comparison, point at an existing large sample output:

```sh
XDEBUG_MODE=off python3 -B v0.2/samples/capability-benchmarks/pch-benchmark.py \
  --input v0.2/build/capability-benchmarks-my-run/large \
  --output v0.2/build/capability-pch-my-run
```

The runner compares no PCH, reused output/entry PCH, and reused output/entry/string/
arithmetic PCH. It changes no panel or compiler settings.
