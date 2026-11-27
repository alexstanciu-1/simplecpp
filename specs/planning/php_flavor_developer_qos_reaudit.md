# PHP developer experience: replay of the 33 original tasks

Doc Status: planning

The [original field report](php_flavor_developer_qos.md) describes the experience at `356b6ff15`. The first full replay used `4e035377d`. On November 27 we reran the full study on dev002 with the PHP onboarding changes based on `bbd2d9663`. The unit of assessment is the developer's task. **Fixed** means the original blocking operation now works under its intended v0.2 contract. **Still reproducible** means at least one concrete usability issue remains for discussion, including misleading diagnostics. **Intentional difference** means the tested behavior follows a language or library boundary we can explain to a PHP developer. A reproducible inconvenience is evidence for discussion, not a commitment to implement PHP compatibility.

## Evidence

On dev002, all ten [study drivers](../../samples/php-developer-qos/) completed successfully with PHP 8.5.12 and Clang 18.1.3. All 250 isolated snippets matched their recorded observations. Of these, 129 ran and printed the expected output, 107 reported the expected compiler error, and 14 raised the expected runtime failure. An expected rejection can still be an onboarding issue. The default compiler and runtime suite passed all 618 tests after correcting the style annotation on the PHP entry fallthrough fact.

| Replay | Observed on dev002 |
|---|---|
| Applications and input corpus | All 11 applications passed, including Apache/FastCGI, MariaDB, the curl client and the contended lock. The separate corpus checked 10,000 invoices, 80 form bodies and 276 settings requests. |
| Carriers and storage | The presence driver and all 32 paired comparison tables completed. Of 117 storage experiments, 84 produced output, 29 were rejected at build time and four failed a runtime check. For example, `?int` worked for a local or parameter and was rejected as an instance field. |
| Wire and PHP comparisons | Boxed decoding rejected a duplicate decoded name, and the document parser reported `duplicate_key@7`. All 60 byte-position comparisons agreed with stock PHP. Numeric-looking strings still differed on one equality comparison and three ordering comparisons. |
| Multi-file projects and edits | An included test script was diagnosed at its top-level statement. Explicit source selection and nested module boundaries worked. All five ordinary same-second edits produced the intended executable. An invalid atomic save was rejected, and repair restored the build. |

## November 27 onboarding follow-up

The complete first script can be `echo "hello";`. Source discovery brings project functions into scope. Copied PHP file-loading statements now point to the project manifest. An opening tag is accepted during migration and produces one compiler warning across tagged files.

**Calling functions and handling failures.** Unpacking is optional. For example, `$p = strpos("abc", "a"); if ($p !== false) { $n int = $p; echo $n; }` printed zero. Changing the needle to a missing byte skipped the body. Passing a successful `json_encode(10)` result directly to `strlen` printed 2. A typed consumer checks for a payload and throws on a missing one. Use a guard when the caller can recover, forward a compatible result when the caller's caller handles failure, or use `take` when the payload and diagnostic are needed separately.

## Task verdicts

