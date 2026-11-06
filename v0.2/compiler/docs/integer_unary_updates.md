# Integer-family signs, updates and truthiness

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#integer-family-signs-updates-and-truthiness--2026-11-06)
owns this slice. Int64/int and uint8/byte share generated unary signs, discarded-value
updates and zero/nonzero truthiness. Unsigned results preserve their representation:
byte negation and updates wrap modulo 256. Signed overflow retains the native contract.

`-(byte)1` is byte 255; `(-(byte)1)+1` is int64 256. Byte 255 followed by `++` becomes
zero; byte zero followed by `--` becomes 255. In contrast, byte `+=1` at 255 still
fails its checked write-back because the literal RHS selects int64 execution.

Conditions and `!`, `&&`, `||` reuse generated truthiness and preserve short-circuit
evaluation. Updates remain discarded-value-only, including supported for headers;
new lvalues, value-producing updates, nullable conditions and optional widths are deferred.

## Ownership and structures

`compiler.integer_operators` now owns `unary`, `updates` and `condition`, replacing
the concrete int64 rows in `compiler.unary`, `compiler.updates` and `compiler.conditions`.
Float and other family policies retain their owners. Integer expansion validates
runtime unary/update `preserve_operand` contracts and supplies transient concrete rows
for existing handler rendering. Duplicate per-type and family ownership is rejected.

The unary/update renderers validate canonical numeric family identity rather than a
hardcoded int64/float64 name list. Narrow integer unary lowering explicitly casts the
native result back to its metadata representation, preventing C++ integral promotions
from leaking into enclosing expressions. Native ++/-- already preserves unsigned
storage semantics. Conditions use the existing nonzero integer generator.

No AST class, property, prepared fact, enum, watcher or queue changes. Existing unary,
update and condition paths are reused. No preparation-time metadata interpretation,
extra traversal, synthetic binary/cast AST or new folding. Native ownership gates and
incremental invalidation remain unchanged.

## Verification

All three flavors cover nesting, representation before enclosing arithmetic,
short-circuit side effects, single evaluation, exact preparation visits, reset/reprepare,
emission purity, reference writes, while/do-while/for headers and rejection boundaries.
The native observer checks all 256 byte values for both signs, nested negation, every
prefix/postfix update form, truthiness and checked compound contrast.

Retained projects exercise body edits, int/byte/uint8 signature transitions, invalid bool
updates, out-of-range byte declarations, retries without edits, deletion/re-addition,
caller edits and no-op reuse. Unchanged caller ASTs survive; recovered artifacts match
fresh compilation. The native proof executes 21 fixtures, including recovered programs.

One stale float-arithmetic assertion still rejected integer-RHS compounds supported
by earlier slices; it now checks the still-rejected bool RHS. No float behavior changed.

## Timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime, fresh instrumented app objects.
Single correctness samples, not flavor rankings. LLVM includes optimization/codegen;
frontend/LLVM trace work and link time do not sum to parallel native wall time.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 142.21 | 13.85 | 17.26 | 67.00 |
| php | 131.89 | 11.24 | 16.01 | 58.00 |
| js | 133.37 | 14.75 | 18.94 | 54.00 |

PHP fixture generation plus semantic/incremental checks: 163.90 ms.
Raw evidence: `v0.2/build/integer-unary-updates-proof-2026-11-06-final/results.json`.

Validation: 15 focused semantic/style/generator tests and eight native/runtime regression
tests passed. Generator proofs cover 19 accepted/537 rejected scalar cases,
130 accepted/247 rejected runtime-family cases and 17 accepted/37 rejected CLI cases.
Scalar runtime artifact hashes and runtime-family snapshots remain unchanged; only
metadata-derived input/case fingerprints needed refreshing.
