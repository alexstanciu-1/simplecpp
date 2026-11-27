# PHP developer experience: replay of the 33 original tasks

Doc Status: planning

The [original field report](php_flavor_developer_qos.md) describes the experience at `356b6ff15`. The full replay used `4e035377d` on dev002. Task 13 was subsequently verified at `1964c4cab`; the other rows retain their replay verdicts. The unit of assessment is the developer's task. **Fixed** means the original blocking operation now works under its intended v0.2 contract. **Still reproducible** means at least one concrete usability issue remains for discussion, including misleading diagnostics. **Intentional difference** means the tested behavior follows a language or library boundary we can explain to a PHP developer. A reproducible inconvenience is evidence for discussion, not a commitment to implement PHP compatibility.

## Evidence

Ran all ten [study drivers](../../samples/php-developer-qos/) against the current compiler and runtime. The 245 isolated snippets ran to completion. The snippet driver exited 1 for exactly one stale expectation: `form-int` expected a build rejection and printed `42`. Other fixes listed below already have updated expectations in that driver. The other nine drivers exited 0. These are developer-experience probes, so an expected compile rejection or runtime failure can be a successful observation.

All 11 apps passed, including the Apache/FastCGI client and service, MariaDB report and contended file lock. The corpus covered 10,000 invoice rows, 80 form bodies and 276 settings requests. Also reran the presence, paired-state, 117 storage-position, JSON wire-shape, stock-PHP differential and project-layout probes. As a separate control outside the 33, five ordinary same-second saves produced the five expected executables, and an invalid atomic save was rejected. Three additional source controls exercised a typed regex capture and a checked decimal conversion in a function body.

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
| 8 | Read a nested JSON property | Still reproducible | Both `$v->user->name` and `$v["user"]["name"]` rejected. Reading through an intermediate dynamic local printed Ana. |
| 9 | Copy a row and change it | Still reproducible | Nested boxed writes still reject. Extract/edit/write-back succeeded. Mixed copies and shared dynamic aliases retain their intended distinct behavior. |
| 10 | Validate an unknown value | Still reproducible | Ordinary JSON decode supplied dynamic, while `is_bool` required exact mixed. `is_string` and `is_array` were unknown. A successful mixed predicate followed by explicit typed extraction worked. |
| 11 | Round-trip a JSON API response | Intentional difference | The boxed table encoded an empty object as `[]`; document parsing retained object identity. The wire corpus exercised numeric-looking keys, exact large-number tokens and duplicate names. Use documents for wire fidelity. |
| 12 | Distinguish malformed bytes from an invalid schema | Intentional difference | Boxed decoding accepted raw invalid UTF-8, while the document parser reported `utf8@1`. The settings endpoint distinguished malformed input (400), valid input with the wrong shape (422) and valid false (200). |
| 13 | Pass a checked or boxed value to a function | Fixed | Source functions, methods and builtins share by-value argument admission. The document-root chain printed `array` for a valid parse and raised `carrier_no_value` before the next statement for a failed parse. All 468 dev002 suite tests and seven targeted developer probes passed. |
| 14 | Move between domain objects and JSON | Intentional difference | Direct encode of the tested Product class and typed vector rejected. Explicit field projection encoded `{"id":7}`. This verdict applies to those inputs and does not generalize to every record API. |
| 15 | Use familiar collection operations | Still reproducible | The former list/foreach/strlen snag is fixed (printed 11). Typed `collection_map` worked. PHP `array_map`, `array_filter`, `in_array` and the broad `array` parameter remain unavailable in these probes. |
| 16 | Update or remove elements | Intentional difference | An indexed vector element could not bind the writable argument. Passing a named vector by reference updated its caller. Removing an index with the vector method preserved dense positions. |
| 17 | Keep a loop variable or reference | Intentional difference | A newly introduced element stayed loop-local, a predeclared variable survived empty traversal, and writable foreach left no alias to the last element. Structural mutation during that traversal rejected. |
| 18 | Start a script | Still reproducible | An opening PHP tag reported “Expected variable name” and `declare(strict_types=1)` reported “Expected )”. A file containing only echo required an explicit value return. CLI argument helpers work; PHP globals are a separate flavor decision. |
| 19 | Choose an inferred type | Still reproducible | Bare null got an actionable type diagnostic. In the typed declaration `$v vector<int>=[];`, removing the space before assignment still produced “Expected >”. |
| 20 | Select defaults and branches | Intentional difference | Full ternaries require compatible result types, a match requires a default, and the shorthand ternary rejected. Choosing an empty-string default explicitly avoids assuming PHP truthiness. |
| 21 | Compare or divide request values | Intentional difference | Integer division produced 3 and floating division 3.5. Text comparisons remained lexical. The PHP differential found one equality and three ordering differences on numeric-looking strings; all 60 byte-position comparisons agreed. |
| 22 | Render a template | Still reproducible | Field interpolation reported “Expected closing interpolation brace”; interpolating a local worked. The byte/codepoint control gave 6 versus 2 for the same string, as intended. |
| 23 | Call a function | Intentional difference | Typed named parameters, literal defaults, variadics and vector unpack at the call worked. Untyped named parameters, expression defaults, list spread and assignment destructuring rejected. |
| 24 | Declare a model | Still reproducible | Nullsafe access and constructor promotion still produced generic parser errors. An explicit null branch and declared fields worked. The object and inline-struct copy probes retained their distinct identity rules. |
| 25 | Dispatch through a type | Intentional difference | The missing-override diagnostic named the fix. PHP enum member lookup rejected; the published enum value/lookup helpers worked. A hint to those helpers would aid discovery. |
| 26 | Traverse a producer | Intentional difference | A typed iterator resumed after break and the keyed producer yielded its keys. PHP's Generator name and yield delegation remain outside the tested slice. |
| 27 | Organize multiple files | Still reproducible | Manifest-selected sources linked. A copied `require_once` still produced “Expected variable name”; `__DIR__` was unknown. Neither points a new user to the project layout. |
| 28 | Read HTTP input and send status | Intentional difference | Superglobals, `php://input` and PHP header/session helpers did not supply request state. The explicit FastCGI exchange handled the request, headers and status in the live apps. |
| 29 | Build or decode a URL, HTML or CSV | Still reproducible | Form/query encoding and decoding, HTML escaping and CSV helpers remain unavailable under the probed PHP names. The form app still needed its own component decoder. URL components, HTML text and CSV fields require separate contracts. |
| 30 | Use timestamps or a regex | Still reproducible | PHP `time`, `date` and `strtotime` were unknown with datetime enabled; the general UTC formatter worked. The old mixed-captures workaround is obsolete for numbered strings: a fresh `vector<string>` capture probe printed `2:a`. Miss and invalid-pattern controls still distinguished present zero from failure. |
| 31 | Call a remote endpoint | Still reproducible | The compiled curl client handled HTTP errors, invalid upstream data and connection failure. `CURLINFO_HTTP_CODE` still rejected while the published response-code spelling worked. This is a narrow naming issue; the exercise gives no reason to map every curl selector. |
| 32 | Prepare and bind a SQL query | Still reproducible | A fresh statement observed changes to its bound variable on MariaDB. Checking prepare before extracting the statement still prevented binding a stable named variable. Temporary and escaping bindings also rejected as intended. Immediate execution arguments remain a working route. |
| 33 | Bring a filesystem snippet | Intentional difference | The two-argument PHP write alias rejected the append-flag call. The journal used append mode and a checked byte count; contention left the file untouched. POSIX lexical path behavior and general-API visibility remained as specified. |

