# Strict vs PHP-Legacy Library Layering

Doc Status: planning

## Stage 1 API Inventory

Purpose: list the currently surfaced PHP runtime symbols, their current owner shape, and the proposed Stage 1 runtime direction.

Stage 1 rule:

- PHP API stays unchanged
- runtime ownership may change
- PHP becomes adapter-only for reusable capability

Proposed new runtime API rule:

- prefer short, expressive names
- prefer typed contracts
- prefer `result<T>` over PHP sentinel wrappers
- avoid `mixed_t` unless the domain is genuinely dynamic
- prefer typed collections such as `vector_t<T>` over PHP-shaped tables when the shape is known

## Notes

- This is a proposal inventory, not a final naming decision.
- The current surfaced symbol source is `generators/php/specs/php_runtime_symbols_legacy.json`.
- Stage 1 target is one pass across all extractable reusable APIs.
- PHP semantic operator/support helpers remain excluded unless directly required.
- Strict visible API names should be flat family-prefixed calls such as `fs_is_file(...)`, `str_strlen(...)`, `io_open(...)`, and `json_decode(...)`.
- Internal runtime ownership remains grouped by family under `scpp::fs::*`, `scpp::str::*`, `scpp::io::*`, and `scpp::json::*`.

## Status Legend

- `stage1-now`: included in the Stage 1 implementation pass
- `stage1-later`: shared-runtime extraction is desirable but deferred
- `leave-php-owned`: keep PHP-owned in Stage 1 unless a stronger cross-language/runtime case appears

## Filesystem

Namespaces:

- PHP API column: `scpp::php::`
- current runtime API column: `scpp::fs::`
- strict visible API column: flat prefixed names
- internal strict runtime column: `scpp::fs::`

