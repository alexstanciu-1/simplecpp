# Canonical byte/uint8 identity

Doc Status: supporting
Date: 2026-11-06

The [agreed scalar model](../../../specs/compiler_scalars_v0_2.md#agreed-family-and-representation-model--2026-11-06)
owns the direction. This is the identity/representation slice, not completion of
numeric operators, promotions or family-derived conversions.

## Canonical ownership

Runtime metadata defines uint8 once with unsigned eight-bit representation.
`integer_aliases.byte` points to uint8. `core_integers` makes int64 and uint8 core;
other integer representations still require the numeric module. Both byte and
uint8 resolve to one compiler `type_uint8` singleton. Integer literals retain int64.
Alias names are derived from metadata, not a second compiler registration list.

The generator no longer adds a separate byte record/family. Integer-literal handling
is separated from nonliteral integer representation generation; the latter supplies
the uint8 native representation and range. Checked integer conversion strategy guards
now inspect integer domains rather than a special byte category. Current conversion
rows still admit only int64↔uint8. Cross-type assignment stays rejected, while byte
and uint8 assignment/reference binding are exact identity operations.

Runtime publication canonicalizes integer type and operator requests before emission.
Requesting both names emits one class and one overload per canonical signature.
Repeated identical requests remain errors; alias-equivalent requests coalesce.
The public API now has `class uint8` and `using byte = uint8`, replacing class byte.
Existing constructors/bitwise/shift/equality requests lower to canonical symbols.
Source byte spelling is preserved in syntax; resolved facts/diagnostics use uint8.

## ABI, cleanup and intermediate coverage

C++ mangled identity changes; an old runtime archive must not be mixed with new
headers. The runtime and native test consumers were rebuilt. No ABI compatibility
wrapper or dependency-version machinery was added. Compiler generation inventory
version 18 removes obsolete types/byte.php and emits types/uint8.php. Boot/CMake
byproducts and tests refer to the canonical class. No AST fields, watcher records,
queues or conversion enums were added.

The old byte-only runtime operation validator is removed. Explicit runtime
publication requests and ordinary integer operation policies now control admission;
the default publication still requests the prior operations. Compiler operation rows
remain equality-only for uint8 until grouped operator policy is agreed. Runtime
`deferred_scalar_casts` explicitly retains uint8 bool/float/text exclusions during
this intermediate migration; it is not a semantic integer-family restriction.
The full-family generator proofs explicitly clear that deferral to test
existing generic runtime capabilities. Source compiler admissions remain unchanged.

## Verification

All flavors test canonical registration, byte variable→uint8 reference parameter,
byte value parameter→uint8 return, alias assignment/equality, checked narrowing and
rejected ordinary cross-type assignment/arithmetic. Incremental tests cover alias-only
signature edits, incompatible int signatures, overflow, retry without edits, deletion,
re-addition and caller edits. Unchanged caller ASTs survive; successful C++/headers
match fresh compilation byte for byte and recovery executables run natively.

The public native consumer asserts byte/uint8 type identity and one-byte storage,
then checks existing conversions, bitwise/shift operations and failure behavior.
Publication tests request both aliases and equivalent operators and verify identical
prepared output with no duplicate declarations. Generated-file retirement is checked.
Reviewed artifact snapshots were refreshed for the canonical ABI; the corpus and
native layout facts remain unchanged. Runtime-family proof: 130 accepted/247 rejected;
scalar proof: 19 accepted/537 rejected, including generated native consumers.

Raw native proof: `v0.2/build/byte-alias-proof-2026-11-06/` (18 executables).
O2/g0, libc++, Ninja8, mold, no PCH/LTO; rebuilt then prebuilt runtime archive and
fresh instrumented application objects. Single samples, not comparative averages.
Main cpp: 136.49 ms wall, 10.21 ms frontend, 9.11 ms LLVM, 65 ms link.
Main php: 155.80 ms wall, 13.83 ms frontend, 13.02 ms LLVM, 70 ms link.
Main js: 181.35 ms wall, 10.61 ms frontend, 10.65 ms LLVM, 88 ms link.
Full per-flavor and recovery timings are in results.json; LLVM includes optimizer/
code generation and cumulative trace work is not additive wall time.
