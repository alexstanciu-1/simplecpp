# First portability conversion slice
Doc Status: supporting

This records the bounded implementation of the [concept](README.md); it does not
change PHP++ semantics. The [first real compiler slice](compiler_context_slice.md)
extends this foundation with scoped prologues and scalar-field reference classes.
The target wrapper contract is owned by
[runtime wrappers](../../runtime/specs/spec.md#result_or_falset-result_or_boolt-and-resultt).

## Local conversion

The separate PHP tool uses `token_get_all(..., TOKEN_PARSE)` for PHP syntax checking
and builds a small structural AST of files, groups, mapped calls, annotated locals
and retained tokens. PHP parsing is not symbol resolution. It does not import the
compiler prototype or infer wrapper types from assignments.

The initial input subset is script bodies: annotated locals, assignment, scalar
literals, `echo`, conditionals, ternaries, strict equality and boolean expressions.
Comments and whitespace are retained where possible. PHP tags are removed.
Function/method declarations, includes, manual imports, dynamic calls, arbitrary
calls, arrays, interpolation, arithmetic and general expression support are not
implemented and produce diagnostics. The bounded class/prologue extension is
specified in the compiler slice linked above. Source diagnostics carry input file/line;
output source maps are not implemented yet.

Local types: `int`, `uint32`, `bool`, `string`, and the `nullable<T>`,
`result_or_false<T>`, `result_or_bool<T>` forms over those scalars, except boolean
payloads in the last two wrappers. These need explicit tagged states to distinguish
payload booleans from sentinels and are rejected in this slice. An annotation such
as `$out /** int */ = 0;` becomes `$out int = 0;`.

## Explicit function map and PHP support

Every portable PHP file carries the same generated import block, managed by
`tools/php_portability/sync_imports.php`. `function_map.php` is the single policy
for PHP implementation names, target names and arities. A null PHP implementation
selects the ordinary PHP builtin, currently `is_bool`.

The current block imports `scpp\take_nullable`, `scpp\take_false` and
`scpp\take_bool`. PHP then executes natural bare calls without global aliases.
The converter requires the exact current block and removes it locally before
emission, preserving original diagnostic line positions. No semantic resolution
or arbitrary import alias lookup is performed. Explicit calls to the configured
qualified implementation remain valid. A global bypass such as `\take_false(...)`
is rejected. Other manual imports remain unsupported.

Use `sync_imports.php SOURCE_DIRECTORY --check` to detect stale/missing blocks
without changing source. Policy changes invalidate conversion and require import
synchronization. One lowercase semicolon namespace and an optional leading
strict-types declaration now have explicit placement rules. JSON and count adapters are not implemented by this slice;
a private test override proves builtin shadowing and bypass rejection mechanically.

| PHP operation | Target | PHP approximation |
| --- | --- | --- |
| `take_nullable($out, $value)` | `take($out, $value)` | null means absent; false and zero can be payloads |
| `take_false($out, $value)` | `take($out, $value)` | false means absent; zero is a payload |
| `take_bool($out, $flag, $value)` | `take($out, $flag, $value)` | booleans update flag; other supported payloads update out |

These are explicit wrapper-intent operations, not overload resolution. Authored
annotations and calls must agree; the converter does not prove that agreement.
The PHP library uses ordinary PHP values rather than allocating wrapper objects.
It is intended for the supported annotated payload types, not arbitrary mixed data.

Absent branches leave the value output unchanged. A boolean-true branch returns
true and updates only the flag; a boolean-false branch returns false and updates
only the flag. A payload branch leaves the flag unchanged. Arguments are evaluated
once by ordinary PHP calls. PHP copying/identity remains an approximation outside
the scalar slice. Structured `result<T>` / error extraction is a follow-up.

Use PHP's `auto_prepend_file` to load `runtime/bootstrap.php`; the source fixture
needs no require statement that would interfere with file-to-file conversion.

## Incremental publication

Directory input maps relative `.php` paths to `.phs` paths in a separate output
tree. A `.scpp-portability.json` manifest records source/rule hashes and output hashes.
The rule fingerprint covers the converter, import policy, driver and function map. Unchanged inputs
and verified outputs skip conversion. Changed rules reconsider files; unchanged
final bytes retain timestamps. PHP-only runtime changes do not invalidate conversion.
Missing sources remove their previously owned output; unowned files are retained.

All changed files are converted before output publication, so conversion errors
leave the previous outputs and manifest intact and return failure. Outputs must not
be consumed after a failed invocation. Individual files use atomic replacement;
whole-tree crash transactions and concurrent converter invocations are unsupported.
Manifest version/hash shapes and output paths are checked before reuse/publication.
Import synchronization refuses unexpected statements inside its managed block.
The output tree is converter-owned; do not manually maintain generated files.

## Proof and next boundary

`tests/portability/run.py` tests actual CLI conversion, reuse, edits, renames/removal,
rule invalidation, output repair, rejection and PHP wrapper states. `--native` builds
and runs the converted fixture through the current strict PHP++ toolchain and checks
both PHP and native output against the same expected result.

See [consolidation and adoption debt](debt.md) for the current capability gates.

The update-context extension now ports one real component. Further extensions
should select the next component and add only its required syntax/library operations;
the complete compiler is not portable yet.
