# Native token/source-location proof

Doc Status: supporting

The valid-data milestone-1 proof is implemented in
[`tests/token_source_native.py`](../tests/token_source_native.py). It translates the
logical token and source-location model into all three source flavors; it does not
replace the PHP compiler's own input or tokenizer implementation.

Run with an unused output directory:

```sh
XDEBUG_MODE=off python3 -B compiler/tests/token_source_native.py --output /tmp/token-source-proof
```

The directory retains the source fixtures, emitted C++, native executables, Clang
traces and per-flavor timing JSON. No generated build output is checked in.

## Structures and algorithms

- `TokenKind`: generated from the production enum's explicit case IDs.
- `Token`: readonly offset, length and kind; no copied spelling or per-token source owner.
- `SourceLocation`: a small returned value with one-based line and byte column.
- `SourceFile`: readonly source bytes and a line-start vector, constructed once.
- `TokenList`: readonly token vector and a shared source snapshot. Returning the list
  from a factory preserves the source after the factory's local owner is destroyed.

The proof uses int64 for offsets, lengths and line starts. It tests logical behavior,
not the eventual uint32/packed storage ABI. Location results also use int64, which
can represent the production contract's possible 2^32 line/column result.

Line-index construction scans bytes once, recording the position after each LF.
The production PHP implementation uses repeated `strpos`; both produce the same
index. Lookup uses the production binary-search algorithm, O(log line count).
CR, NUL and UTF-8 continuation bytes remain ordinary bytes. EOF is a valid location.
No preparation facts, AST structures or compiler special cases were added.

For example, the PHP-flavor fixture translates the lookup result into a value:

```php
while ($low + 1 < $high) {
    $middle = ($low + $high) / 2;
    if ($this->line_starts[$middle] <= $offset) {
        $low = $middle;
    } else {
        $high = $middle;
    }
}
$result SourceLocation($low + 1, $offset - $this->line_starts[$low] + 1);
return $result;
```

This is Simple C++ source; integer division uses its integer arithmetic contract.

## Evidence and boundaries

The expected output comes from actual PHP `source_file`, `token` and `token_list`
instances. Every byte offset through EOF is compared for empty input, LF, CRLF,
lone CR, trailing LF, UTF-8, embedded NUL, a long line and many short lines.
Three-byte token spans (with a shorter final token) verify offsets, lengths, kind
IDs, ordered traversal and exact text extraction, including split UTF-8 sequences.

Each flavor runs seven resident compiler revisions: initial build, unchanged reuse,
a location-method edit, deletion, failed retry without edits, recovery, and another
unchanged run. Every revision is compared to a fresh compiler: diagnostics on failure,
byte-identical emitted artifacts on success. Native executables verify the expected
output; identical fresh artifacts are not redundantly built. Unchanged preparation
is zero bodies. Failed generations retain the previous successful artifacts.

Deferred: invalid-input exception parity, token packing/sizeof, lexer translation,
path/flavor metadata, operator-token recombination, file I/O and multi-gigabyte inputs.
Readonly syntax/ownership behavior is exercised, but this is not a complete translation
of every method on the production source/token classes.

## Recorded timings — 2026-11-19

Initial builds from `/tmp/scpp-token-source-proof-final`, milliseconds, one sample
per flavor (not a performance comparison between flavors):

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link | PHP + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| PHP | 13.41 | 329.43 | 199.88 | 248.76 | 65 | 342.83 |
| C++ | 13.73 | 290.78 | 138.09 | 199.15 | 63 | 304.51 |
| JS | 15.31 | 311.65 | 145.05 | 204.82 | 65 | 326.96 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt runtime, direct Ninja qualification.
Frontend/backend numbers sum trace work across translation units and can exceed
parallel native wall time; do not add them to native wall. PHP + native is staged
measured work, not the complete test-harness wall time. No-op compiler times were
1.92/1.87/1.98 ms, respectively, with zero prepared bodies. These small proof timings
do not benchmark tokenizer performance or a translated compiler.
