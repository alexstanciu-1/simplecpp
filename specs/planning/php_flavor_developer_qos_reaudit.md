# PHP developer experience: replay of the 33 original tasks

Doc Status: planning

The [original field report](php_flavor_developer_qos.md) describes the experience at `356b6ff15`. The first full replay used `4e035377d`. On November 27 we reran the full study on dev002 with the PHP onboarding changes, then repeated it on the merged compiler at `da940c179`. The unit of assessment is the developer's task. **Fixed** means the original blocking operation now works under its intended v0.2 contract. **Still reproducible** means at least one concrete usability issue remains for discussion, including misleading diagnostics. **Intentional difference** means the tested behavior follows a language or library boundary we can explain to a PHP developer. A reproducible inconvenience is evidence for discussion, not a commitment to implement PHP compatibility.

## November 28 refresh on the pushed Stringable merge

At `87ca872a9`, we replayed all 250 isolated PHP snippets on dev002. There were 143 expected outputs, 93 compiler rejections and 14 runtime failures after reconciling two stale expectations. The published PHP byte helpers now produce `chr(65) === "A"` and `ord("A") === 65`; both corrected probes passed on rerun. The Stringable native and incremental checks passed, as did both field/order interpolation tests and the form-preview and catalog-view applications. The remaining study drivers retain their earlier dated evidence. [Snippet runner](../../samples/php-developer-qos/snippets.py) · [Stringable checks](../../compiler/tests/stringable_native.py)

## November 27 full-replay evidence

On dev002, all ten [study drivers](../../samples/php-developer-qos/) completed successfully with PHP 8.5.12 and Clang 18.1.3. All 250 isolated snippets matched their recorded observations. Of these, 129 ran and printed the expected output, 107 reported the expected compiler error, and 14 raised the expected runtime failure. An expected rejection can still be an onboarding issue. The default compiler and runtime suite passed all 618 tests before integration and all 629 on the merged revision.

| Replay | Observed on dev002 |
|---|---|
| Applications and input corpus | All 11 applications passed, including Apache/FastCGI, MariaDB, the curl client and the contended lock. The separate corpus checked 10,000 invoices, 80 form bodies and 276 settings requests. |
| Carriers and storage | The presence driver and all 32 paired comparison tables completed. Of 117 storage experiments, 84 produced output, 29 were rejected at build time and four failed a runtime check. For example, `?int` worked for a local or parameter and was rejected as an instance field. |
| Wire and PHP comparisons | Boxed decoding rejected a duplicate decoded name, and the document parser reported `duplicate_key@7`. All 60 byte-position comparisons agreed with stock PHP. Numeric-looking strings still differed on one equality comparison and three ordering comparisons. |
| Multi-file projects and edits | An included test script was diagnosed at its top-level statement. Explicit source selection and nested module boundaries worked. All five ordinary same-second edits produced the intended executable. An invalid atomic save was rejected, and repair restored the build. |

## November 27 onboarding follow-up

The complete first script can be `echo "hello";`. Source discovery brings project functions into scope. Copied PHP file-loading statements now point to the project manifest. An opening tag is accepted during migration and produces one compiler warning across tagged files.

**Calling functions and handling failures.** Unpacking is optional. For example, `$p = strpos("abc", "a"); if ($p !== false) { $n int = $p; echo $n; }` printed zero. Changing the needle to a missing byte skipped the body. Passing a successful `json_encode(10)` result directly to `strlen` printed 2. A typed consumer checks for a payload and throws on a missing one. Use a guard when the caller can recover, forward a compatible result when the caller's caller handles failure, or use `take` when the payload and diagnostic are needed separately.

## Follow-up to the scalar and collection probes

PHP echo now prints native scalar values, including a numeric byte. A direct
boxed read still needs explicit extraction and its diagnostic names the type
(task 6). The six published kind predicates accept both boxed families and
native scalar and collection operands (10). Single-input map, key-preserving
filter and typed membership are available by their PHP names (15). Constructor
promotion now points to explicit field declaration and constructor assignment
(24). Executable-relative source paths now cover task 27 on Linux. On dev002 all 634 default
tests passed, and ten targeted onboarding snippets matched their expectations.
Filtering `[0,1,2]` retained keys 1 and 2, indexing `"ABC"` and echoing the
byte printed `65`, and the promotion diagnostic pointed to the field declaration.
The full-replay tally below belongs to its recorded revision.

