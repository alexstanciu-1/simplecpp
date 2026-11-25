// Each accepted line represents one job in the queue snapshot.
$args = cli_arguments();
if (count($args) !== 2) {
    echo "usage: queue-report SNAPSHOT\n";
    return 2;
}
$source result<string> = fs_get($args[1]);
if (!isset($source)) {
    echo "unable to read snapshot\n";
    return 1;
}
$text string = $source;
$waiting = 0;
$running = 0;
$failed = 0;
$invalid = 0;
$failures hash<int,string> = [];
foreach (explode("\n", $text) as $line) {
    $row = trim($line);
    if ($row === "" || str_starts_with($row, "#")) continue;
    $fields = explode("|", $row);
    if (count($fields) !== 2 || $fields[0] === "") {
        $invalid += 1;
    } else if ($fields[1] === "waiting") {
        $waiting += 1;
    } else if ($fields[1] === "running") {
        $running += 1;
    } else if ($fields[1] === "failed") {
        $failed += 1;
        $queue = $fields[0];
        if (!hash_contains($failures, $queue)) $failures[$queue] = 0;
        $failures[$queue] += 1;
    } else {
        $invalid += 1;
    }
}
echo "waiting ", $waiting, " running ", $running, " failed ", $failed, " invalid ", $invalid, "\n";
foreach ($failures as $queue => $total) {
    echo $queue, " failures ", $total, "\n";
}
return 0;
