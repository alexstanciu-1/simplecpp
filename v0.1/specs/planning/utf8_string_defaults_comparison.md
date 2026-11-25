# String defaults: bytes, UTF-8, and Unicode operations

Doc Status: planning

Research date: 2026-10-21. Scope: C++, Rust, JavaScript, Python 3, PHP 8, and Java; current Simple C++ contracts and selected implementation anchors.

Purpose: provide evidence for choosing default string semantics and investigating reported replacement/comparison slowness. This document does not change language contracts. Non-goals: implementing optimizations, selecting a Unicode library, or claiming a measured cross-language speed ranking.

## 1. Decision summary

**UTF-8 storage and character-based string functions are separate decisions.** Rust demonstrates that UTF-8 text can use byte lengths and byte search offsets. JavaScript and Java expose UTF-16 code units; Python exposes code points; PHP and C++ narrow strings expose bytes. None of these ordinary string models makes every operation work on user-perceived characters.

The initial recommendation is to retain fast exact byte operations, make position units explicit and composable, and provide explicit code-point/grapheme operations. Whether *all strings must be valid UTF-8* remains a separate design decision. We need local timings before deciding whether the reported slowness requires an algorithm fix, fewer copies, fewer scans, or changed semantics.

## 2. What “no UTF8” and “UTF8” mean here

| Layer | Meaning | Example consequence |
| --- | --- | --- |
| Bytes / no decoding | An operation treats storage as bytes; UTF-8 text can still be stored and searched | A byte substring can cut a character in half |
| Code units | Elements of an encoding: 8 bits in UTF-8, 16 bits in UTF-16 | A supplementary character occupies two UTF-16 positions |
| Unicode code points | Encoded values; scalar values exclude surrogate code points | An accent can be a separate code point |
| Grapheme clusters | Segmented user-perceived characters | An emoji sequence can contain several code points |
| Linguistic text | Normalization, case folding, locale-sensitive collation | Alphabetical order is not numeric code-point order |

