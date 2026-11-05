# Named arithmetic runtime bindings

Doc Status: supporting

This implements the arithmetic-binding portion of finding 4 in the
[saved metadata audit](../../specs/planning/compiler_metadata_audit.md).

`compiler.runtime_bindings` in runtime metadata owns each helper's public C++ symbol,
header, operand/result identities and supported lowering strategy. Arithmetic rows
refer to these records through `bindings`, keyed by operator. The three current
bindings are int64 division, int64 remainder and float64 division.

The generator resolves references and validates their declared signature and strategy
against the admitted operation. It rejects malformed symbols, non-public header paths,
missing references and incompatible contracts. This validates metadata consistency;
the native integration tests verify the actual runtime ABI. Metadata does not contain
executable C++ expressions. Runtime helper implementations/declarations remain unchanged.

`compilerArithmeticBinding()` performs generation-time resolution. `compilerBinary()`
emits the resolved symbol and groups required headers by operator. There is no new
per-expression lookup, AST property, prepared fact or runtime object. Default generated
PHP remains byte-for-byte identical, so the inventory version is unchanged.

Division checks, signed overflow behavior, compound orchestration and operand safety
remain unchanged. Native operators still use native syntax. Copy semantics, operation
safety metadata and echo are separate future work.

## Verification

Seven focused CTests passed: generator, integer/float arithmetic, integer/float compounds,
arithmetic runtime helpers and runtime freshness. Generator mutation tests verify that
changing symbol/header metadata changes generated output, and reject invalid references,
signatures, strategies and spellings. All eight generated files match the pre-change files.

The integer and floating native proofs passed for cpp/php/js. They cover zero-divisor
failures and normal execution; semantic checks cover signature changes, invalidation and
recovery. Floating incremental tests compare emitted artifacts with fresh compilation,
retry failed runs without edits, and verify unchanged caller AST reuse.

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime; fresh instrumented application
objects. These are single correctness runs, with the two proof suites running concurrently,
not a comparative performance benchmark. Floating recovery builds:

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 142.71 | 17.61 | 23.50 | 68.00 |
| php | 150.80 | 20.28 | 25.06 | 72.00 |
| js | 123.06 | 13.05 | 17.94 | 63.00 |

Frontend/backend sums are cumulative trace durations and are not additive wall-time
components. Full results, including integer builds and the heavier IEEE/exception
observer fixtures, are retained under `v0.2/build/arithmetic-bindings-proof/`.

## Checked conversions and string storage

The same `compiler.runtime_bindings` catalog now includes named `kind: type` records
for boxed int64, byte, float64 and string storage. Each declares its semantic identity,
public C++ symbol and header. Wrappers used to extract a native result also declare
an `extract` member. No arbitrary executable expressions are accepted.

Checked conversion rows reference `target_binding` and, for floating-to-integer,
`source_binding`. String's value-type row references `storage_binding`, replacing its
separate `cpp_type` spelling. `compilerTypeBinding()` validates and resolves these
records during generation. The normalized conversion plan retains resolved bindings;
there is no application-compilation metadata lookup or new AST/preparation structure.

Strategies still own wrapper construction, range checks, truncation, constant conversion
and diagnostics. Proven-safe branches remain native casts and require no wrapper
headers. String decoding, byte escaping and length computation remain handwritten.
Symbols and extraction calls in generated lowering are unchanged; only the generated
conversion-header methods change from target-specific ternaries to binding-derived
matches. String's generated class remains byte-identical. Safety and echo are unchanged.

Generator tests verify binding substitution and reject missing bindings, wrong identities,
wrong kinds, invalid symbols/headers and extraction members. Eight focused CTests pass.
The existing cast and string native suites pass, including checked exceptions and copy
behavior. `tests/type_bindings.php` additionally checks all three flavors for byte/int
checked conversion and string storage: edits, provider declaration removal, repeated
failure without edits, re-addition, unchanged caller AST reuse and no-op reuse. Successful
incremental output is compared with fresh compilation. All nine recovery executables
pass. Raw traces and results are in `v0.2/build/type-bindings-proof/`.

Single-run recovery timings (same O2/g0, Ninja8/mold configuration above):

| Flavor / destination | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp_byte | 149.94 | 16.12 | 22.03 | 73.00 |
| cpp_int | 150.03 | 18.61 | 18.25 | 71.00 |
| cpp_string | 143.78 | 23.17 | 21.59 | 71.00 |
| php_byte | 147.90 | 17.02 | 21.45 | 76.00 |
| php_int | 164.84 | 26.23 | 26.31 | 74.00 |
| php_string | 140.59 | 25.17 | 20.42 | 64.00 |
| js_byte | 142.31 | 14.02 | 19.04 | 69.00 |
| js_int | 146.73 | 18.59 | 23.01 | 72.00 |
| js_string | 145.38 | 22.71 | 19.75 | 73.00 |