### Nullable scalar fields follow-up

The settings-model probe now prints 10 from a `?int` instance field. PHP
`?int`, `?float` and `?bool` fields use the same published nullable types as
parameters. Zero and false survive field writes as present values; assigning
null restores absence. [Native field and edit checks](../../compiler/tests/nullable_scalar_fields_native.py)

### Quoted-key template follow-up

Task 22 now prints `Hello Ada` directly from `echo "Hello {$row['name']}";`
with a mixed row. A typed string-keyed hash supports the same spelling.
A failed lookup or formatting check prints no part of that interpolated
operand. [Interpolation checks](../../compiler/tests/interpolation.php)

## Current task verdicts

| # | Original developer task | Verdict | Current evidence and scope |
|---|---|---|---|
| 1 | Detect a failed search or read | Fixed | Strict false guards distinguished byte position zero, a search miss and a missing file. Two missed searches and two absent nullable ints also compared without extracting an absent payload. |
| 2 | Test emptiness | Fixed | The string matrix passed for native and boxed text. The contract uses byte length, so the string `"0"` is nonempty. Boxed numbers and null still raise `invalid_empty_operand`. |
| 3 | Default a missing value | Intentional difference | Nullable coalescing preserved zero and `hash_find` supplied the fallback on a miss. Direct hash reads and falseable search results still reject `??`. Presence, a false sentinel and key lookup have distinct contracts. |
| 4 | Ask whether a key exists | Fixed | `isset($h[$k])` now compiles for typed hashes, including a stored zero. The nullable-value probe separately exercises absence. Boxed `has` can distinguish an explicit null from an absent key. |
| 5 | Read a form integer | Fixed | `(int)"42"` printed 42. A function casting its string parameter printed 1234 for decimal input and raised `invalid_decimal_text` for bad text. A mixed slot holding text still fails direct integer extraction with `boxed_scalar_kind`; extract its string first. |
| 6 | Print a value | Fixed | Scalar echo uses PHP output spelling. Classes that implement Stringable and both text builders work with echo, interpolation and explicit string casts. A direct mixed read still requires extraction, and the diagnostic names mixed. |
| 7 | Handle a bad request | Intentional difference | Invalid JSON returned a checked failure. A user exception exposed `get_message()`; a range guard ran finally and exited. The filesystem result forwarded its failure, but the PHP-style attempt `$r->error()` still has no such method. |
| 8 | Read a nested JSON property | Fixed | Both `$v->user->name` and `$v["user"]["name"]` printed Ana directly. |
| 9 | Copy a row and change it | Fixed | The nested indexed write printed Jo. The explicit copy/edit/write-back control preserved Ana in the original. Mixed copies and shared dynamic aliases retain their intended distinct behavior. |
| 10 | Validate an unknown value | Fixed | The published kind predicates accept mixed, dynamic and native inputs. A decoded dynamic boolean passed `is_bool`, and `is_int(1)` also succeeded. |
| 11 | Round-trip a JSON API response | Intentional difference | The boxed table encoded an empty object as `[]`; document parsing retained object identity. The wire corpus exercised numeric-looking keys, exact large-number tokens and duplicate names. Use documents for wire fidelity. |
| 12 | Distinguish malformed bytes from an invalid schema | Intentional difference | Boxed decoding accepted raw invalid UTF-8, while the document parser reported `utf8@1`. The settings endpoint distinguished malformed input (400), valid input with the wrong shape (422) and valid false (200). |
| 13 | Pass a checked or boxed value to a function | Fixed | Source functions, methods and builtins share by-value argument admission. The document-root chain printed `array` for a valid parse and raised `carrier_no_value` before the next statement for a failed parse. Both observations reproduced in the full replay. |
| 14 | Move between domain objects and JSON | Intentional difference | Direct encode of the tested Product class and typed vector rejected. Explicit field projection encoded `{"id":7}`. This verdict applies to those inputs and does not generalize to every record API. |
| 15 | Use familiar collection operations | Fixed | Single-input PHP array_map, key-preserving array_filter and strict in_array work on the tested typed inputs. The broad PHP array parameter remains a separate type-system difference. Further collection work awaits standardization. |
| 16 | Update or remove elements | Intentional difference | An indexed vector element could not bind the writable argument. Passing a named vector by reference updated its caller. Removing an index with the vector method preserved dense positions. |
| 17 | Keep a loop variable or reference | Intentional difference | A newly introduced element stayed loop-local, a predeclared variable survived empty traversal, and writable foreach left no alias to the last element. Structural mutation during that traversal rejected. |
| 18 | Start a script | Fixed | An echo-only entry printed hello and exited zero. An opening tag works with one deprecation warning per compiler execution. The strict-types diagnostic explains how to fix the file. PHP CLI globals remain a separate flavor decision. |
| 19 | Choose an inferred type | Fixed | The compact typed declaration `$v vector<int>=[];` builds. Bare null still requires a type. |
| 20 | Select defaults and branches | Intentional difference | The shorthand ternary now works. With `$label="0"`, `$label ?: "anon"` printed anon. The full ternary with incompatible arms still rejected, and the match with a default returned 405. |
| 21 | Compare or divide request values | Intentional difference | Integer division produced 3 and floating division 3.5. Text comparisons remained lexical. The PHP differential found one equality and three ordering differences on numeric-looking strings; all 60 byte-position comparisons agreed. |
| 22 | Render a template | Fixed | A template can interpolate a local, a declared field, a quoted key on a mixed row or typed hash, and an opted-in Stringable value. A throwing formatter prints no prefix from its interpolated operand. |
| 23 | Call a function | Intentional difference | Typed named parameters, literal defaults, variadics and vector unpack at the call worked. Untyped named parameters, expression defaults, list spread and assignment destructuring rejected. |
| 24 | Declare a model | Fixed | Optional reads and nullable scalar instance fields work. Constructor promotion remains an intentional syntax difference. Its diagnostic now directs the author to declare the field in the class body. |
| 25 | Dispatch through a type | Intentional difference | The missing-override diagnostic named the fix. PHP enum member lookup rejected; the published enum value/lookup helpers worked. A hint to those helpers would aid discovery. |
| 26 | Traverse a producer | Intentional difference | A typed iterator resumed after break and the keyed producer yielded its keys. PHP's Generator name and yield delegation remain outside the tested slice. |
| 27 | Organize multiple files | Fixed | Include/require diagnostics identify manifest source selection. On Linux, `__FILE__` and `__DIR__` resolve relative to the executable; the source-file/parent-directory probe succeeded. |
| 28 | Read HTTP input and send status | Intentional difference | Superglobals, `php://input` and PHP header/session helpers did not supply request state. The explicit FastCGI exchange handled the request, headers and status in the live apps. |
| 29 | Build or decode a URL, HTML or CSV | Still reproducible | Form/query encoding and decoding, HTML escaping and CSV helpers remain unavailable under the probed PHP names. The form app still needed its own component decoder. URL components, HTML text and CSV fields require separate contracts. |
| 30 | Use timestamps or a regex | Still reproducible | PHP `time`, `date` and `strtotime` were unknown with datetime enabled; the general UTC formatter worked. The old mixed-captures workaround is obsolete for numbered strings: a fresh `vector<string>` capture probe printed `2:a`. Miss and invalid-pattern controls still distinguished present zero from failure. |
| 31 | Call a remote endpoint | Still reproducible | The compiled curl client handled HTTP errors, invalid upstream data and connection failure. `CURLINFO_HTTP_CODE` still rejected while the published response-code spelling worked. This is a narrow naming issue; the exercise gives no reason to map every curl selector. |
| 32 | Prepare and bind a SQL query | Fixed | The ticket report guards the prepare result, extracts the statement and binds a named slot. On dev002 it handled a failed prepare and read each changed state on subsequent MariaDB executions. The temporary-slot and unproven-alias probes still reject. |
| 33 | Bring a filesystem snippet | Intentional difference | The two-argument PHP write alias rejected the append-flag call. The journal used append mode and a checked byte count; contention left the file untouched. POSIX lexical path behavior and general-API visibility remained as specified. |

