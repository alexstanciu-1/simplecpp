// Generated shallow runtime symbol surface for STAN.
// Profile: legacy
// This file is for front-end symbol extraction only.
// Skipped reserved or unsafe names: empty, isset

function __scpp_debug_break(): void {}
function __scpp_debug_call_entry(): void {}
function __scpp_debug_dump(string $phase, string $label, mixed $value): void {}
function __scpp_debug_exit(): void {}
function basename(string $path): string {}
function bin2hex(string $bytes): string {}
function cli_argc(): int {}
function cli_args(): mixed {}
function cli_argv(): mixed {}
function coalesce_eval(): mixed {}
function condition_truthy(mixed $value): bool {}
function copy(string $from, string $to): bool {}
function count(mixed $value): int {}
function curl_close(curl_handle $handle) /** result_or_false<bool> */ {}
function curl_errno(curl_handle $handle): int {}
function curl_error(curl_handle $handle): string {}
function curl_exec(curl_handle $handle): mixed {}
function curl_getinfo(curl_handle $handle): mixed {}
function curl_init() /** result_or_false<curl_handle> */ {}
function curl_reset(curl_handle $handle): void {}
function curl_setopt(curl_handle $handle, int $option, mixed $value) /** result_or_false<bool> */ {}
function curl_strerror(int $code): string {}
function date(string $format): string {}
function dbg(mixed $value): void {}
function dbg_enabled(int $flag): bool {}
function dbg_if(bool $flag, mixed $value): void {}
function dbg_set(int $flag): void {}
function dbg_unset(int $flag): void {}
function dirname(string $path): string {}
function dt_format(int $stamp, string $format): string {}
function dt_format_iso_utc(int $stamp): string {}
function dt_format_now(string $format): string {}
function dt_monotonic_ms(): int {}
function dt_now(): int {}
function dt_now_ms(): int {}
function dt_parse(string $text) /** result<int> */ {}
function dt_parse_iso_utc(string $text) /** result<int> */ {}
function dt_sleep_ms(int $millis): void {}
function echo_eval(mixed $value): void {}
function echo_one(mixed $value): void {}
function expect_array_argument(): mixed {}
function explode(string $separator, string $text) /** vector<string> */ {}
function fclose(mixed $fh): mixed {}
function feof(mixed $fh): bool {}
function fflush(mixed $fh): mixed {}
function fgets(mixed $fh): mixed {}
function file_exists(string $path): bool {}
function file_get_contents(string $path): mixed {}
function file_put_contents(string $path, string $data): mixed {}
function filemtime(string $path): mixed {}
function filesize(string $path): mixed {}
function fopen(string $path, string $mode): mixed {}
function fputs(mixed $fh, string $data): mixed {}
function fread(mixed $fh, int $length): mixed {}
function fseek(mixed $fh, int $offset): mixed {}
function ftell(mixed $fh): mixed {}
function fwrite(mixed $fh, string $data): mixed {}
function hash_string(string $text): string {}
function hex2bin(string $hex) /** result_or_false<string> */ {}
function identical(mixed $left, mixed $right): bool {}
function implode(string $separator, mixed $parts): string {}
function is_dir(string $path): bool {}
function is_file(string $path): bool {}
function is_link(string $path): bool {}
function isset_eval(): bool {}
function js_plus(): mixed {}
function json_decode(string $json): dynamic {}
function json_encode(mixed $value): string {}
function jss_tokenize(string $source): token_buffer {}
function jss_tokenize_buffer(string $source): token_buffer {}
function lcfirst(string $text): string {}
function ltrim(string $text): string {}
function mkdir(string $path): bool {}
function not_identical(mixed $left, mixed $right): bool {}
function number_format(float $value): string {}
function phs_tokenize(string $source): token_buffer {}
function phs_tokenize_buffer(string $source): token_buffer {}
function preg_filter(string $pattern, string $replacement, string $subject): mixed {}
function preg_grep(string $pattern, mixed $input): mixed {}
function preg_jit_available(): bool {}
function preg_match(string $pattern, string $subject) /** result_or_false<int> */ {}
function preg_match_all(string $pattern, string $subject) /** result_or_false<int> */ {}
function preg_quote(string $text): string {}
function preg_replace(string $pattern, string $replacement, string $subject): mixed {}
function preg_replace_callback(string $pattern, string $subject): mixed {}
function preg_replace_callback_array(string $subject): mixed {}
function preg_split(string $pattern, string $subject): mixed {}
function realpath(string $path): mixed {}
function rename(string $from, string $to): bool {}
function rewind(mixed $fh): mixed {}
function rmdir(string $path): bool {}
function rtrim(string $text): string {}
function scandir(string $path): mixed {}
function shell_exec(string $command): mixed {}
function str_ends_with(string $text, string $suffix): bool {}
function str_pad(string $text, int $length): string {}
function str_replace(string $search, string $replace, string $subject): string {}
function str_starts_with(string $text, string $prefix): bool {}
function string_byte_at(string $text, int $offset): int {}
function string_byte_find(string $haystack, string $needle, int $offset = 0) /** result_or_false<int> */ {}
function string_byte_len(string $text): int {}
function string_byte_slice(string $text, int $offset, int $length): string {}
function string_byte_slice_equals(string $text, int $offset, int $length, string $literal): bool {}
function string_grapheme_count(string $text): int {}
function string_grapheme_slice(string $text, int $start, int $length): string {}
function string_utf8_codepoint_at(string $text, int $index): int {}
function string_utf8_codepoint_count(string $text): int {}
function string_utf8_slice_codepoints(string $text, int $start, int $length): string {}
function strlen(string $text): int {}
function strpos(string $haystack, string $needle) /** result_or_false<int> */ {}
function strrpos(string $haystack, string $needle) /** result_or_false<int> */ {}
function strtolower(string $text): string {}
function strtotime(string $text): mixed {}
function strtoupper(string $text): string {}
function substr(string $text, int $offset, int $length = 0): string {}
function substr_compare(string $main, string $str, int $offset, int $length = 0): int {}
function substr_replace(string $text, string $replace, int $offset, int $length = 0): string {}
function take(mixed $out, mixed $source): bool {}
function ternary_eval(): mixed {}
function time(): int {}
function to_dynamic(): mixed {}
function to_hash(mixed $value) /** hash<mixed> */ {}
function token_buffer_count(token_buffer $buffer): int {}
function token_buffer_kind_id(token_buffer $buffer, int $index): int {}
function token_buffer_length(token_buffer $buffer, int $index): int {}
function token_buffer_start_offset(token_buffer $buffer, int $index): int {}
function token_buffer_to_mixed(token_buffer $buffer): mixed {}
function touch(string $path): bool {}
function trim(string $text): string {}
function ucfirst(string $text): string {}
function unlink(string $path): bool {}
function var_dump(mixed $value): void {}
function vector_capacity(): mixed {}
function vector_clear(): mixed {}
function vector_compact(): mixed {}
function vector_reserve(): mixed {}

namespace scpp;

class mysqli_result
{
	public function fetch_assoc(): dynamic {}
}

class mysqli
{
	public int $connect_errno;
	public string $connect_error;
	public int $errno_code;
	public string $error;
	public function query(string $sql) /** result_or_bool<scpp\mysqli_result> */ {}
	public function close(): void {}
	public function set_charset(string $charset): bool {}
}