“Multibyte” alone does not specify which layer an API uses. Unicode support also does not imply UTF-8 internal storage. Grapheme boundaries are defined by segmentation rules, not simply by decoding UTF-8. [Unicode text segmentation](https://www.unicode.org/reports/tr29/)

Concrete counts, excluding terminators and object overhead:

| Text | UTF-8 bytes | UTF-16 units | Code points | Extended grapheme clusters |
| --- | ---: | ---: | ---: | ---: |
| `A` | 1 | 1 | 1 | 1 |
| `é` (U+00E9) | 2 | 1 | 1 | 1 |
| `e` + U+0301 | 3 | 2 | 2 | 1 |
| `中` | 3 | 1 | 1 | 1 |
| `😀` | 4 | 2 | 1 | 1 |

These are derived encoding counts, not timing results. Canonically equivalent spellings such as the two accented examples need not compare equal in ordinary exact comparisons. [Python Unicode HOWTO](https://docs.python.org/3/howto/unicode.html)

## 3. Default representations

| Language | Ordinary string model | UTF-8 default? | Unicode default? | Byte-oriented alternative |
| --- | --- | --- | --- | --- |
| C++ | `std::string`: `char` sequence, no encoding invariant | No | No | Ordinary narrow string already serves this role |
| Rust | `String` / `str`: valid UTF-8 | **Yes** | Yes, but positions are generally bytes | `Vec<u8>` / `[u8]` |
| JavaScript | String: UTF-16 code-unit sequence | No | Yes; isolated surrogates are possible | `Uint8Array`; explicit text encoding/decoding |
| Python 3 | `str`: Unicode code-point sequence | No, not the abstract representation | **Yes**, code-point indexing | `bytes` / `bytearray` |
| PHP 8 | `string`: byte sequence | No | No in ordinary string functions | Ordinary string already serves this role |
| Java | `String`: UTF-16 semantics | No | Yes; code-unit indexing | `byte[]`; explicit charset conversion |

Sources: [C++ string specification](https://eel.is/c++draft/string.classes), [Rust String](https://doc.rust-lang.org/std/string/struct.String.html), [ECMAScript strings](https://tc39.es/ecma262/multipage/text-processing.html), [Python text/binary types](https://docs.python.org/3/library/stdtypes.html), [PHP strings](https://www.php.net/manual/en/language.types.string.php), [Java String](https://docs.oracle.com/en/java/javase/25/docs/api/java.base/java/lang/String.html).

C++ `std::u8string` explicitly uses UTF-8 code units but does not validate contents or make indexing code-point based. Python's abstract model is not a promise that every value can be encoded as strict UTF-8: surrogate code points can exist. Rust's safe string APIs preserve its stronger validity invariant. [C++ strings](https://eel.is/c++draft/string.classes), [Python Unicode handling](https://docs.python.org/3/howto/unicode.html), [Rust String](https://doc.rust-lang.org/std/string/struct.String.html)

## 4. Ordinary functions: what each language actually counts

All search/replacement entries below mean **literal, case-sensitive** matching. Regex and locale-aware matching are separate contracts.

| Language | Length | Index / substring | Find | Replace literal occurrences | Exact equality / ordering |
| --- | --- | --- | --- | --- | --- |
| C++ | `.size()`: code units, bytes for `std::string` | `[]`, `.substr()`: units | `.find()`: unit offset | `.replace()` replaces a positional range; replace-all requires a search/build loop | `==`, `.compare()`: units |
| Rust | `.len()`: bytes | Byte range slicing requires UTF-8 boundaries; no integer string indexing | `.find()`: byte offset | `.replace()`: all matches | `==`, `.cmp()`: exact, lexicographic |
| JS | `.length`: UTF-16 units | `[]`, `.slice()`: units | `.indexOf()`: unit offset | `.replace()`: first literal match; `.replaceAll()`: all | `===`, `<`: exact / unit order |
| Python | `len(str)`: code points | `s[i]`, `s[a:b]`: code points | `.find()`: code-point offset | `.replace()`: all unless count supplied | `==`, `<`: exact / code-point order |
| PHP | `strlen()`: bytes | `$s[$i]`, `substr()`: bytes | `strpos()`: byte offset | `str_replace()`: all | `===`: exact for strings; `strcmp()`: byte order |
| Java | `.length()`: UTF-16 units | `.charAt()`, `.substring()`: units | `.indexOf()`: unit offset | `.replace(CharSequence, CharSequence)`: all | `.equals()`, `.compareTo()`: exact / unit order |

Sources: [C++ string operations](https://eel.is/c++draft/string.classes), [Rust str methods](https://doc.rust-lang.org/std/primitive.str.html), [ECMAScript string methods](https://tc39.es/ecma262/multipage/text-processing.html), [Python methods](https://docs.python.org/3/library/stdtypes.html), [PHP string representation](https://www.php.net/manual/en/language.types.string.php), [PHP replacement](https://www.php.net/manual/en/function.str-replace.php), [PHP comparison](https://www.php.net/manual/en/function.strcmp.php), [Java methods](https://docs.oracle.com/en/java/javase/25/docs/api/java.base/java/lang/String.html).

Do not benchmark Java `==` as content equality: it compares references. Java `replaceAll` takes a regex. JS replacement strings interpret substitution markers such as `$&`; use a replacement callback when benchmarking arbitrary literal replacement bytes/text. PHP loose comparisons can perform numeric-string comparison; use `strcmp` for lexicographic order. [Java String](https://docs.oracle.com/en/java/javase/25/docs/api/java.base/java/lang/String.html), [ECMAScript replacement](https://tc39.es/ecma262/multipage/text-processing.html), [PHP strcmp](https://www.php.net/manual/en/function.strcmp.php)

### Explicit Unicode operations and their limits

| Language | Code-point operations | Case / whitespace | Graphemes / linguistic operations |
| --- | --- | --- | --- |
| C++ | Decode explicitly or use a library; narrow `substr` is not enough | Byte `tolower` is not UTF-8 case mapping | ICU supplies Unicode processing |
| Rust | `.chars()`, `.char_indices()`; count iteration for code-point length | `.to_lowercase()`, `.to_uppercase()`, `.trim()` use Unicode; explicit ASCII variants exist | Dedicated library needed for segmentation/normalization/collation beyond these standard methods |
| JS | `for…of` iterates code points; `.codePointAt()` still accepts a unit offset | Unicode casing and `.trim()` | `Intl.Segmenter`, `Intl.Collator`; `.normalize()` |
| Python | Ordinary `str` indexing already uses code points | `.lower()`, `.upper()`, `.casefold()`, `.strip()` | `unicodedata.normalize`; grapheme segmentation needs additional support |
| PHP | `mb_strlen`, `mb_substr`, `mb_strpos` with explicit `"UTF-8"` | `mb_strtolower`, `mb_strtoupper`, `mb_convert_case` | `intl`: grapheme functions, `Normalizer`, `Collator` |
| Java | `.codePointCount()`, `.codePointAt()`, `.offsetByCodePoints()` | Unicode case methods; `.strip()` differs from historical `.trim()` | `Collator` for linguistic order |

Sources: [ICU UTF-8 APIs](https://unicode-org.github.io/icu/userguide/strings/utf-8.html), [Rust str](https://doc.rust-lang.org/std/primitive.str.html), [ECMAScript internationalization](https://tc39.es/ecma402/), [Python types](https://docs.python.org/3/library/stdtypes.html), [Python normalization guidance](https://docs.python.org/3/howto/unicode.html), [PHP mbstring](https://www.php.net/manual/en/ref.mbstring.php), [PHP intl](https://www.php.net/manual/en/book.intl.php), [Java String](https://docs.oracle.com/en/java/javase/25/docs/api/java.base/java/lang/String.html), [Java Collator](https://docs.oracle.com/en/java/javase/25/docs/api/java.base/java/text/Collator.html).

PHP's ordinary `strtolower` is ASCII-only in PHP 8.2 onward. `mb_strlen(..., "UTF-8")` counts encoded characters rather than grapheme clusters. There is no ordinary `mb_str_replace` counterpart required for exact UTF-8 literal replacement: byte replacement can already do that job under the validity conditions below. [PHP lowercase](https://www.php.net/manual/en/function.strtolower.php), [mb_strlen](https://www.php.net/manual/en/function.mb-strlen.php), [mbstring function catalog](https://www.php.net/manual/en/ref.mbstring.php)

## 5. Where UTF-8 costs work, and where it does not

The following are algorithmic assessments, **not measured speedups**. Let B be input bytes, M needle bytes, K copied output bytes, and P the inspected common prefix. Assume stored byte length and flat storage; caches, views, SIMD, and engine representations can change constants or avoid work.

| Operation | Bytes / no decoding | UTF-8 / Unicode-aware requirement | Performance implication |
| --- | --- | --- | --- |
| Length | O(1) stored byte length | Uncached code-point count scans O(B) | Potentially substantial extra work on repeated calls |
| Random index | O(1) byte access | Locating code point i generally scans the prefix without an index | Repeated indexing can become quadratic; use iteration or an index |
| Substring | Locate byte endpoints O(1); copy O(K) | Locate character endpoints first; then copy | A view removes copying, not endpoint discovery |
| Exact equality | Compare length, then bytes as needed | Same encoded sequence can be compared directly | No Unicode decoding inherently required |
| Exact order | O(P), unsigned byte lexicographic order | Valid UTF-8 preserves scalar-value order | No decode needed for scalar order; collation is different |
| Literal contains/find | Depends on search algorithm; naive worst case O(BM) | Can search valid UTF-8 bytes directly | Converting a result to code-point offset adds a prefix scan |
| Literal replace-all | Search cost plus output construction | Valid complete UTF-8 needles/replacements need no decoding | Allocation, search algorithm, and output size may dominate |
| Prefix / suffix / literal split | Direct comparisons/search | Whole valid UTF-8 delimiters work directly | No general requirement to decode |
| Case-insensitive comparison | ASCII fold can be cheap | Unicode case folding may expand text | Specify fold rules; lowercase is not a universal substitute |
| Trim | Byte set membership | Unicode whitespace requires decoding/classification | Different recognized sets; not equivalent work |
| Reverse / truncate / pad | Operate on byte positions/counts | Code points require boundaries; graphemes require segmentation | Code-point reversal can still break combining sequences |
| Normalization / collation | Not provided by byte equality | Unicode data and explicit policies | Separate operations with separate budgets |

Why direct matching works: a nonempty valid UTF-8 needle cannot begin on a continuation byte of a valid UTF-8 subject. Replacing complete matches with valid UTF-8 preserves validity. Equality of valid UTF-8 bytes is equality of scalar sequences; unsigned byte ordering preserves scalar order. These are consequences of UTF-8 encoding structure, not guarantees about canonical equivalence or locale order. Invalid inputs, partial-character needles, empty needles, and arbitrary byte offsets need their own contracts. [RFC 3629](https://www.rfc-editor.org/rfc/rfc3629)

For example, replacing `é` with `E` in valid UTF-8 needs exact matching, while matching both U+00E9 and `e` + U+0301 requires an additional equivalence policy. Empty-needle insertion must specify byte, code-point, or grapheme boundaries; the nonempty-needle argument does not apply.

Representation also affects performance. CPython uses a per-string 1/2/4-byte code-point representation, allowing direct code-point indexing and stored character length. It pays for decoding and potentially wider storage instead of rescanning UTF-8 for each index. This is an implementation fact, not a universal Python complexity guarantee. [PEP 393](https://peps.python.org/pep-0393/)

A byte array is not automatically faster than a language's optimized text type. Likewise, ASCII optimizations do not remove the need to define non-ASCII behavior. Comparing different languages' wall times mixes interpreter/JIT overhead, allocation, representation, and semantics.

## 6. Current Simple C++ baseline

Read the owning contracts before interpreting old bindings or timings:

| Function | Current normative meaning | Relevant distinction |
| --- | --- | --- |
| [`strlen`](../builtins/string/strlen.md) | Bytes | v0.2 uses `byte_length`; older strict S2S code-point binding is a documented inconsistency |
| [`strpos`](../builtins/string/strpos.md), [`strrpos`](../builtins/string/strrpos.md) | Byte positions and offsets | Historical adapters retain code-point behavior |
| [`substr`](../builtins/string/substr.md) | Code-point slicing policy | Does not share `strpos` coordinates |
| [`str_replace`](../builtins/string/str_replace.md) | Binary-safe replace-all | Empty needle is a no-op |
| [`substr_compare`](../builtins/string/substr_compare.md) | Byte window; optional ASCII case folding | Not linguistic collation |
| [`substr_replace`](../builtins/string/substr_replace.md) | Byte positions | Different coordinates from `substr` |
| [`strtolower`](../builtins/string/strtolower.md) | Byte/ASCII-style conversion | Not full Unicode folding |

Composition hazard: in UTF-8 `"éX"`, byte search finds `X` at 2; feeding 2 into a code-point substring starts after `X`. This follows from the two contracts, independently of performance. A default decision needs coherent search/slice coordinates or visibly distinct APIs.

Selected implementation evidence, not a full performance audit:

- [`string_t`](../../runtime/include/scpp/string_t.hpp) wraps `std::string`; the inspected constructors do not validate UTF-8. `size()` reads byte size, while `length_cp()` delegates to the UTF-8 helper. This is not currently a Rust-style validated-text invariant.
- [`str::replace`](../../runtime/include/modules/strings/strings.hpp) searches native bytes and appends into an output string. It does not decode UTF-8. Output growth and copying are investigation candidates, not established bottlenecks.
- `str::substr_compare` in the same file creates byte `string_view` windows and calls sensitive/ASCII-insensitive comparison helpers. It does not perform character-position conversion.
- `str::find` converts a found byte position to a code-point index; `str::byte_find` does not. Offset-taking character search also converts its input position. Which binding a workload reaches matters.

The reported “compare” workload has not yet been identified: equality, ordering, substring comparison, and case-insensitive comparison must be measured separately. No root cause or local slowdown ratio is established by this documentation pass.

## 7. Options for the default

| Option | Benefits | Costs / unresolved requirements |
| --- | --- | --- |
| Byte string defaults plus explicit Unicode APIs | Binary compatibility; cheap offsets; direct exact operations | Callers must request character-safe slicing; reconcile existing `substr` contract |
| Validated UTF-8 text plus a byte type | Enforced text validity; efficient exact operations; byte offsets can remain the default | Requires a real invariant owner, ingestion validation, mutation rules, and binary-data migration |
| Code-point positions by default over UTF-8 storage | Convenient character coordinates | Scans or indexes/caches; still not grapheme semantics; binary input policy required |
| Grapheme positions by default | Better fit for cursor movement and display truncation | Segmentation/version policy and traversal cost; unsuitable as the sole binary/compiler string model |

Provisional direction: byte coordinates for low-level/default operations, explicit code-point and grapheme APIs, and separate normalization/case-folding/collation operations. Keep exact replacement and exact comparison independent of decoding. Do not add a global UTF-8 flag that silently changes offsets returned by the same function.

This is a proposal, not approval to change `substr` or historical compiler text helpers. Any future migration must reconcile the normative builtin contracts, runtime helper ownership, and each source binding. A validated text type would be a broader design slice, not a local performance patch.

## 8. Measurements needed before deciding

First measure our runtime and actual source bindings against semantically equivalent native baselines. A six-language speed contest would not isolate the cause.

| Axis | Required cases |
| --- | --- |
| Data | ASCII source text; Romanian accents; CJK; emoji; combining sequences; embedded NUL; malformed UTF-8 for binary APIs |
| Sizes | Short strings (16–64 bytes), 1 KiB, 64 KiB, 1 MiB; record both bytes and code points |
| Search / replace | Missing, early, late, dense matches; short/long needles; adversarial repeated prefixes; shrinking/equal/growing output |
| Compare | Equal; unequal length; early/late mismatch; long shared prefix; ASCII fold separately |
| Position work | Byte offsets versus character offsets; repeated indexing versus one-pass iteration |
| Validation | Already-valid text operations versus ingest-and-validate; cold versus reused metadata |
| Memory | Allocations, allocated/copied bytes, output size, and retained memory where relevant |

Record build mode, compiler/library/runtime versions, CPU, repetitions, warmup, median and spread. Consume outputs to prevent dead-code elimination; keep corpus generation and printing outside the timed loop. Measure API-call overhead separately on tiny inputs. Report ns/op for short inputs and bytes/s for scans, with semantic units alongside them.

Before timing, verify outputs, offsets, mutation behavior, malformed-input policy, overlap rules, and empty-needle behavior. Measure native helpers separately from PHS calls to detect binding/conversion costs. Benchmark byte and Unicode modes with the same result requirement; explicitly label tests that perform different work.

Evidence status: primary-source semantic comparison and selected local inspection complete. A subsequent [seven-language benchmark](results/string_defaults_2026_10_21/README.md) measures literal replacement and case-sensitive comparison through `compiler/my-try/`, with direct runtime and native controls. Its bounded corpus does not cover every axis above. Accept a semantic change only for a clear usability/contract benefit. Accept an optimization only with equivalent outputs and an explained measured improvement.
