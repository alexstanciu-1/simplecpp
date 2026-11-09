# Builtin contract for byte slicing

Doc Status: normative

## v0.2 signatures

| Flavor | Remainder | Bounded slice |
| --- | --- | --- |
| PHP | `substr(value, start)` | `substr(value, start, length)` |
| C++ | `value.substr(start)` | `value.substr(start, length)` |
| JS | `value.slice(start)` | `value.slice(start, end)` |

The receiver or first argument is a string. Each bound is a signed int64. All forms return an owning string.

## Byte coordinates

Let N be the byte length. A nonnegative position is clamped to N. A negative position counts from N and is clamped to zero. Normalize the start this way for all three flavors. Omitting the final argument selects all remaining bytes.

- PHP and C++ take a length. A nonnegative length selects at most that many remaining bytes. A negative length sets the exclusive end relative to the end of the original string.
- JS takes an exclusive end position, normalized by the same rule as start.
- An end at or before the normalized start yields an empty string. Every int64 bound is valid, including both extremes.

For `"abcdef"`, start 2 and final argument 3 yield `"cde"` in PHP/C++ and `"c"` in JS. Embedded NUL is an ordinary byte. A slice may split a UTF-8 sequence and return bytes that independently form invalid UTF-8.

The historical `my-try` implementation used code-point coordinates. v0.2 uses the same byte coordinates as its indexing, length and search functions.