| PHP API (unchanged in Stage 1) | Current runtime API | Strict visible API | Internal strict runtime API | Status | Stage 1 note |
| --- | --- | --- | --- | --- |
| `is_file(path) -> bool_t` | `is_file(path) -> bool_t` | `fs_is_file(path) -> bool_t` | `is_file(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `is_dir(path) -> bool_t` | `is_dir(path) -> bool_t` | `fs_is_dir(path) -> bool_t` | `is_dir(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `is_link(path) -> bool_t` | `is_link(path) -> bool_t` | `fs_is_link(path) -> bool_t` | `is_link(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `file_exists(path) -> bool_t` | `file_exists(path) -> bool_t` | `fs_exists(path) -> bool_t` | `exists(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `file_get_contents(path) -> result_or_false<string_t>` | `file_get_contents(path) -> result_or_false<string_t>` | `fs_get(path) -> result<string_t>` | `get(path) -> result<string_t>` | `stage1-now` | PHP adapter converts `result<string_t>` to `result_or_false<string_t>` |
| `file_put_contents(path, data) -> result_or_false<int_t>` | `file_put_contents(path, data) -> result_or_false<int_t>` | `fs_put(path, data) -> result<int_t>` | `put(path, data) -> result<int_t>` | `stage1-now` | PHP adapter converts `result<int_t>` to `result_or_false<int_t>` |
| `mkdir(path) -> bool_t` | `mkdir(path) -> bool_t` | `fs_mkdir(path) -> bool_t` | `mkdir(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `scandir(path) -> result_or_false<hash_t<mixed_t>>` | `scandir(path) -> result_or_false<hash_t<mixed_t>>` | `fs_scan(path) -> result<vector_t<string_t>>` | `scan(path) -> result<vector_t<string_t>>` | `stage1-now` | PHP adapter converts typed list to PHP-shaped table |
| `filesize(path) -> result_or_false<int_t>` | `filesize(path) -> result_or_false<int_t>` | `fs_size(path) -> result<int_t>` | `size(path) -> result<int_t>` | `stage1-now` | PHP adapter converts `result<int_t>` to `result_or_false<int_t>` |
| `filemtime(path) -> result_or_false<int_t>` | `filemtime(path) -> result_or_false<int_t>` | `fs_mtime(path) -> result<int_t>` | `mtime(path) -> result<int_t>` | `stage1-now` | PHP adapter converts `result<int_t>` to `result_or_false<int_t>` |
| `touch(path) -> bool_t` | `touch(path) -> bool_t` | `fs_touch(path) -> bool_t` | `touch(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `rmdir(path) -> bool_t` | `rmdir(path) -> bool_t` | `fs_rmdir(path) -> bool_t` | `rmdir(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `unlink(path) -> bool_t` | `unlink(path) -> bool_t` | `fs_remove(path) -> bool_t` | `remove(path) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `copy(source, dest) -> bool_t` | `copy(source, dest) -> bool_t` | `fs_copy(source, dest) -> bool_t` | `copy(source, dest) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `rename(source, dest) -> bool_t` | `rename(source, dest) -> bool_t` | `fs_rename(source, dest) -> bool_t` | `rename(source, dest) -> bool_t` | `stage1-now` | keep PHP name and contract |
| `realpath(path) -> result_or_false<string_t>` | `realpath(path) -> result_or_false<string_t>` | `fs_realpath(path) -> result<string_t>` | `realpath(path) -> result<string_t>` | `stage1-now` | PHP adapter converts `result<string_t>` to `result_or_false<string_t>` |
| `dirname(path) -> string_t` | `dirname(path) -> string_t` | `fs_dirname(path) -> string_t` | `dirname(path) -> string_t` | `stage1-now` | keep PHP name and contract |
| `basename(path) -> string_t` | `basename(path) -> string_t` | `fs_basename(path) -> string_t` | `basename(path) -> string_t` | `stage1-now` | keep PHP name and contract |

## String

Namespaces:

- PHP API column: `scpp::php::`
- current runtime API column: `scpp::php::`
- strict visible API column: flat prefixed names
- internal strict runtime column: `scpp::str::`

| PHP API (unchanged in Stage 1) | Current runtime API | Strict visible API | Internal strict runtime API | Status | Stage 1 note |
| --- | --- | --- | --- | --- |
| `substr(...)` | `substr(...)` in `php_string.hpp` | `str_substr(...)` | `substr(...)` | `stage1-now` | candidate for shared runtime extraction |
| `substr_compare(...)` | `substr_compare(...)` in `php_string.hpp` | `str_substr_compare(...)` | `substr_compare(...)` | `stage1-now` | candidate for shared runtime extraction |
| `substr_replace(...)` | `substr_replace(...)` in `php_string.hpp` | `str_substr_replace(...)` | `substr_replace(...)` | `stage1-now` | candidate for shared runtime extraction |
| `str_pad(...)` | `str_pad(...)` in `php_string.hpp` | `str_pad(...)` | `pad(...)` | `stage1-now` | candidate for shared runtime extraction |
| `str_replace(...)` | `str_replace(...)` in `php_string.hpp` | `str_replace(...)` | `replace(...)` | `stage1-now` | candidate for shared runtime extraction |
| `explode(...)` | `explode(...)` in `php_string.hpp` | `str_explode(...)` | `split(...)` | `stage1-now` | strict runtime should return typed collection, not `mixed_t` |
| `implode(...)` | `implode(...)` in `php_string.hpp` | `str_implode(...)` | `join(...)` | `stage1-now` | candidate for shared runtime extraction |
| `hex2bin(...)` | `hex2bin(...)` in `php_string.hpp` | `str_hex2bin(...)` | `hex_decode(...)` | `stage1-now` | strict runtime should avoid `mixed_t` if possible |
| `bin2hex(...)` | `bin2hex(...)` in `php_string.hpp` | `str_bin2hex(...)` | `hex_encode(...)` | `stage1-now` | candidate for shared runtime extraction |
| `number_format(...)` | `number_format(...)` in `php_string.hpp` | `str_number_format(...)` | `number_format(...)` | `stage1-now` | candidate for shared runtime extraction |
| `strlen(...)` | `strlen(...)` in `php_string.hpp` | `str_strlen(...)` | `length(...)` | `stage1-now` | candidate for shared runtime extraction |
| `strpos(...)` | `strpos(...)` in `php_string.hpp` | `str_strpos(...)` | `find(...)` | `stage1-now` | strict runtime should prefer typed not-found representation |
| `strrpos(...)` | `strrpos(...)` in `php_string.hpp` | `str_strrpos(...)` | `rfind(...)` | `stage1-now` | strict runtime should prefer typed not-found representation |
| `strtolower(...)` | `strtolower(...)` in `php_string.hpp` | `str_strtolower(...)` | `lower(...)` | `stage1-now` | candidate for shared runtime extraction |
| `strtoupper(...)` | `strtoupper(...)` in `php_string.hpp` | `str_strtoupper(...)` | `upper(...)` | `stage1-now` | candidate for shared runtime extraction |
| `lcfirst(...)` | `lcfirst(...)` in `php_string.hpp` | `str_lcfirst(...)` | `lcfirst(...)` | `stage1-now` | candidate for shared runtime extraction |
| `ucfirst(...)` | `ucfirst(...)` in `php_string.hpp` | `str_ucfirst(...)` | `ucfirst(...)` | `stage1-now` | candidate for shared runtime extraction |
| `str_starts_with(...)` | `str_starts_with(...)` in `php_string.hpp` | `str_starts_with(...)` | `starts_with(...)` | `stage1-now` | candidate for shared runtime extraction |
| `str_ends_with(...)` | `str_ends_with(...)` in `php_string.hpp` | `str_ends_with(...)` | `ends_with(...)` | `stage1-now` | candidate for shared runtime extraction |
| `trim(...)` | `trim(...)` in `php_string.hpp` | `str_trim(...)` | `trim(...)` | `stage1-now` | candidate for shared runtime extraction |
| `ltrim(...)` | `ltrim(...)` in `php_string.hpp` | `str_ltrim(...)` | `ltrim(...)` | `stage1-now` | candidate for shared runtime extraction |
| `rtrim(...)` | `rtrim(...)` in `php_string.hpp` | `str_rtrim(...)` | `rtrim(...)` | `stage1-now` | candidate for shared runtime extraction |

## Stdio / Resource I/O

Namespaces:

- PHP API column: `scpp::php::`
- current runtime API column: `scpp::php::`
- strict visible API column: flat prefixed names
- internal strict runtime column: `scpp::io::`

| PHP API (unchanged in Stage 1) | Current runtime API | Strict visible API | Internal strict runtime API | Status | Stage 1 note |
| --- | --- | --- | --- | --- |
| `fopen(...)` | `fopen(...)` in `php_stdio.hpp` | `io_open(...)` | `open(...)` | `stage1-now` | PHP wrapper likely stays falseable/resource-shaped |
| `fseek(...)` | `fseek(...)` in `php_stdio.hpp` | `io_seek(...)` | `seek(...)` | `stage1-now` | PHP wrapper likely stays PHP-shaped |
| `ftell(...)` | `ftell(...)` in `php_stdio.hpp` | `io_tell(...)` | `tell(...)` | `stage1-now` | PHP adapter converts result shape if needed |
| `fgets(...)` | `fgets(...)` in `php_stdio.hpp` | `io_read_line(...)` | `read_line(...)` | `stage1-now` | PHP adapter likely required |
| `fread(...)` | `fread(...)` in `php_stdio.hpp` | `io_read(...)` | `read(...)` | `stage1-now` | PHP adapter likely required |
| `fwrite(...)` | `fwrite(...)` in `php_stdio.hpp` | `io_write(...)` | `write(...)` | `stage1-now` | PHP adapter likely required |
| `fputs(...)` | `fputs(...)` in `php_stdio.hpp` | `io_write(...)` | `write(...)` | `stage1-now` | alias/wrapper over shared write |
| `rewind(...)` | `rewind(...)` in `php_stdio.hpp` | `io_rewind(...)` | `rewind(...)` | `stage1-now` | candidate for shared runtime extraction |
| `fflush(...)` | `fflush(...)` in `php_stdio.hpp` | `io_flush(...)` | `flush(...)` | `stage1-now` | candidate for shared runtime extraction |
| `feof(...)` | `feof(...)` in `php_stdio.hpp` | `io_eof(...)` | `eof(...)` | `stage1-now` | candidate for shared runtime extraction |
| `fclose(...)` | `fclose(...)` in `php_stdio.hpp` | `io_close(...)` | `close(...)` | `stage1-now` | candidate for shared runtime extraction |

## JSON

Namespaces:

- PHP API column: `scpp::php::`
- current runtime API column: `scpp::php::`
- strict visible API column: flat prefixed names
- internal strict runtime column: `scpp::json::`

| PHP API (unchanged in Stage 1) | Current runtime API | Strict visible API | Internal strict runtime API | Status | Stage 1 note |
| --- | --- | --- | --- | --- |
| `json_decode(json) -> mixed_t` | `json_decode(...)` via `php_json.cpp` | `json_decode(...) -> mixed_t` | `decode(...) -> mixed_t` | `stage1-now` | candidate for shared runtime/module extraction |
| `json_encode(value) -> string_t` | `json_encode(...)` via `php_json.cpp` | `json_encode(...) -> string_t` | `encode(...) -> string_t` | `stage1-now` | candidate for shared runtime/module extraction |

## PHP Semantic Operators / Support

Namespace:

- PHP API column: `scpp::php::`

| PHP API (unchanged in Stage 1) | Current runtime API | Proposed new runtime API | Status | Stage 1 note |
| --- | --- | --- | --- | --- |
| `count(...)` | `count(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already a semantic adapter family |
| `empty(...)` | `empty(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already PHP semantic |
| `isset(...)` | `isset(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already PHP semantic |
| `coalesce_eval(...)` | `coalesce_eval(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already shared semantic family underneath |
| `ternary_eval(...)` | `ternary_eval(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already shared semantic family underneath |
| `condition_truthy(...)` | `condition_truthy(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already shared semantic family underneath |
| `identical(...)` | `identical(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already shared semantic family underneath |
| `not_identical(...)` | `not_identical(...)` adapter over shared operator family | unchanged in Stage 1 | `leave-php-owned` | already shared semantic family underneath |
| `take(...)` | `take(...)` in `php_take.hpp` | unchanged in Stage 1 | `leave-php-owned` | PHP support helper, not first extraction target |
| `expect_array_argument(...)` | `expect_array_argument(...)` in `php_common.hpp` | unchanged in Stage 1 | `leave-php-owned` | PHP validation helper |
| `echo_eval(...)` | `echo_eval(...)` in `php_string.hpp` | unchanged in Stage 1 | `leave-php-owned` | PHP output helper |
| `var_dump(...)` | PHP-visible debug helper | unchanged in Stage 1 | `leave-php-owned` | PHP/debug-specific |

## Stage 1 Prioritization

Recommended order:

1. filesystem extraction
2. string extraction
3. stdio extraction
4. JSON extraction
5. leave PHP semantic operator/support families unchanged unless directly required
