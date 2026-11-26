// One flat form component. Malformed percent escapes are input errors.
function component(string $input): ?string {
    $out = "";
    $len = strlen($input);
    for ($i = 0; $i < $len; $i += 1) {
        $byte = substr($input, $i, 1);
        if ($byte === "+") { $out .= " "; continue; }
        if ($byte !== "%") { $out .= $byte; continue; }
        if ($i + 2 >= $len) { return null; }
        $hi = strpos("0123456789abcdef", strtolower(substr($input, $i + 1, 1)));
        $lo = strpos("0123456789abcdef", strtolower(substr($input, $i + 2, 1)));
        if (!isset($hi) || !isset($lo)) { return null; }
        $high int = $hi;
        $low int = $lo;
        $out .= string_byte_from_int($high * 16 + $low);
        $i += 2;
    }
    return $out;
}
