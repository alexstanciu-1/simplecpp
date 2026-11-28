// Preview two flat form fields from a raw URL-encoded body.
$args = cli_arguments();
if (count($args) !== 2) { echo "usage: form-preview BODY\n"; return 2; }
$raw = $args[1];
if (strlen($raw) > 4096) { echo "form too long\n"; return 1; }
$fields hash<string,string> = [];
foreach (explode("&", $raw) as $pair) {
    $parts = explode("=", $pair, 2);
    if (count($parts) !== 2) { echo "expected key=value\n"; return 1; }
    $decoded_key = component($parts[0]);
    $decoded_text = component($parts[1]);
    if (!isset($decoded_key) || !isset($decoded_text)) { echo "invalid escape\n"; return 1; }
    $key string = $decoded_key;
    if ($key !== "label" && $key !== "notify") { echo "unknown field\n"; return 1; }
    if (hash_contains($fields, $key)) { echo "duplicate field\n"; return 1; }
    $text string = $decoded_text;
    $fields[$key] = $text;
}
$label = hash_find($fields, "label") ?? "";
if ($label === "") { echo "label required\n"; return 1; }
$utf8 = preg_match("//u", $label);
if (!isset($utf8)) { echo "invalid UTF-8\n"; return 1; }
if (string_utf8_codepoint_count($label) > 80) { echo "label too long\n"; return 1; }
$notify = hash_find($fields, "notify") ?? "off";
if ($notify !== "on" && $notify !== "off") { echo "invalid notify\n"; return 1; }
$preview mixed = ["label" => $label, "notify" => $notify === "on"];
$json string = json_encode($preview);
echo $json, "\n";
return 0;
