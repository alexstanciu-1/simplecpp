<?php
declare(strict_types=1);

// Generated global facade. Owned by function_map.php; no per-file imports.
function weakref_get(?object $value): ?object { return \scpp\weakref_get($value); }
function object_cast(?object $value, string $target): object { return \scpp\object_cast($value, $target); }
function dt_sleep_ms(int $millis): void { \scpp\dt_sleep_ms($millis); }
function fs_read_snapshot(string $path, int $expected_mtime, int $expected_size): string { return \scpp\fs_read_snapshot($path, $expected_mtime, $expected_size); }
function fs_is_windows(): bool { return \scpp\fs_is_windows(); }
function fs_basename(string $path): string { return \scpp\fs_basename($path); }
function fs_dirname(string $path): string { return \scpp\fs_dirname($path); }
function fs_read_text(string $path): string { return \scpp\fs_read_text($path); }
function fs_require_realpath(string $path): string { return \scpp\fs_require_realpath($path); }
function json_read(string $text): \scpp\Json_View { return \scpp\json_read($text); }
function sequence_require_strings(array $items, string $shape_error, string $element_error): void { \scpp\sequence_require_strings($items, $shape_error, $element_error); }
function fs_is_link(string $path): bool { return \scpp\fs_is_link($path); }
function fs_is_dir(string $path): bool { return \scpp\fs_is_dir($path); }
function fs_is_file(string $path): bool { return \scpp\fs_is_file($path); }
function fs_size(string $path): int|false { return \scpp\fs_size($path); }
function fs_mtime(string $path): int|false { return \scpp\fs_mtime($path); }
function fs_scan(string $path): array|false { return \scpp\fs_scan($path); }
function json_quote(string $text): string { return \scpp\json_quote($text); }
function string_byte_from_int(int $value): string { return \scpp\string_byte_from_int($value); }
function enum_name(\UnitEnum $value): string { return \scpp\enum_name($value); }
function lock_empty(): \scpp\File_Lock { return \scpp\lock_empty(); }
function lock_try(\scpp\File_Lock &$out, string $path, bool $shared): bool { return \scpp\lock_try($out, $path, $shared); }
function lock_release(\scpp\File_Lock $handle): void { \scpp\lock_release($handle); }
function lock_transfer(\scpp\File_Lock $handle): \scpp\File_Lock { return \scpp\lock_transfer($handle); }
function process_spawn(string $executable, array $args, string $input, int $timeout_ms, string $cwd): \scpp\Process_Handle { return \scpp\process_spawn($executable, $args, $input, $timeout_ms, $cwd); }
function process_poll(\scpp\Process_Handle $handle): bool { return \scpp\process_poll($handle); }
function process_output(\scpp\Process_Handle $handle): \scpp\Process_Output { return \scpp\process_output($handle); }
function process_stop(\scpp\Process_Handle $handle): void { \scpp\process_stop($handle); }
function process_close(\scpp\Process_Handle $handle): void { \scpp\process_close($handle); }
function sequence_map(array $input, \Closure $callback): array { return \scpp\sequence_map($input, $callback); }
function sequence_filter(array $input, \Closure $predicate): array { return \scpp\sequence_filter($input, $predicate); }
function keyed_map(array $input, \Closure $callback): array { return \scpp\keyed_map($input, $callback); }
function keyed_filter(array $input, \Closure $predicate): array { return \scpp\keyed_filter($input, $predicate); }
function string_byte_len(string $text): int { return \scpp\string_byte_len($text); }
function string_byte_starts_with(string $text, string $prefix): bool { return \scpp\string_byte_starts_with($text, $prefix); }
function string_byte_ends_with(string $text, string $suffix): bool { return \scpp\string_byte_ends_with($text, $suffix); }
function string_utf8_is_valid(string $text): bool { return \scpp\string_utf8_is_valid($text); }
function string_codepoint_at(string $text, int $index): int { return \scpp\string_codepoint_at($text, $index); }
function q_substr(string $text, int $offset, int $length = \PHP_INT_MAX): string { return \scpp\compat\substr($text, $offset, $length); }
function q_strpos(string $text, string $needle, int $offset = 0): int|false { return \scpp\compat\strpos($text, $needle, $offset); }
function q_strrpos(string $text, string $needle, int $offset = 0): int|false { return \scpp\compat\strrpos($text, $needle, $offset); }
function same_exception(\Throwable $left, \Throwable $right): bool { return \scpp\same_exception($left, $right); }
function string_byte_at(string $value, int $offset): int { return \scpp\string_byte_at($value, $offset); }
function q_count(\Countable|array $value): int { return \count($value); }
function take_nullable(mixed &$out, mixed $value): bool { return \scpp\take_nullable($out, $value); }
function take_false(mixed &$out, mixed $value): bool { return \scpp\take_false($out, $value); }
function take_bool(mixed &$out, bool &$flag, mixed $value): bool { return \scpp\take_bool($out, $flag, $value); }
function q_is_int(mixed $value): bool { return \is_int($value); }
function q_is_bool(mixed $value): bool { return \is_bool($value); }
function q_str_starts_with(string $text, string $prefix): bool { return \scpp\compat\str_starts_with($text, $prefix); }
function q_str_ends_with(string $text, string $suffix): bool { return \scpp\compat\str_ends_with($text, $suffix); }
function q_strlen(string $text): int { return \scpp\compat\strlen($text); }
function string_byte_slice(string $value, int $offset, int $length): string { return \scpp\string_byte_slice($value, $offset, $length); }