## Remaining work after Stringable

The current classification of the original 33 tasks is **16 fixed, 3 still reproducible and 14 intentional differences**. The November 27 full replay counted 10, 9 and 14 respectively. The six changes are tasks 6, 10, 15, 22, 24 and 27. A fixed task can still have a deliberate boundary, such as explicit extraction of a boxed value before echo.

| Priority | Task | Evidence and proposed scope |
|---|---|---|
| 1 | 29 · Web inputs and output | Audit the four scalar URL component helpers together: `urlencode`, `urldecode`, `rawurlencode` and `rawurldecode`. Each takes and returns one string. The [form preview decoder](../../samples/php-developer-qos/form-preview/component.php) treats malformed percent escapes as errors; stock PHP preserves them. Compare both policies before changing that sample. |
| 2 | 29 · HTML | The [catalog view](../../samples/php-developer-qos/catalog-view/view.php) escapes text nodes with three replacements. Audit `htmlspecialchars` separately, including quotes, flags, encoding, invalid bytes and repeated escaping. PHP query structures and CSV field lists go with the later collection review. |
| 3 | 30 · Dates | Start with the zero-argument `time()` spelling against the existing Unix-seconds operation `dt_now`. The timezone, formatting vocabulary and parser required by `date` and `strtotime` deserve their own decision. Native regex capture output already addresses the regex part of this task. |
| 4 | 31 · curl | Consider the single `CURLINFO_HTTP_CODE` synonym for `CURLINFO_RESPONSE_CODE`. The HTTP client already reads the status through the published selector. |

