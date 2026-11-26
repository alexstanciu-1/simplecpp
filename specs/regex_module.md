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

The split flags are PREG_SPLIT_NO_EMPTY and PREG_SPLIT_DELIM_CAPTURE.

PHP preg_split directly exposes regex_split and returns result_or_false<vector<string>>.
For preg_grep the second argument selects the overload. A vector<string> yields
result_or_false<hash<string,int>>, keyed by the original vector indices in encounter
order. A mixed input yields result_or_false<mixed> and preserves its selected keys.
A bare collection literal uses mixed. A carrier of either input type supplies its
payload under the implicit extraction policy; a missing payload raises before grep
runs. Invalid grep patterns yield false even for an empty input.

PHP preg_filter selects its overload from the subject for both supported arities
(pattern, replacement, subject and optional limit). A string subject returns
?string. Any replacement yields a present string, including empty output and
identity replacements. Zero replacements yields null. Invalid patterns and
matching failures throw ValueError. A vector<string> subject returns
result_or_false<hash<string,int>> with original indices. A hash of strings keyed
by int or string returns a falseable hash of the same type. Collection overloads
retain selected keys in encounter order and return false on regex failure,
discarding any partial output. A mixed subject retains its result_or_false<mixed>
contract. A non-container mixed subject yields an empty container for a valid
pattern. Bare PHP list literals select the mixed overload.
Successful empty collections retain the value state. PHP callbacks take a
mixed collection of numbered captures. A callback exception propagates.

Replacement limits are per subject and per pattern. The optional limit is a
value argument even when the caller supplies a writable integer. Minus one
means unlimited. Zero replacements still validates the pattern. A negative
limit below minus one raises ValueError. Split also accepts zero for unlimited.
Count-output replacement overloads and offset-capture flags are outside this
publication.
