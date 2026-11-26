# Regex module

Doc Status: normative

Projects select the regex module. PHP exposes the preg names. The general surface
uses regex names and follows the project opt-in for PHP and JS. C++ uses the
general surface. PCRE2 compiles length-delimited pattern and subject bytes in
the runtime archive. The public operation headers do not require PCRE2 headers.

| General operation | Result contract |
| --- | --- |
| regex_test, regex_find_span | Checked boolean and checked byte start respectively. Span takes a nonnegative byte offset and writes the exclusive end only on a hit. A miss returns −1. |
| regex_quote, regex_jit_available | Quoted string with an optional delimiter, and boolean capability of the linked PCRE2 engine. |
| regex_match, regex_match_named | Falseable numbered string vector or string-keyed hash containing both numbered and named captures. A miss yields an empty collection. A successful empty match contains the empty full-match string. |
| regex_match_all, regex_match_all_named | Falseable vectors of capture rows in match order. The optional byte offset on the capture APIs may count backwards from the end. |
| regex_match_all_pattern_order | Falseable vector of columns indexed by capture number. Missing values in a column are empty strings. |
| regex_grep, regex_filter | Falseable packed string vector. Grep selects matching subjects. Filter returns rewritten subjects for which a replacement occurred, including identity replacements. |
| regex_split | Falseable packed string vector. Optional limit and flags select the number of parts and whether to drop empty strings or include captured delimiters. |
| regex_replace, regex_replace_callback, regex_replace_callback_array | Falseable string. Positional references expand in replacement text. Callback text is literal. A typed callback takes an owned vector of numbered strings. A string-keyed callback hash runs in insertion order on successive results. |

PHP publishes preg_match, preg_match_all, preg_quote, preg_jit_available,
preg_grep, preg_filter, preg_split, preg_replace, preg_replace_callback and
preg_replace_callback_array. Match status is a falseable integer. A mutable mixed
matches argument receives the captures and becomes an empty collection on regex
failure. Match-all defaults to pattern order and also accepts PREG_SET_ORDER.
Numbered output overloads require an exact writable variable. For preg_match,
a vector<string> receives the full match at index zero and numbered captures in
order. Interior unmatched groups are empty strings; trailing unmatched groups
are omitted. A miss writes an empty vector. For preg_match_all, a
vector<vector<string>> receives columns by default (or PREG_PATTERN_ORDER),
including the full-match column and every declared group even on a miss.
Unmatched cells are empty strings. PREG_SET_ORDER writes one numbered row per
hit using the single-match rules. Named groups retain their numeric position.
Both typed overloads clear the destination on regex failure and return false.
An empty full match counts as a hit. Unsupported flags raise ValueError.
Named native outputs use string keys. Group zero has key "0"; each named group
inserts its name before its decimal group number in capture order. A single
match writes hash<string,string>. Match-all with PREG_SET_ORDER writes
vector<hash<string,string>>. Rows omit trailing unmatched groups; for duplicate
names the last participating group supplies the value. Pattern order (including
the default) writes hash<vector<string>,string>, with a column for every group
even on a miss. A duplicate name selects its rightmost declared column and keeps
its original insertion position. An order incompatible with the output slot
raises ValueError before regex evaluation or mutation. Regex failure clears
the typed output and returns false.

PHP also exposes `preg_match_all_set_order(pattern, subject, matches[, offset])`
and `preg_match_all_pattern_order(pattern, subject, matches[, offset])`. The
name selects the layout. Both accept an exact writable `vector<vector<string>>`
for numbered captures. Set order accepts `vector<hash<string,string>>` for named
rows; pattern order accepts `hash<vector<string>,string>` for named columns.
The optional fourth argument is a byte offset, defaulting to zero. Status,
capture keys, misses and failures follow the corresponding `preg_match_all` order.

The following capture cell types apply to PHP single matches, set-order rows
and pattern-order columns. The surrounding numbered vectors and named hashes
keep their existing layout and key order.

| Capture modifiers | Native cell | Unmatched cell | Participating empty capture at byte N |
| --- | --- | --- | --- |
| None | `string` | `""` | `""` |
| `PREG_UNMATCHED_AS_NULL` | `?string` | `null` | present `""` |
| `PREG_OFFSET_CAPTURE` | `regex_offset_capture` | `text = "", byte_offset = -1` | `text = "", byte_offset = N` |
| Both | `regex_nullable_offset_capture` | `text = null, byte_offset = -1` | `text = "", byte_offset = N` |

The two records expose readonly `text` and `byte_offset` fields. Offsets are
absolute byte positions in the original subject, including with a nonzero start
offset or a UTF pattern. Null mode includes trailing unmatched groups in rows.
Without it, rows end at the last participating group. Columns always cover every
declared group. Boxed outputs use the same values and a two-element indexed
array for an offset pair. Traditional helpers validate the flags against the
exact native output type before writing. The two fixed-order helpers infer
the capture modifiers from that output type and retain the optional byte offset.

Both split helpers accept PREG_SPLIT_NO_EMPTY and PREG_SPLIT_DELIM_CAPTURE.
A positive limit counts emitted ordinary pieces, including the final unsplit
remainder. Captured delimiters and suppressed empty pieces consume no limit.

