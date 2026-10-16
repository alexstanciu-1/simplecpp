# Integer method parameter defaults
Doc Status: supporting

Nonnullable `int` parameters on ordinary instance/static methods, expanded trait
methods, abstract methods and interface declarations accept integer literal
defaults. Parameters stay required unless they have an explicit default. Optional
parameters must follow required parameters.

```php
public static function prepare(int $required, int $start = 0): int {
    return $required + $start;
}
```

Omission supplies the declared value. Explicit zero, negative and positive values
replace it; multiple trailing optional parameters may be partially omitted.

The converter shares one integer-literal reader with fields. An unsigned
`T_LNUMBER` keeps its spelling, including base prefixes and digit separators.
One `+` or `-` is accepted only before a plain decimal magnitude that also tokenizes
as `T_LNUMBER`. Ordinary comments/whitespace between sign and magnitude are allowed.
Signed nondecimal/separated forms, repeated signs, parentheses, constants, calls,
arithmetic and floats are rejected. On the proved 64-bit PHP host, the signed
magnitude can reach `9223372036854775807`. The magnitude in
`-9223372036854775808` tokenizes as a float; PHP rejects that typed parameter default
and the converter does not coerce it.

Existing bool defaults and nullable-null defaults retain their contracts. `?int`
still accepts only `null` as a default. This slice does not change string/float/enum
defaults, constructors/promoted parameters or callable-reference adaptation.

## Virtual dispatch boundary

**Keep matching defaults across an override chain and its interface declarations.**
The converter is structural and does not validate this requirement. In native C++,
default arguments come from the static declaration at the call site. PHP can use
the override's default instead. The focused proof records a base default of `4`
and override default of `9`: a base-typed caller receives `4` natively and `9` in
PHP. This form is not portable; no dispatch adapter is synthesized to hide it.
Explicit arguments avoid this default-selection difference.

## Lowering and cost

PHS preserves the default in the signature. The existing downstream generator
emits a typed C++ default in the header declaration and omits it from the
out-of-line definition. Separate-file callers can omit arguments. There is no new
forwarding wrapper, nullable box, allocation or runtime branch for choosing a
literal default. No speedup or memory reduction is claimed over passing it
explicitly. Operand/type and missing-call-argument validation retain their existing
owners; the converter does not infer callee signatures at every call.

## Proofs

Run from the repository root with fresh results directories:

```sh
python3 tests/portability/integer_method_defaults.py --results /tmp/integer-method-defaults
python3 tests/portability/integer_method_defaults_compiler.py --results /tmp/integer-method-defaults-compiler --baseline /path/to/pre-migration-baseline
```

The standalone proof checks 12 literal forms, omitted/explicit/partially omitted
calls, shared declaration forms, cross-file header/definition placement, 19
structural rejections, required-argument native rejection, conversion reuse and
failure publication. It retains the differing-virtual-default observation as an
explicit limitation, not a successful PHP/native parity case.

The signature and nullable-parameter regression runners exercise PHP/conversion;
the new fixture proves the relevant current native declaration forms. The retained
signed-field runner also executes its PHP/native proof. Native builds use the
my-try `--no-stan` workflow; this is not a STAN result.

The compiler proof compares pre-migration `control_flow`, `constructors` and
`inheritance` artifact folders, rebuilds my-try, and executes focused ordinary,
block and explicit-parent-constructor programs. The retained body witness checks
omitted start/explicit zero against explicit start `1`, including attached facts,
completion and a deliberately invalid first transfer that must be skipped only
by the nonzero start. The compiler's computed constructor skip remains explicit.
See the [compiler review](../../compiler/my-try/docs/planning/conversion_quality.md)
for dated evidence and build-input hashes.
