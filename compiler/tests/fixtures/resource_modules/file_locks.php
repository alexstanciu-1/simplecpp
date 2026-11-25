function acquire(file_lock_handle &$slot, string $path): bool {
    return fs_lock_try($slot, $path);
}
$args = cli_arguments();
$mode = $args[1];
$path = $args[2];
$lock = fs_lock_empty();
if ($mode === "writer" || $mode === "reader") {
    $attempt = fs_lock_try($lock, $path, $mode === "reader");
    if (!isset($attempt)) { return 2; }
    $won bool = $attempt;
    if (!$won) { return 3; }
    return 0;
}
$before = $lock;
if (!acquire($lock, $path)) { return 4; }
if (isset(fs_lock_try($lock, $path))) { return 5; }
if (isset(fs_lock_transfer($before))) { return 6; }
$released bool = fs_lock_release($before);
$alias = $lock;
$next file_lock_handle = fs_lock_transfer($alias);
if (!$released || isset(fs_lock_transfer($lock))) { return 7; }
$old bool = fs_lock_release($alias);
if (!$old) { return 8; }
$ready int = fs_put("ready", "held");
$go string = fs_get("/dev/stdin");
if ($ready !== 4 || $go !== "release") { return 9; }
$done bool = fs_lock_release($next);
$again bool = fs_lock_release($next);
if (!$done || !$again || isset(fs_lock_transfer($next))) { return 10; }
if (isset(fs_lock_try($alias, "")) || isset(fs_lock_try($alias, "x\0bad"))) { return 11; }
if (isset(fs_lock_try($alias, ".")) || isset(fs_lock_try($alias, "missing", true))) { return 12; }
$first bool = fs_lock_try($alias, $path, true);
$second bool = fs_lock_try($next, $path, true);
$denied bool = fs_lock_try($lock, $path);
if (!$first || !$second || $denied) { return 13; }
return 0;