For the component audit, compare space, literal plus, tilde, embedded NUL, non-ASCII bytes and incomplete or malformed percent escapes against PHP. For example, stock PHP decodes `A+B%2BC` as `A B+C` with form decoding and `A+B+C` with raw decoding. Both leave `%zz` intact. The form application also validates field names, duplicates and UTF-8 independently of percent decoding.

### Follow-up to task 13 — PHP free builtin inputs

An all-options compiler publication showed two sources of the gap. Most runtime signatures published a parameter type and its native passing mode, with no PHP argument override. Preparation previously treated these as exact-type requirements. Five PHP regex overload groups explicitly marked their ordinary pattern, subject, replacement, flags, offset or limit inputs exact as well.

| Surface inspected | Affected inputs and controls |
|---|---|
| JSON | The opt-in document root and every node operation take document/node values. Document parsing, PHP decode and typed record decode take text. Encoding needs exact overload priority: an empty nullable has its own encoder. |
| Strings and environment | String transforms, search, compare, split, join and formatting inputs without a policy shared the gap, as did the named input to getenv. Examples include trim, substr, strpos, str_starts_with and the selected collection in implode. The previously published strlen, str_replace and number_format typed inputs already used checked argument admission. |
| Filesystem, streams and curl | Path and data arguments (basename, file_get_contents, file_put_contents), stream arguments (fopen, fread, fwrite, fclose) and handle/option arguments (curl_exec, curl_setopt, curl_getinfo) use concrete published types. Each eligible by-value slot follows the same rule. A selector-specific mixed return is a separate contract. |
| Regex | preg_quote, preg_split, preg_split_offsets and both callback helpers had ordinary inputs without a slot policy. preg_match, preg_match_all, their two fixed-order aliases and preg_replace had explicit exact value modes. Matches and count are writable outputs. preg_filter and preg_grep already published typed input policies. |
| Other general API modules used from PHP | The same preparation path also covers concrete free-function inputs in modules such as datetime, process and FastCGI. Enabling the module and the general API remains a prerequisite where specified. |
| Boundaries | Ordinary PHP parameters on source functions, methods and runtime bindings now share value-argument admission. This includes opt-in general names. Exact mixed predicates, strict hash keys, writable storage, receiver selection and compiler-specific constructs such as isset retain their own contracts. Zero-argument helpers have no affected input. |

Focused source probes are in [snippets.py](../../samples/php-developer-qos/snippets.py): json-direct-chain, json-failed-chain, json-exact-or-carried, builtin-carried-aliases, regex-carried-inputs and regex-carried-failure. The [native JSON test](../../compiler/tests/json_native.py) checks the lowered document call and runs it.
