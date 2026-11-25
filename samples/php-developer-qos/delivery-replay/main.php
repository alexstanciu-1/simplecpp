// Stream a delivery history and report the last successful time for each job.
$args = cli_arguments();
if (count($args) !== 2) { echo "usage: delivery-replay LOG\n"; return 2; }
$opened = fopen($args[1], "rb");
if (!isset($opened)) { echo "unreadable\n"; return 1; }
$file file_stream = $opened;
$latest hash<int,string> = [];
$retries = 0;
$invalid = 0;
while (true) {
    $next = fgets($file);
    if (!isset($next)) { break; }
    $line string = $next;
    $line = trim($line);
    if ($line === "") { continue; }
    $fields mixed = [];
    $matched = preg_match("/^([^ ]+) ([a-z0-9_-]+) (ok|retry)$/", $line, $fields);
    if (!isset($matched)) { fclose($file); echo "pattern error\n"; return 1; }
    $hits int = $matched;
    if ($hits === 0) { $invalid += 1; continue; }
    $stamp string = $fields[1];
    $parsed = dt_parse_iso_utc($stamp);
    if (!isset($parsed)) { $invalid += 1; continue; }
    $job string = $fields[2];
    $status string = $fields[3];
    if ($status === "retry") { $retries += 1; continue; }
    $seconds int = $parsed;
    if (!hash_contains($latest, $job) || $seconds > $latest[$job]) { $latest[$job] = $seconds; }
}
fclose($file);
foreach ($latest as $job => $seconds) { echo $job, " ", dt_format_iso_utc($seconds), "\n"; }
echo "retries=", $retries, " invalid=", $invalid, "\n";
return 0;
