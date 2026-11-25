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
runtime string operations. Each flavor admits a single local or parameter name
per insertion using its existing ASCII name characters. Calls, expressions,
global names and formatting specifiers are outside this slice.

String, bool, byte/uint8, int/int64 and float/float64 insertions use the
[explicit scalar cast](compiler_scalars_v0_2.md#explicit-scalar-to-string-casts-in-v02).
Null, boxed values, carriers and collections require separate handling by the caller.
