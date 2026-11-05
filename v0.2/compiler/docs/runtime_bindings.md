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
safety metadata, conversion bindings, string storage and echo are separate future work.

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
