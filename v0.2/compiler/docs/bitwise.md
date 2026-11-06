# Integer bitwise and shifts

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#integer-bitwise-and-shift-group--2026-11-06)
and [checked count contract](../../../specs/compact_layout_types.md#71-checked-integer-shift-counts)
own this slice. Int64/int and uint8/byte support `& | ^ ~ << >>` and five compounds.
Bitwise binaries reuse integer promotion and checked compound write-back. Unary `~`
and shifts preserve the operand/left representation. Counts use the declared left
width: 0–7 for byte, 0–63 for int64. Invalid counts fail, including large positive
counts that would otherwise become valid if narrowed. No masking or count truncation.

## Ownership and structure changes

`binary_operator` adds bit_and, bit_or, bit_xor, shift_left and shift_right;
`unary_operator` adds bit_not. No AST fields or classes are added. Existing tokenizer
kinds and `token_list::operator_text_at()` preserve adjacent angles; the parser consumes
two adjacent angle tokens for a shift. Compound tokens already exist. Precedence is
encoded in the existing operator enum; typed-reference ampersands remain parser-local.

`compiler.integer_operators.bitwise` expands through the existing integer execution
and promotion path. Its `shifts` section declares the operators, count transport
representation and per-left named runtime bindings. Transient `compiler.shifts` rows
feed the existing binary execution plan and emit direct count-type admission. They
are not retained in a compiler session. Duplicate policy owners and narrowing count
transport are rejected during generation. `bit_not` uses the existing unary renderer
and explicit narrow-result restoration.

Shift preparation retains existing left execution/result facts and identity operand
conversions. It does not convert the RHS to the left type, allocate a second AST,
revisit operands, fold constants or add watcher records. Existing compound snapshots
and failure behavior remain intact. Lowering calls public named helpers; the
`checked_shift` safety strategy is explicitly non-reorderable.

Four thin functions in runtime numeric arithmetic expose the existing checked shift
helpers through generated `scpp/arithmetic.hpp`. Their left type is int64 or uint8;
counts use int64. Generation proves that every activated count domain fits this
transport. Byte counts widen losslessly at the native function boundary; optional
uint64 counts cannot silently activate through this ABI. Public includes remain
minimal (`stdint.h`); native count checks and representation behavior stay in the runtime.
ABI declaration consolidation remains its previously recorded debt.

## Verification

All three flavors cover every binary operator/type pairing, unary complement,
compound forms, precedence, adjacency rejection, reference parameter syntax, for
headers, result identities, exact preparation visits, reset/reprepare and lowering
purity. The native observer exhausts byte bitwise pairs and every valid byte shift
count, checks signed extremes/all valid int64 counts, invalid negative/large counts,
failed-store behavior and RHS mutation before failure. Checked byte casts remain distinct
from byte complement.

Incremental projects cover body/signature changes, rejected bool signatures, out-of-range
byte declarations, no-edit retries, deletion/re-addition and caller-operator edits.
They retain unchanged caller ASTs, compare recovered artifacts with fresh compilation,
and verify no-op reuse. An edit splitting `>>` into `> >` fails parsing and recovers
identically to fresh compilation after restoration. Twenty-four native fixtures pass,
including recovered applications and checked-cast failures.

The donor's parser precedence/adjacency design was reviewed. Its historical unchecked
shift-count behavior was not adopted; the newer specification/runtime checks prevail.

## Timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime, fresh instrumented application
objects. Single correctness samples. LLVM includes optimizer/codegen; cumulative trace
work and linker timing do not sum to native parallel wall time.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 122.32 | 11.55 | 11.38 | 57.00 |
| php | 128.59 | 13.20 | 12.22 | 58.00 |
| js | 125.56 | 12.44 | 11.22 | 58.00 |

PHP fixture generation plus semantic/incremental checks: 161.35 ms.
Raw evidence: `v0.2/build/bitwise-proof-2026-11-06-final/results.json`.
The additional spaced-shift incremental recovery check was subsequently run in PHP.

Validation: 12 focused semantic/style/generator tests passed, followed by six
native/generator/freshness regressions. Generator captures passed 19 accepted/537
rejected scalar cases, 130 accepted/247 rejected runtime cases, and 17 accepted/37
rejected CLI cases. Snapshot review found only the four-declaration addition to
`public/scpp/arithmetic.hpp` and metadata-derived input identities; other runtime
artifacts remained unchanged. Final PHP fixtures match the native-tested contents
after normalizing filenames derived from temporary source paths.
