function route_key(string $target): ?string {
    $end = strpos($target, "?");
    $path = $target;
    if (isset($end)) { $length int = $end; $path = substr($target, 0, $length); }
    if (!str_starts_with($path, "/")) { return null; }
    $parts = explode("/", $path);
    if (count($parts) < 2 || $parts[1] === "") { return "/"; }
    return "/" . $parts[1];
}
