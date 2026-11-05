# Bounded echo output

Doc Status: supporting

`echo` currently accepts one or more comma-separated int64 or string values,
with identical behavior in cpp/php/js flavors. It works in the entry body and
function bodies, including calls across files. Output has no automatic
separator or newline.

```text
function ping(): void { echo "called\n"; }
$ret int = 8;
echo $ret;
ping();
return $ret;
```

This PHP-flavor example prints `8called` followed by a newline and exits with 8.
Use existing flavor-specific variable/function declaration syntax for cpp/js.

Single quotes decode only escaped quote/backslash. Double quotes accept `\\`,
`\"`, `\$`, `\n`, `\r`, `\t` and isolated `\0`. Interpolation, other escapes,
empty operand lists, trailing commas and unsupported value types diagnose. String literals and typed string locals are supported. Source spelling must be valid UTF-8 (including quoted text); NUL is
allowed inside literals. The runtime byte-output API does not impose that encoding
restriction on its input buffers. String operators, interpolation and text conversions remain deferred.

## Ownership

The parser retains literal token positions and a named echo statement. Collection
records ordinary local references. Preparation resolves integer bindings and
decodes literal bytes into immutable output facts. Lowering consumes those facts
without source-name lookup, decoding or mutation.

The C++ backend includes `scpp/output.hpp` only when needed and calls the compiled
runtime output API. Its private implementation formats integers using locale-independent `std::to_chars`; strings use length-aware
`std::fwrite`, preserving NUL and arbitrary bytes. Literal spelling uses fixed-width
octal escapes. A reported short write throws; this does not promise flushing on
every statement or detection of later buffered flush failures.

This follows the donor's separation of literal preparation from output emission.
The donor's general runtime output system was not copied into the bounded slice.
The [full output contract](../../../specs/output_v0_2.md) remains authoritative.
**@todo:** return to [echo debt](../../specs/planning/echo_debt.md) for broader
operands, full strings/interpolation, runtime integration and IO/error semantics.

## Verification

From the repository root, with the v0.2 native runtime already built:

```sh
php -n v0.2/compiler/tests/output.php
python3 v0.2/compiler/tests/output_native.py
```

Checks cover direct local bindings, traversal counts, lowering purity, equivalent
flavors, exact output bytes, no automatic newline, integer limits, CLI inspection,
invalid operands/escapes, cross-file call order, and artifact preservation on errors.
A body edit changes stdout and only its owning translation unit. The chained-output proof additionally verifies incremental recovery against fresh compilation.

## Metadata-driven admission and ordered operands

`compiler.output` declares integer and byte-string strategies, referencing `echo_int`
and `echo_bytes` in the named runtime binding catalog. The generator validates active
type/strategy compatibility and binding signatures, and generates `supports_output()`,
`output_headers()` and `lower_cpp_output()`. Unsupported types keep conservative base
methods. No implicit conversion or `to_string` capability is introduced.

`ast_echo_statement.values` owns an ordered, nonempty list of expression children,
replacing `value`. Collection, preparation, reset and inspection traverse every operand.
Syntax and compilation inspection expose `values` arrays; literal byte inspection is
preserved. Each operand is prepared once and the complete statement must pass admission
before any executable is emitted. No extra prepared facts are stored.

Emission produces one output statement per operand, preserving print-before-next
semantics. The handwritten `Cpp_Output::bytes()` strategy retains the literal-byte
optimization and the scoped reference that keeps a returned string alive through both
span queries. Integer output consumes native int64 directly. Headers use the existing
required-header index; the separate `uses_output` plumbing is removed.

`tests/echo_chain.php` and `echo_chain_native.py` cover all three flavors: ordered
side effects, returned strings evaluated once, embedded NULs, rejected types, syntax
errors, AST ownership, lowering purity, and a runtime failure that prevents later
operands from executing. Provider edits, int/string signature changes, invalid bool
signatures, declaration deletion, repeated failure without edits and re-addition are
compared with fresh output. Unchanged callers reuse their AST; no-op runs prepare no
bodies. Runtime formatting and IO-error behavior are unchanged. Echo debt remains for
additional types, formatting and interpolation; general string conversion is deferred.

Native proof artifacts: `v0.2/build/echo-chain-proof-complete/`. O2/g0, libc++,
Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented application objects.
Fifteen executables passed (order, failure and three recovery states per flavor).
Single-run recovery timings below are correctness measurements, not speedup claims;
frontend/LLVM totals are cumulative traces and not additive wall-time components.

| Fixture | Native ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp_recovery_"B" | 129.87 | 23.11 | 16.66 | 62.00 |
| cpp_recovery_"C" | 133.61 | 20.03 | 18.41 | 66.00 |
| cpp_recovery_10 | 121.17 | 11.77 | 14.84 | 60.00 |
| php_recovery_"B" | 128.48 | 19.79 | 17.75 | 65.00 |
| php_recovery_"C" | 137.42 | 25.37 | 19.84 | 66.00 |
| php_recovery_10 | 126.09 | 12.29 | 15.25 | 64.00 |
| js_recovery_"B" | 126.50 | 18.53 | 16.33 | 62.00 |
| js_recovery_"C" | 128.82 | 18.17 | 16.65 | 64.00 |
| js_recovery_10 | 120.30 | 10.64 | 18.39 | 59.00 |