## Decisions to discuss

| Group | Tasks | Smallest useful discussion |
|---|---|---|
| mysqli statement and bound-slot lifetimes | 32 | Can the compiler prove that a local prepare result has one extraction on the success path and that both the handle and bound slots stay confined? Check a second extraction and an escaping handle as counterexamples. |
| Editing and inspecting boxed data | 8–10 | Choose whether to support nested reads first. Writes also need defined copy/write-back versus alias semantics. Decide which predicates are useful on decoded data within the existing set of boxed kinds. |
| First-file and everyday diagnostics | 6, 18, 19, 22, 24, 27 | Give the PHP developer a usable next line of code: show the actual echo operand type, describe the entry-file form, point require users to manifest sources, and identify unsupported interpolation/model syntax. The generic-close/assignment ambiguity is a separate parser decision. |
| Web data boundaries | 29 | Start with form component encode/decode, which removes handwritten parsing from a working app. Discuss query multiplicity, HTML escaping contexts and CSV rules independently. |
| Familiar library names | 15, 30, 31 | Inventory typed collection counterparts and useful datetime aliases. For curl, decide only whether the response-code synonym pays its way. Native regex captures already address the original regex complaint. |

**Current tally:** 5 fixed, 14 still reproducible, 14 intentional differences. The native CLI alias decision and wider regex family work belong to the separate helper inventory. Neither is an extra task in this tally.

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
