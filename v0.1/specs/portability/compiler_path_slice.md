# Source-path spelling portability slice
Doc Status: supporting

The source-reader now separates pure path spelling in
`compiler/src/01_prepare_inputs/read_sources/utilities/path_syntax.php` from host
selection and canonical filesystem resolution in `Source_Paths`. Existing callers
keep the same `Source_Paths` API. Its three spelling methods delegate to the new
owner; `resolve` and filesystem failure behavior remain unchanged. The bootstrap
loads the shared portability runtime and the new owner before the delegating API.
This is one local ownership refactor, not a second implementation of the rules.

`Source_Path_Syntax::is_absolute` takes an explicit Windows-policy boolean. The
host-facing method passes its existing `DIRECTORY_SEPARATOR` decision. Both policy
branches are therefore testable on the same host without emulating filesystem IO.
Existing behavior is preserved, including the original permissive drive-prefix
check; this migration does not introduce new path validation or normalization.

## Converter and runtime

The converter adds explicit public static methods with scalar parameters and
scalar returns, literal named static calls, return statements, concatenation and
`>=`. Parameters have no defaults, references or variadic forms in this slice.
Instance method bodies/calls, constructors, inheritance and call resolution remain
unsupported. Static call existence, argument types and return correctness are
checked by PHP and the native compiler/STAN, not by conversion.

The central function map adds ordinary `str_starts_with`, `str_ends_with`, `strlen`
and `scpp\string_byte_slice`. The latter is supplied by the PHP runtime and maps
to native `string_byte_slice`. Negative offsets/lengths and offsets beyond the
string return empty; excessive lengths clamp to available bytes. This operation
is explicit because native `substr` uses codepoint slicing, unlike PHP `substr`.
No ordinary `substr` mapping was added. All current portable inputs have the same
updated managed imports; no per-file library selection was introduced.

## Evidence and limitations

The cumulative compiler-component runner now stages four production files plus
its shared entrypoint. [path-03](../planning/compiler_migration/results/path-03/summary.json)
records PHP/native comparisons for joining, source suffix recognition, both host
spelling policies, short/empty strings, backslashes, non-ASCII strings, byte slicing
and all earlier components. Nine existing compiler fixtures cover compilation,
tokenization, source paths, discovery, scan tasks and source snapshots.

The production extraction preserves the former algorithms except for explicit
host-policy injection and replacing PHP byte substrings with the equivalent byte
operation. `path-01` was an initial ASCII proof; `path-02` retains a failed native
probe where the target lowered the literal `"\xA9"` to an empty string. The final
proof verifies a one-byte UTF-8 continuation slice by its byte length instead.
Hex-escaped binary literal portability is unresolved target/converter debt, not a
claim established by this slice. Native filesystem resolution, errors, Windows IO,
full source discovery and packed token storage remain unported.

Use the existing cumulative command in
[the component test README](../../tests/portability/compiler_context/README.md).

Follow-up: [string-byte consolidation](compiler_string_bytes_slice.md) maps authored
PHP `strlen` to native `string_byte_len` and rejects unsafe literal byte sequences
locally. The earlier path evidence remains a historical checkpoint.
