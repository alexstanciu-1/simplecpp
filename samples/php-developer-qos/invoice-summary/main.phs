// Read a pipe-delimited snapshot. Amounts are whole cents.
function amount_cents(string $text): ?int {
    if ($text === "" || strlen($text) > 8) { return null; }
    $amount = 0;
    for ($i = 0; $i < strlen($text); $i += 1) {
        $digit = substr($text, $i, 1);
        $match = strpos("0123456789", $digit);
        if (!isset($match)) { return null; }
        $digit_value int = $match;
        $amount = $amount * 10 + $digit_value;
    }
    return $amount;
}
$args = cli_args();
if (count($args) !== 2) { echo "usage: invoice-summary FILE\n"; return 2; }
$path string = $args[1];
$file = file_get_contents($path);
if (!isset($file)) { echo "unreadable\n"; return 1; }
$text string = $file;
$accounts hash<int,string> = [];
$invalid = 0;
foreach (explode("\n", $text) as $line) {
    if ($line === "\r" || $line === "") { continue; }
    $fields = explode("|", $line);
    if (count($fields) !== 2 || $fields[0] === "") { $invalid += 1; continue; }
    $amount = amount_cents(trim($fields[1]));
    if (!isset($amount)) { $invalid += 1; continue; }
    $account = $fields[0];
    if (!hash_contains($accounts, $account)) { $accounts[$account] = 0; }
    $cents int = $amount;
    $accounts[$account] += $cents;
}
foreach ($accounts as $account => $total) {
    echo $account, "=", $total, "\n";
}
echo "invalid=", $invalid, "\n";
return 0;
