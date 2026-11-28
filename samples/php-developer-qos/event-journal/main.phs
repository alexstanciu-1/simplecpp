// Append a single event while cooperating with other local writers.
$args = cli_arguments();
if (count($args) !== 3) { echo "usage: event-journal PATH EVENT\n"; return 2; }
$path = $args[1];
$event = $args[2];
if ($event === "" || strlen($event) > 512 || isset(strpos($event, "\n")) || isset(strpos($event, "\r"))) {
    echo "invalid event\n";
    return 2;
}
$lock = fs_lock_empty();
$attempt = fs_lock_try($lock, $path . ".lock", false);
if (!isset($attempt)) { echo "lock failed\n"; return 1; }
$acquired bool = $attempt;
if (!$acquired) { echo "busy\n"; return 3; }
$opened = fopen($path, "ab");
if (!isset($opened)) { fs_lock_release($lock); echo "open failed\n"; return 1; }
$file file_stream = $opened;
$line = $event . "\n";
$written = fwrite($file, $line);
$closed = fclose($file);
$released = fs_lock_release($lock);
if (!isset($written) || !$closed || !isset($released)) { echo "write failed\n"; return 1; }
$bytes int = $written;
if ($bytes !== strlen($line)) { echo "incomplete write\n"; return 1; }
echo $bytes, "\n";
return 0;
