# Signed integer field defaults
Doc Status: supporting

Ordinary class `int` and `?int` fields accept one unary `+` or `-` followed by a
plain decimal integer magnitude: `0` or a nonzero digit followed by digits. This
applies to instance and static fields with explicit public/protected/private
visibility. Whitespace and ordinary comments between sign and digits are allowed.
For example:

```php
class Entry {
    public int $position = -1;
    public ?int $previous = -1;
    public static int $limit = +17;
}
```

The converter preserves the sign and magnitude as syntax. It does not evaluate an
expression, infer types or synthesize a constructor. Existing unsigned integer
literal spellings and nullable `null` defaults retain their previous handling.

The signed magnitude must tokenize as `T_LNUMBER`. On the proved 64-bit PHP host,
`+9223372036854775807` and `-9223372036854775807` work. The magnitude in
`-9223372036854775808` tokenizes as `T_DNUMBER`; PHP also rejects that spelling as
an `int` property default. The converter rejects it rather than coercing a float.
This is not a portable integer-width emulation facility.

Signed nondecimal literals, digit separators, repeated signs, parentheses,
constants, calls and arithmetic are outside this grammar. This field extension does not
change `uint32` annotations, readonly fields or value records. Method parameters
now have a separate [integer-default contract](integer_method_defaults.md);
constructor/promoted-parameter defaults remain unchanged.

A declaration default is visible before that class's constructor body. Replacing
an assignment after a base-constructor call can change observable ordering; audit
that case separately. Removing an assignment-only constructor is appropriate only
when its removal preserves construction behavior.

The generated native field retains its integer type and uses a direct in-class
initializer; static fields use their existing definition path. Nullable fields
retain the existing nullable wrapper. No runtime helper, new allocation, or layout
change is required. No performance improvement is claimed.

## Focused proof

Run from the repository root, using a fresh results directory:

```sh
python3 tests/portability/signed_integer_fields.py --results /tmp/signed-fields-proof
```

The proof covers PHP/native values, constructor observation and overrides, fresh
instances, static state, nullable null/zero/negative distinctions, signed bounds,
39 conversion rejections, unchanged neighboring field paths and existing nullable
regressions. It checks failed conversion preserves published files and cache,
and inspects generated field types/initializers. Native runs use `--no-stan` under
the my-try portability workflow; this is not a STAN proof.

`tests/portability/signed_integer_fields_compiler.py` separately rebuilds my-try,
checks `prepared_data_entry` default/update/fresh-instance behavior inside that
native compiler, compares PHP/native record and sequence artifacts, and executes
the emitted programs. Its `--baseline` directory must contain pre-migration
`data_records` and `data_preparation` suite output folders; the former publishes
C++ artifacts, while the latter checks semantic facts and recovery internally.
See the [compiler review](../../compiler/my-try/docs/planning/conversion_quality.md)
for the dated baseline/evidence paths and exact invocation.
