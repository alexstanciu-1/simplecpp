# PHP array and string inventory

Doc Status: planning

2026-11-28. Compared both PHP manual chapter indexes with the generated PHP and common free-function registry in the working tree based on `d5aa46625`, including the pending array and scalar onboarding changes. Compiler-owned constructs are counted separately from runtime bindings. Each entry appears once per chapter. Only the first row of each chapter is available under the PHP spelling. The remaining rows are gaps, alternative syntax, or historical entries. A published name means at least one admitted form. It does not establish parity for optional arguments, types, flags or errors. Extension chapters such as PCRE and mbstring have their own inventories. [Array index](https://www.php.net/manual/en/ref.array.php) · [String index](https://www.php.net/manual/en/ref.strings.php)

## PHP array chapter (88 entries)

| Group | Names |
| --- | --- |
| Published PHP names (bounded signatures) | `count`, `array_map`, `array_filter`, `in_array` |
| Keys, membership and projection | `array_values`, `array_keys`, `array_key_exists`, `key_exists`, `array_is_list`, `array_key_first`, `array_key_last`, `array_first`, `array_last`, `array_search`, `array_column` |
| Predicates, scans and aggregation | `array_all`, `array_any`, `array_find`, `array_find_key`, `array_reduce`, `array_count_values`, `array_sum`, `array_product`, `array_unique` |
| Construct, split and combine | `array_chunk`, `array_combine`, `array_fill`, `array_fill_keys`, `array_flip`, `array_pad`, `array_reverse`, `array_slice`, `array_merge`, `array_merge_recursive`, `array_replace`, `array_replace_recursive`, `array_change_key_case`, `range` |
| Mutable operations and selection | `array_pop`, `array_push`, `array_shift`, `array_unshift`, `array_splice`, `array_walk`, `array_walk_recursive`, `array_rand`, `shuffle` |
| Set operations (values, keys and comparators) | `array_diff`, `array_diff_assoc`, `array_diff_key`, `array_diff_uassoc`, `array_diff_ukey`, `array_intersect`, `array_intersect_assoc`, `array_intersect_key`, `array_intersect_uassoc`, `array_intersect_ukey`, `array_udiff`, `array_udiff_assoc`, `array_udiff_uassoc`, `array_uintersect`, `array_uintersect_assoc`, `array_uintersect_uassoc` |
| Ordering | `sort`, `rsort`, `asort`, `arsort`, `ksort`, `krsort`, `natsort`, `natcasesort`, `usort`, `uasort`, `uksort`, `array_multisort` |
| Cursor, scope and convenience names | `current`, `end`, `key`, `next`, `pos`, `prev`, `reset`, `compact`, `extract`, `sizeof` |
| Other syntax and removed PHP entries | `array`, `list`, `each` |

## PHP string chapter (105 entries)

| Group | Names |
| --- | --- |
| Published PHP names (bounded signatures) | `htmlspecialchars`, `quoted_printable_encode`, `quoted_printable_decode`, `strnatcmp`, `strnatcasecmp`, `str_increment`, `str_decrement`, `strtr`, `str_ireplace`, `chunk_split`, `wordwrap`, `ucwords`, `nl2br`, `addslashes`, `stripslashes`, `strstr`, `strchr`, `stristr`, `strrchr`, `strpbrk`, `strspn`, `strcspn`, `echo`, `ord`, `chr`, `chop`, `strrev`, `str_rot13`, `bin2hex`, `explode`, `hex2bin`, `implode`, `lcfirst`, `ltrim`, `number_format`, `rtrim`, `str_contains`, `str_ends_with`, `str_pad`, `str_repeat`, `str_replace`, `str_starts_with`, `strcasecmp`, `strcmp`, `stripos`, `strlen`, `strncasecmp`, `strncmp`, `strpos`, `strripos`, `strrpos`, `strtolower`, `strtoupper`, `substr`, `substr_compare`, `substr_count`, `substr_replace`, `trim`, `ucfirst` |
| Direct byte operations and aliases | `str_split`, `join` |
| Text composition and scanning | `count_chars`, `str_word_count`, `str_shuffle`, `strtok` |
| HTML and web boundaries | `htmlspecialchars_decode`, `htmlentities`, `html_entity_decode`, `get_html_translation_table`, `strip_tags`, `parse_str`, `str_getcsv` |
| Formatting, output and parsing | `sprintf`, `printf`, `fprintf`, `vsprintf`, `vprintf`, `vfprintf`, `sscanf`, `print` |
| Escapes and transfer encodings | `addcslashes`, `stripcslashes`, `quotemeta`, `convert_uuencode`, `convert_uudecode` |
| Matching, phonetics and locale | `levenshtein`, `similar_text`, `metaphone`, `soundex`, `strcoll`, `setlocale`, `localeconv`, `nl_langinfo`, `hebrev` |
| Checksums and password/hash functions | `crc32`, `crypt`, `md5`, `md5_file`, `sha1`, `sha1_file` |
| Legacy and deprecated encodings/formatting | `convert_cyr_string`, `hebrevc`, `money_format`, `utf8_encode`, `utf8_decode` |


## Array admission observed in a PHP caller

Local native caller, default project, no general API opt-in. Used a vector with values 0 and 2, a string-keyed hash, and one table of each boxed family. Every table used keys `"a"` and `"b"`. The caller mapped the identity, filtered positive values, read the survivor by its original key and checked the source kind. Each printed 2 input entries, 1 survivor, value 2 and true for `is_array`. Negative cases were separate compiler invocations.

| Call tested | vector<int> | hash<int,string> | mixed table | dynamic table |
| --- | --- | --- | --- | --- |
| count(source) | 2 | 2 | 2 | 2 |
| array_map(callback, source) | vector, dense | hash, original keys | mixed, original keys | dynamic, original keys |
| array_filter(source, callback) | hash keyed by original position | hash, original keys | mixed, original keys | dynamic, original keys |
| array_filter(source) | 1 survivor | 1 survivor | rejects, requires callback | rejects, requires callback |
| in_array(2, source) | true | true | rejects, requires typed collection | rejects, requires typed collection |
| is_array(source), from the type predicates | true | true | true for container tag | true for container tag |

The native filter without a callback requires a published scalar condition. Map takes one input and a callable. Membership compares the exact scalar element type using its published equality. General transform tests also cover sparse integer hash keys, mixed copy-on-write and shared dynamic children. Boxed storage may hold a scalar or null, so each future boxed collection overload needs a non-table case. `array(1)`, `list($x)=[1]`, `sizeof([1])` and the PHP print statement were separately rejected. Use bracket literals and `count` today. PHP removed `each`, `convert_cyr_string`, `hebrevc` and `money_format` by 8.5; they do not belong on the implementation backlog. [Collection contracts](../../collection_traversal.md#php-array-helpers) · [Boxed keys and ownership](../../boxed_slice.md)

## Proposed next contracts

| Candidate | Vector | Typed hash | Mixed | Dynamic |
| --- | --- | --- | --- | --- |
| array_values(source) | Dense vector<T>, independent result under subsequent writes | Dense vector<T> in insertion order | Fresh dense mixed table, mixed child copy semantics | Fresh dense dynamic table, shared dynamic children |
| array_keys(source), one argument | vector<int> of positions | vector<K> in insertion order | Dense mixed table containing each actual key kind | Dense dynamic table containing each actual key kind |
| array_key_exists(key, source) | Integer in bounds, including zero | Exact K, inspect key presence even for a null value | Use boxed key classification, distinguish absent and present-null | Same presence test on the dynamic table |
| array_is_list(source) | Always true, still evaluate source | Check iteration keys are integers 0…n−1 in order; empty is true for either key type | Check actual keys and order | Same check |
| array_search(needle, source) | Index or false | K or false, verify the carrier exists for K | Defer heterogeneous key return and loose comparison | Same decisions |

For each new read helper exercise empty input, keys 0 and 2, reinserted keys, numeric-looking string keys, and presence with a null payload where representable. Use an alias and edit a nested result to verify the expected ownership. For mutators settle writable-slot admission, numeric reindexing, key preservation and shared aliases before selecting a runtime target. Vector append and indexed removal already address some uses of push and splice. Sorting by value while keeping keys (`asort`) can produce a sparse or reordered index sequence that an in-place typed vector cannot represent. Multi-input map, filter key modes, heterogeneous comparison, recursive merges and callback sorts merit separate contracts.

The bounded PHP HTML escaper covers UTF-8, HTML 4.01 and double encoding enabled. The existing `implode` and `explode` bindings, comparisons, replace count and search miss contracts are documented in the [string family audit](string_families_audit.md) and [byte-string contract](../../string_slice.md).

## Relocatable PHP source paths

On Linux, PHP `__FILE__` and `__DIR__` use the running executable as their anchor. The native build records the displacement from the executable directory to the project root. The compiler emits the source-specific suffix. The runtime snapshots the actual executable directory at startup and produces a normalized absolute string. Moving the package as a unit preserves the layout even when launched by symlink from a different working directory. Diagnostic source identifiers keep their existing logical paths.
