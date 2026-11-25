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
The split flags are PREG_SPLIT_NO_EMPTY and PREG_SPLIT_DELIM_CAPTURE.

PHP grep, filter and split return result_or_false<mixed>. Grep and filter
preserve the selected input keys. Split produces a packed collection.
Successful empty collections retain the value state. PHP callbacks take a
mixed collection of numbered captures. A callback exception propagates.

Replacement limits are per subject and per pattern. The optional limit is a
value argument even when the caller supplies a writable integer. Minus one
means unlimited. Zero replacements still validates the pattern. A negative
limit below minus one raises ValueError. Split also accepts zero for unlimited.
Count-output replacement overloads and offset-capture flags are outside this
publication.
