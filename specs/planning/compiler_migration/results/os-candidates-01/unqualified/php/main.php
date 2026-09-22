<?php
declare(strict_types=1);
// <scpp-imports>
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>
$handle = lock_empty();
echo lock_try($handle, "/tmp/scpp-os-native-01/shared.lock", false) ? "locked\n" : "bad\n";
$alias = $handle;
$other = lock_empty();
echo lock_try($other, "/tmp/scpp-os-native-01/shared.lock", false) ? "bad\n" : "contended\n";
$moved = lock_transfer($handle);
lock_release($alias);
echo lock_try($other, "/tmp/scpp-os-native-01/shared.lock", true) ? "bad\n" : "transfer-held\n";
lock_release($moved); lock_release($moved);
echo lock_try($other, "/tmp/scpp-os-native-01/shared.lock", true) ? "shared\n" : "bad\n";
$reader = lock_empty(); echo lock_try($reader, "/tmp/scpp-os-native-01/shared.lock", true) ? "shared-pair\n" : "bad\n";
lock_release($other); lock_release($reader);
try { lock_transfer($moved); } catch (\RuntimeException $error) { echo "released-transfer-error\n"; }
try { lock_try($other, "", false); } catch (\RuntimeException $error) { echo "path-error\n"; }
$args /** vector<string> */ = ["-c", "cat; printf err >&2; exit 7"];
$p = process_spawn("/bin/sh", $args, "input", 1000, "");
$alias_process = $p;
for ($i = 0; $i < 1000000; $i++) { if (process_poll($p)) { break; } }
$out = process_output($p);
echo $out->stdout_text, ":", $out->stderr_text, ":", $out->exit_code, "\n";
$out->stdout_text = "edited";
$again = process_output($p); echo $again->stdout_text, "\n";
process_close($alias_process); process_close($p);
try { process_poll($p); } catch (\RuntimeException $error) { echo "closed-error\n"; }
$missing_args /** vector<string> */ = [];
try { process_spawn("/not/a/program", $missing_args, "", 0, ""); } catch (\RuntimeException $error) { echo "exec-error\n"; }
$args_127 /** vector<string> */ = ["-c", "exit 127"];
$p127 = process_spawn("/bin/sh", $args_127, "", 1000, "");
for ($i = 0; $i < 1000000; $i++) { if (process_poll($p127)) { break; } }
$out127 = process_output($p127); echo $out127->exit_code, "\n";process_close($p127);
$sleep_args /** vector<string> */ = ["10"];
$slow = process_spawn("/bin/sleep", $sleep_args, "", 10, "");
try { process_output($slow); } catch (\RuntimeException $error) { echo "pending-error\n"; }
for ($i = 0; $i < 1000000; $i++) { if (process_poll($slow)) { break; } }
$timed = process_output($slow);
echo $timed->timed_out ? "timeout" : "bad", ":", $timed->signal, "\n";process_close($slow);
$stop = process_spawn("/bin/sleep", $sleep_args, "", 0, "");process_stop($stop);process_stop($stop);
for ($i = 0; $i < 1000000; $i++) { if (process_poll($stop)) { break; } }
$stopped = process_output($stop);echo $stopped->stopped ? "stopped" : "bad", ":", $stopped->signal, "\n";process_close($stop);
