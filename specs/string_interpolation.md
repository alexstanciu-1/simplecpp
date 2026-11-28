# String interpolation

Doc Status: normative

## Active v0.2 flavors

| Flavor | Example | Literal markers |
| --- | --- | --- |
| PHP | `"Hi $name, count={$n}"` | `\$` is a literal dollar; single quotes preserve markers. |
| JS | `` `Hi ${name}, count=${n}` `` | Backticks activate interpolation. A backslash escapes a dollar or backtick. |
| C++ | `f"Hi {name}, count={n}"` | `{{` and `}}` insert literal braces. The prefix is adjacent to the opening quote. |

Ordinary JS and C++ quoted strings keep markers as text. The C++ flavor implements
the f-literal spelling in the Simple C++ frontend. Native builds consume ordinary
runtime string operations. Each flavor admits local and parameter names using its existing ASCII name
characters. PHP also admits one single-quoted literal key on a local or
parameter inside braces, as in `"Hello {$row['name']}"`. A PHP braced insertion can also
read one declared instance field from a local or parameter, as in `"{$user->name}: {$user->score}"`.
Ordinary field visibility and receiver checks apply. The field value uses the same formatting as a local.
Calls, arbitrary expressions,
global names and formatting specifiers are outside this slice.

String, bool, byte/uint8, int/int64 and float/float64 insertions use the
[explicit scalar cast](compiler_scalars_v0_2.md#explicit-scalar-to-string-casts-in-v02).
PHP also formats published integer widths, both string builders, and class or
interface values implementing the core Stringable contract. Each insertion
captures its text before the next user formatter can modify its source.
PHP accepts `mixed` insertions using the explicit boxed-to-string cast.
For example, a boxed false formats as `false` in both a stored string and an
echoed string. Boxed null and containers raise `boxed_scalar_kind`.
Other boxed types, statically null values, carriers and collections require
separate handling by the caller.

PHP keyed insertions use the ordinary indexed read on a string-keyed typed
hash or a mixed row. A typed hash miss raises its ordinary missing-key error.
A missing key in a mixed row yields boxed null and fails during formatting.
Each insertion is evaluated once in source order. When echoing an interpolated
operand, all its insertions must succeed before the first byte of that operand
is printed. Earlier, separate echo operands retain their usual effects.
