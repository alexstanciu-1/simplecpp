# Bounded echo output

Doc Status: supporting

`echo` currently accepts one integer literal/local or one quoted string literal,
with identical behavior in cpp/php/js flavors. It works in the entry body and
void function bodies, including calls across files. Output has no automatic
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
multiple operands and unsupported value types diagnose. String literals are
output-only; this slice does not introduce general string locals or expressions.

## Ownership

The parser retains literal token positions and a named echo statement. Collection
records ordinary local references. Preparation resolves integer bindings and
decodes literal bytes into immutable output facts. Lowering consumes those facts
without source-name lookup, decoding or mutation.

The C++ backend temporarily emits source-local stdout helpers only when needed.
Integers use locale-independent `std::to_chars`; strings use length-aware
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
A body edit changes stdout and only its owning translation unit. This is a complete
rebuild proof, not incremental reuse.
