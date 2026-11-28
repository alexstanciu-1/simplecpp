// Group incoming request targets by the first path segment.
$targets = cli_argv();
$seen hash<int,string> = [];
$skipped = 0;
for ($i = 1; $i < cli_argc(); $i += 1) {
    $raw string = $targets[$i];
    $key = route_key($raw);
    if (!isset($key)) { $skipped += 1; continue; }
    $path string = $key;
    if (!hash_contains($seen, $path)) { $seen[$path] = 0; }
    $seen[$path] += 1;
}
foreach ($seen as $name => $hits) { echo "$name $hits\n"; }
echo "skipped $skipped\n";
return 0;
