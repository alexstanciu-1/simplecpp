# UTF-8 text and explicit bytes
Doc Status: supporting

This checkpoint implements UTF-8 text semantics in the portability framework.
It does not change PHP globally or redefine the Simple C++ language builtins.
The same managed function imports apply to every portable file. PHP implementations
use mbstring with explicit `UTF-8`, independent of the ambient mbstring encoding.
The PHP runtime therefore requires the mbstring extension for text operations.

## Authoring contract

Text is valid UTF-8, and positions/lengths count Unicode code points. A combining
mark is a separate code point; an emoji sequence may contain several. There is no
normalization, grapheme segmentation, locale behavior or implicit conversion from
another encoding. Text helpers reject malformed UTF-8 with `InvalidArgumentException`
and message `Text operation requires valid UTF-8`, in PHP and the native framework.

| Authored call | PHP implementation owner | Contract |
| --- | --- | --- |
| `strlen($text)` | `scpp\compat\strlen` | Code-point count: `é中😀` has length 3, byte length 9. |
| `substr($text, $offset[, $length])` | `scpp\compat\substr` | Code-point slicing; negative offsets count from the end, negative lengths exclude trailing code points. Out-of-range/clamped empty slices return an empty string. Two or three arguments; supplied length must be an integer. |
| `strpos($text, $needle[, $offset])` | `scpp\compat\strpos` | First match's code-point position or PHP `false`; native `result_or_false<int>`. Extract using `take_false`, retaining position zero as success. |
| `strrpos($text, $needle[, $offset])` | `scpp\compat\strrpos` | Last match's code-point position or false wrapper. A nonnegative offset excludes earlier starting positions; a negative offset limits the latest starting position relative to the end. |
| `str_starts_with`, `str_ends_with` | `scpp\compat` | Exact prefix/suffix matching after validating both strings; no normalization. Empty prefix/suffix matches valid text. |
| `string_codepoint_at($text, $index)` | `scpp` | Unicode scalar value as integer; -1 for negative/out-of-range indices. Validates text even for an out-of-range index. Use `substr($text, $index, 1)` when a UTF-8 substring is wanted. |
| `string_utf8_is_valid($bytes)` | `scpp` | Boolean validation, accepting arbitrary bytes as input, without throwing for malformed data. |

Search offsets must be between minus the text's code-point length and plus that
length, inclusive. Outside that range both implementations throw `OutOfBoundsException`
with message `Text search offset is out of range`. The needle is validated too,
even when it cannot match. Empty needles are supported. No helper replaces malformed
data with a replacement character or silently treats it as a single-byte encoding.

Byte-oriented operations remain explicit and accept arbitrary bytes:

- `string_byte_len`: byte count.
- `string_byte_at`: unsigned byte integer; -1 outside the string.
- `string_byte_from_int`: one byte for an integer in 0..255, including NUL and
  invalid UTF-8 bytes. Outside that range throws `InvalidArgumentException` with
  `Byte value must be between 0 and 255`. PHP uses checked `chr`; native framework
  uses two hexadecimal digits and the existing `hex2bin` wrapper. This is binary
  construction, not a code-point encoder. All 256 values are proved by the
  [quoted-byte decoder slice](compiler_byte_literals_slice.md).
- `string_byte_slice`: nonnegative byte offset/length; invalid ranges give empty,
  overlong lengths clamp.
- `string_byte_starts_with`, `string_byte_ends_with`: raw byte prefix/suffix matching.

Source buffers, lexical offsets, source spans, raw filesystem path spelling and
numeric decimal spelling use these byte operations. The already-portable production
files and cumulative witnesses have been audited and updated accordingly. The
unmigrated compiler keeps its ordinary PHP behavior until individually adapted;
the framework does not globally override PHP builtins.

Do not use `$text[$index]` for portable character access. PHP indexes bytes and the
converter does not infer receiver types or rewrite brackets into character calls.
Container indexing remains supported; distinguishing string indexing is an authoring
obligation. Global `\strlen`, `\substr`, etc. cannot bypass managed text bindings in
portable source. Explicit framework-qualified calls remain permitted.

```php
$characters /** int */ = strlen("é中😀");       // 3
$bytes /** int */ = string_byte_len("é中😀");  // 9
$position /** int */ = -1;
if (take_false($position, strpos("é中😀", "😀"))) {
    echo $position;                            // 2
}
```

## Conversion and runtime ownership

`function_map.php` selects the PHP implementation, accepted arity and native
operation. Accepted arities may now be an explicit list; `substr` selects its
two- or three-argument native wrapper using only the local argument count. The
converter does not determine whether a string is text from its contents or uses.

`runtime/strings.php` owns the PHP implementations. `runtime/strings.phs` owns the
native framework counterparts. The latter validate bytes explicitly before invoking
native code-point operations; v0.1.76 otherwise falls back to bytes for some invalid
UTF-8 operations. The validator rejects overlong encodings, isolated continuation
bytes, truncated sequences, surrogate encodings and values above U+10FFFF.

Native text helpers require framework assembly:

```bash
php tools/php_portability/convert.php SOURCE OUTPUT
php tools/php_portability/install_native_runtime.php OUTPUT
```

Assembly owns separate `scpp_framework/exceptions.phs` and `strings.phs` artifacts.
Its version-2 ownership manifest lists per-file hashes; it can adopt its prior
version-1 exception-only state. All artifact collisions/edits/symlinks are checked
before publication. Source conversion ownership and one-to-one files remain separate.
Native framework implementation edits require assembly again, not source conversion.
Unchanged support files retain timestamps. Per-file atomic replacement and the
existing single-writer limitation still apply.

This remains an incremental library: other PHP string builtins are not implicitly
enabled, and no string indexing/type analysis, grapheme API, Unicode case mapping,
native source-literal repair or byte-search API is added here. Existing literal
admission still rejects embedded NUL and malformed UTF-8 literals because of selected
target generation limitations. Those literal restrictions do not redefine runtime
byte strings or the validity of U+0000 in UTF-8.

## Evidence

[UTF-8 proof](../planning/compiler_migration/results/utf8-01/summary.json) compares
308 output assertions against independently constructed Python expectations, PHP
execution and strict native v0.1.76 execution. It covers all four UTF-8 widths,
combining marks, an emoji sequence, U+10FFFF, empty values, signed/clamped slicing,
search defaults/offsets/missing/empty needles, byte preservation and handled invalid
input. Runtime malformed values are constructed through byte slicing rather than
silently relaxing source-literal rejection. The runner, inputs, outputs and actual
native support are preserved beside the logs.

The [cumulative compiler PHP/native proof](../planning/compiler_migration/results/utf8-cumulative-01/summary.json) and sixteen compiler fixtures
pass; the independent decimal oracle still passes 24,728 cases. Framework assembly
tests additionally cover both artifacts, protection against edits/symlinks, no-op
reuse and migration from its exception-only manifest.

`json_quote($text)` now provides shared exact JSON scalar-string encoding. It accepts
valid UTF-8 including NUL, returns a complete quoted/escaped string, and throws
JsonException (code 5) on malformed UTF-8. This preserves the JSON-specific exception
contract rather than using the ordinary text-helper InvalidArgumentException. See
[manifest snapshot export](compiler_manifest_record_slice.md) for native evidence.
