// Apply a patch to defaults. Omitting limit preserves 10; sending null clears it.
function apply_patch(json_node $root, bool &$enabled, ?int &$limit): void {
    $kind string = json_node_kind($root);
    if ($kind !== "object") { throw new Exception("expected object"); }
    $count int = json_node_size($root);
    for ($i = 0; $i < $count; $i += 1) {
        $key string = json_node_key($root, $i);
        $node json_node = json_node_member($root, $key);
        if ($key === "enabled") {
            $flag = json_node_boolean($node);
            if (!isset($flag)) { throw new Exception("invalid enabled"); }
            $enabled = $flag;
        } else if ($key === "limit") {
            $type string = json_node_kind($node);
            if ($type === "null") { $limit = null; continue; }
            $integer = json_node_int($node);
            if (!isset($integer)) { throw new Exception("invalid limit"); }
            $new_limit int = $integer;
            if ($new_limit < 0 || $new_limit > 100) { throw new Exception("invalid limit"); }
            $limit = $new_limit;
        } else { throw new Exception("unknown field"); }
    }
}

$args = cli_arguments();
if (count($args) !== 2) { echo "usage: settings-patch JSON\n"; return 2; }
$diag ?json_parse_error = null;
$input = json_document_parse($args[1], $diag);
if (!isset($input)) { echo "invalid JSON\n"; return 1; }
$doc json_document = $input;
$node json_node = json_document_root($doc);
$enabled = true;
$limit ?int = 10;
try { apply_patch($node, $enabled, $limit); }
catch (Exception $e) { echo $e->get_message(), "\n"; return 1; }
$output mixed = ["enabled" => $enabled, "limit" => null];
if (isset($limit)) {
    $number int = $limit;
    $output["limit"] = $number;
}
$text string = json_encode($output);
echo $text, "\n";
return 0;