| # | Original developer task | Verdict | Current evidence and scope |
|---|---|---|---|
| 1 | Detect a failed search or read | Fixed | Strict false guards distinguished byte position zero, a search miss and a missing file. Two missed searches and two absent nullable ints also compared without extracting an absent payload. |
| 2 | Test emptiness | Fixed | The string matrix passed for native and boxed text. The contract uses byte length, so the string `"0"` is nonempty. Boxed numbers and null still raise `invalid_empty_operand`. |
| 3 | Default a missing value | Intentional difference | Nullable coalescing preserved zero and `hash_find` supplied the fallback on a miss. Direct hash reads and falseable search results still reject `??`. Presence, a false sentinel and key lookup have distinct contracts. |
| 4 | Ask whether a key exists | Fixed | `isset($h[$k])` now compiles for typed hashes, including a stored zero. The nullable-value probe separately exercises absence. Boxed `has` can distinguish an explicit null from an absent key. |
| 5 | Read a form integer | Fixed | `(int)"42"` printed 42. A function casting its string parameter printed 1234 for decimal input and raised `invalid_decimal_text` for bad text. A mixed slot holding text still fails direct integer extraction with `boxed_scalar_kind`; extract its string first. |
| 6 | Print a value | Still reproducible | Echoing a bool, indexed byte or boxed read reports the same int64/string restriction. An extracted integer prints. Bool interpolation prints `true` or `false`. The diagnostic omits the received type. |
| 7 | Handle a bad request | Intentional difference | Invalid JSON returned a checked failure. A user exception exposed `get_message()`; a range guard ran finally and exited. The filesystem result forwarded its failure, but the PHP-style attempt `$r->error()` still has no such method. |
| 8 | Read a nested JSON property | Fixed | Both `$v->user->name` and `$v["user"]["name"]` printed Ana directly. |
| 9 | Copy a row and change it | Fixed | The nested indexed write printed Jo. The explicit copy/edit/write-back control preserved Ana in the original. Mixed copies and shared dynamic aliases retain their intended distinct behavior. |
| 10 | Validate an unknown value | Still reproducible | Ordinary JSON decode supplied dynamic, while `is_bool` required exact mixed. `is_string` and `is_array` were unknown. A successful mixed predicate followed by explicit typed extraction worked. |
| 11 | Round-trip a JSON API response | Intentional difference | The boxed table encoded an empty object as `[]`; document parsing retained object identity. The wire corpus exercised numeric-looking keys, exact large-number tokens and duplicate names. Use documents for wire fidelity. |
| 12 | Distinguish malformed bytes from an invalid schema | Intentional difference | Boxed decoding accepted raw invalid UTF-8, while the document parser reported `utf8@1`. The settings endpoint distinguished malformed input (400), valid input with the wrong shape (422) and valid false (200). |
| 13 | Pass a checked or boxed value to a function | Fixed | Source functions, methods and builtins share by-value argument admission. The document-root chain printed `array` for a valid parse and raised `carrier_no_value` before the next statement for a failed parse. Both observations reproduced in the full replay. |
| 14 | Move between domain objects and JSON | Intentional difference | Direct encode of the tested Product class and typed vector rejected. Explicit field projection encoded `{"id":7}`. This verdict applies to those inputs and does not generalize to every record API. |
| 15 | Use familiar collection operations | Still reproducible | The former list/foreach/strlen snag is fixed (printed 11). Typed `collection_map` worked. PHP `array_map`, `array_filter`, `in_array` and the broad `array` parameter remain unavailable in these probes. |
| 16 | Update or remove elements | Intentional difference | An indexed vector element could not bind the writable argument. Passing a named vector by reference updated its caller. Removing an index with the vector method preserved dense positions. |
| 17 | Keep a loop variable or reference | Intentional difference | A newly introduced element stayed loop-local, a predeclared variable survived empty traversal, and writable foreach left no alias to the last element. Structural mutation during that traversal rejected. |
| 18 | Start a script | Fixed | An echo-only entry printed hello and exited zero. An opening tag works with one deprecation warning per compiler execution. The strict-types diagnostic explains how to fix the file. PHP CLI globals remain a separate flavor decision. |
| 19 | Choose an inferred type | Fixed | The compact typed declaration `$v vector<int>=[];` builds. Bare null still requires a type. |
| 20 | Select defaults and branches | Intentional difference | The shorthand ternary now works. With `$label="0"`, `$label ?: "anon"` printed anon. The full ternary with incompatible arms still rejected, and the match with a default returned 405. |
| 21 | Compare or divide request values | Intentional difference | Integer division produced 3 and floating division 3.5. Text comparisons remained lexical. The PHP differential found one equality and three ordering differences on numeric-looking strings; all 60 byte-position comparisons agreed. |
| 22 | Render a template | Still reproducible | Field interpolation reported “Expected closing interpolation brace”; interpolating a local worked. The byte/codepoint control gave 6 versus 2 for the same string, as intended. |
| 23 | Call a function | Intentional difference | Typed named parameters, literal defaults, variadics and vector unpack at the call worked. Untyped named parameters, expression defaults, list spread and assignment destructuring rejected. |
| 24 | Declare a model | Still reproducible | The original optional-field read `$u?->name ?? "guest"` printed guest. Constructor promotion still reported “Expected identifier”. |
| 25 | Dispatch through a type | Intentional difference | The missing-override diagnostic named the fix. PHP enum member lookup rejected; the published enum value/lookup helpers worked. A hint to those helpers would aid discovery. |
| 26 | Traverse a producer | Intentional difference | A typed iterator resumed after break and the keyed producer yielded its keys. PHP's Generator name and yield delegation remain outside the tested slice. |
| 27 | Organize multiple files | Still reproducible | All four include/require spellings now point to source selection in module.json. The __DIR__ probe still reported “Unknown constant: __DIR__”. |
| 28 | Read HTTP input and send status | Intentional difference | Superglobals, `php://input` and PHP header/session helpers did not supply request state. The explicit FastCGI exchange handled the request, headers and status in the live apps. |
| 29 | Build or decode a URL, HTML or CSV | Still reproducible | Form/query encoding and decoding, HTML escaping and CSV helpers remain unavailable under the probed PHP names. The form app still needed its own component decoder. URL components, HTML text and CSV fields require separate contracts. |
| 30 | Use timestamps or a regex | Still reproducible | PHP `time`, `date` and `strtotime` were unknown with datetime enabled; the general UTC formatter worked. The old mixed-captures workaround is obsolete for numbered strings: a fresh `vector<string>` capture probe printed `2:a`. Miss and invalid-pattern controls still distinguished present zero from failure. |
| 31 | Call a remote endpoint | Still reproducible | The compiled curl client handled HTTP errors, invalid upstream data and connection failure. `CURLINFO_HTTP_CODE` still rejected while the published response-code spelling worked. This is a narrow naming issue; the exercise gives no reason to map every curl selector. |
| 32 | Prepare and bind a SQL query | Fixed | The ticket report guards the prepare result, extracts the statement and binds a named slot. On dev002 it handled a failed prepare and read each changed state on subsequent MariaDB executions. The temporary-slot and unproven-alias probes still reject. |
| 33 | Bring a filesystem snippet | Intentional difference | The two-argument PHP write alias rejected the append-flag call. The journal used append mode and a checked byte count; contention left the file untouched. POSIX lexical path behavior and general-API visibility remained as specified. |

## Decisions to discuss

| Group | Tasks | Smallest useful discussion |
|---|---|---|
| Editing and inspecting boxed data | 10 | Decide which predicates are useful on decoded data within the existing set of boxed kinds. |
| First-file and everyday diagnostics | 6, 22, 24, 27 | Give the PHP developer a usable next line of code: show the actual echo operand type and identify unsupported interpolation, model and path syntax. |
| Web data boundaries | 29 | Start with form component encode/decode, which removes handwritten parsing from a working app. Discuss query multiplicity, HTML escaping contexts and CSV rules independently. |
| Familiar library names | 15, 30, 31 | Inventory typed collection counterparts and useful datetime aliases. For curl, decide only whether the response-code synonym pays its way. Native regex captures already address the original regex complaint. |

**November 27 full replay of the 33 tasks:** 10 fixed, 9 still reproducible, 14 intentional differences. All nine outstanding tasks reproduced on dev002. The native CLI alias decision and wider regex family work belong to the separate helper inventory. Neither is an extra task in this tally.

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
