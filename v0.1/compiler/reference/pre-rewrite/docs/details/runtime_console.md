# Runtime strings and console
Doc Status: supporting

The bounded console surface uses the existing metadata adapter, ordinary callable
pipeline, conversion selector and owned-result cleanup. No compiler or preparation
generator branches were added for strings, input, concatenation or parsing.

## Exposed contracts

The provider [definitions](../../src-runtime-preparation/definitions/strings.json)
select these ordinary named calls:

| Call | Contract |
|---|---|
| `input_line(): string` | Read one line from standard input into a new owned value. Strip LF and an immediately preceding CR. Preserve all other bytes. Accept a final nonempty line without a newline. Empty EOF and read errors terminate with a diagnostic. A blank line returns an empty string. |
| `int_from_string(string): int` | Call-scoped const borrow; strict Simple C++ decimal conversion. Require complete consumption and signed 64-bit range. Empty input, whitespace, a leading plus, fractional/trailing text and overflow fail. Leading zeroes and a minus are accepted. Registered for `explicit_cast`, independently of implicit conversions. |
| `string_from_int(int): string` | Existing explicit provider conversion, producing an owned decimal string. |
| `string_concat(string, string): string` | Borrow both operands for the call and return an independent owned result. Preserve binary contents and operand values. |
| `string_byte_length(string): int` | Borrow the value and return its byte count, checked against the language integer range. UTF-8 codepoints are not counted. |
| `echo` | Existing metadata-selected output; no automatic newline or integer conversion. |

Input is consumed at runtime in the compiler's existing left-to-right evaluation
order. The stream is process-owned; compiler worker isolation does not imply
concurrent application reads form atomic lines. This slice adds no application
threading policy, retry loop, recoverable result or exception unwinding.

## Implementation ownership

- [Provider string wrappers](../../src-runtime-preparation/include/scpp_provider/strings.hpp)
  call Simple C++'s public strict cast and string operator. Extracting `int_t`'s
  native value adapts its C++ signature to our exposed direct integer ABI.
  Byte length uses a checked cast rather than assuming `size_t` matches `int`.
- [Provider console wrapper](../../src-runtime-preparation/include/scpp_provider/console.hpp)
  implements the local line contract using buffered C stdio. The available runtime
  file API requires resource/result wrappers and does not provide this simple
  console signature. A lone CR and embedded zero bytes remain data.
- JSON selects those implementations, signatures, ownership and conversion purpose.
  `free_function` remains the shared preparation adaptation. Generated bridges
  catch provider exceptions and report the operation before terminating.
- The package adapter imports existing scalar/borrowed-address/caller-storage
  contracts. Checking, lifetime analysis, lowering and linking use their existing
  shared owners. Named concatenation is an ordinary call; no new operator or cast
  expression syntax is introduced.

The wrappers are provider code, not compiler special cases. They stay under
`src-runtime-preparation` until moved with the provider to Simple C++. Their include
directory is configured, and Clang dependency discovery fingerprints the headers.
Changing a wrapper invalidates preparation through the existing mechanism; unchanged
inputs reuse the stable package. Application builds never regenerate it.

## Proof

[runtime_console.php](../../tests/integration/runtime_console.php) checks:

- Input → strict parse → source function → arithmetic → integer formatting →
  concatenation → byte length → echo, including local copies and temporary cleanup.
- Decimal limits and malformed inputs, clear EOF/read errors, blank/final lines,
  CRLF, binary bytes, UTF-8 byte lengths, long lines and successive ordered reads.
- Full rebuild then one body replacement with fixed contract reuse, unchanged
  caller/native objects and preserved prior snapshots.
- Reversed body workers and lifecycle preparation using existing joins/private
  outputs; package reuse and dependency/export contracts.
- The composed program and its error boundary through ordinary linking, full LTO
  and ThinLTO. These remain feasibility tests, not a production LTO mode.

The earlier [two-type literal proof](runtime_string_literals.md) and
[owned-result proof](conversion_selection.md) establish the same compiler path for
other configured runtime types, including a noncopyable/nonmovable result.

See the runnable [console example](../../examples/runtime_console/README.md).
Owned source-function boundaries, structured fields, template families and nested
ownership remain separate foundations.