PHP `preg_split_offsets(pattern, subject[, limit[, flags]])` returns
`result_or_false<vector<regex_offset_capture>>`. It always includes absolute
byte offsets and accepts a redundant PREG_SPLIT_OFFSET_CAPTURE bit. A field
or participating delimiter capture can be empty at its actual byte position.
An interior unmatched delimiter group yields empty text at -1. Trailing
unmatched groups are omitted. With NO_EMPTY all empty cells are omitted.
A successful empty vector remains present. Regex failure returns false.
Plain preg_split retains its string vector and rejects the offset bit.

PHP preg_split directly exposes regex_split and returns result_or_false<vector<string>>.
For preg_grep the second argument selects the overload. A vector<string> yields
result_or_false<hash<string,int>>, keyed by the original vector indices in encounter
order. A mixed input yields result_or_false<mixed> and preserves its selected keys.
A bare collection literal uses mixed. A carrier of either input type supplies its
payload under the implicit extraction policy; a missing payload raises before grep
runs. Invalid grep patterns yield false even for an empty input.

PHP preg_filter selects its overload from the subject for all supported arities
(pattern, replacement, subject, optional limit and optional fifth count). A string subject returns
?string. Any replacement yields a present string, including empty output and
identity replacements. Zero replacements yields null. Invalid patterns and
matching failures throw ValueError. A vector<string> subject returns
result_or_false<hash<string,int>> with original indices. A hash of strings keyed
by int or string returns a falseable hash of the same type. Collection overloads
retain selected keys in encounter order and return false on regex failure,
discarding any partial output. A mixed subject retains its result_or_false<mixed>
contract. A non-container mixed subject yields an empty container for a valid
pattern. Bare PHP list literals select the mixed overload.
The fifth argument binds an exact writable integer. After limit validation the
count starts at zero. It includes every completed replacement, including
identity replacements. The limit resets per subject. A scalar matching error
throws with the partial count available. Collections stop at the first failing
entry and return false with the total through that entry, including replacements
completed inside it. A pattern compilation error leaves the count at zero.
Successful empty collections retain the value state. A callback exception propagates.

Each PHP replacement callback takes one capture row and returns a string.
The declared callback input, or the callback table's value signature, selects
the native row. A numbered row is `vector<Cell>`. A named row is
`hash<Cell,string>` and includes decimal group keys and name aliases in the
single-match order described above. Both use the same capture cell table as
preg_match. A mixed row uses integer group keys and string names, including
when flags are zero. Adding name aliases changes the count and iteration of
mixed rows for patterns containing names.

Flags follow count (sixth argument for preg_replace_callback, fifth for its
array form) and default to zero. Only PREG_OFFSET_CAPTURE and
PREG_UNMATCHED_AS_NULL are admitted. The bits must match the native cell type
exactly. A mixed callback accepts all four combinations. Unsupported bits or
incompatible cells raise ValueError before any invocation or count write, even
for an empty table. An unannotated inline callback defaults to mixed.
Offsets are absolute bytes in the input to the current pattern pass. Later
patterns in a table see the output of the preceding pattern.

The PHP callback helpers accept an optional exact writable integer count after
the by-value limit (argument five for preg_replace_callback and four for
preg_replace_callback_array). preg_replace_callback publishes the number of
invocations on return, including a false regex result, and includes the throwing
invocation when a callback raises. The running count is private during callbacks.
preg_replace_callback_array applies the limit independently per pattern and
publishes the sum only on success, including zero for an empty table. On failure
or exception the table helper leaves any callback writes to the count slot intact.

Both PHP callback helpers also accept `vector<string>`, `hash<string,int>` and
`hash<string,string>`. The result is a falseable collection of the subject type.
Each pattern visits all rows in iteration order, preserving keys, misses and empty
replacement strings. Each row gets the full limit for that pattern. A table
finishes one pass before starting the next. The first regex failure discards
the collection and returns false. A throwing invocation propagates immediately.
The single helper counts invocations across rows through failure or throw.
The table publishes count only after every pass succeeds.

Replacement limits are per subject and per pattern. The optional limit is a
value argument even when the caller supplies a writable integer. Minus one
means unlimited. Zero replacements still validates the pattern. A negative
limit below minus one raises ValueError. Split also accepts zero for unlimited.
Scalar PHP `preg_replace(pattern, replacement, subject[, limit[, count]])`
returns `result_or_false<string>`. The fifth argument binds an exact writable
integer slot. Its initial value is discarded after limit validation. Each
completed replacement counts once, including identity replacements. On regex
failure the call returns false and the slot retains the number completed before
failure. An invalid pattern, miss or zero limit writes zero. When the same slot
supplies limit and count, its incoming value determines the limit.

PHP `preg_replace` also accepts a vector of strings or an integer- or
string-keyed hash of strings. The result is a falseable collection of the same
type. Success preserves every key in iteration order, including misses and
replacements yielding an empty string. Each row gets the full limit. On the
first failed row, the call returns false and discards the output collection.
Count includes completed replacements in earlier rows and in the failed row.
An invalid pattern returns false even for an empty collection.
