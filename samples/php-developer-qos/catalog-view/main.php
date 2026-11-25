// Validate the entire catalog before publishing the first byte of HTML.
function read_product(json_node $node): ?Product {
    $label_node = json_node_member($node, "label");
    $units_node = json_node_member($node, "units");
    if (!isset($label_node) || !isset($units_node)) { return null; }
    $name_node json_node = $label_node;
    $label = json_node_string($name_node);
    $stock_node json_node = $units_node;
    $units = json_node_int($stock_node);
    if (!isset($label) || !isset($units)) { return null; }
    $name string = $label;
    $stock int = $units;
    if ($name === "" || $stock < 0) { return null; }
    return new Product($name, $stock);
}

$args = cli_arguments();
if (count($args) !== 2) { echo "usage: catalog-view FILE\n"; return 2; }
$input = fs_get($args[1]);
if (!isset($input)) { echo "unreadable\n"; return 1; }
$diagnostic ?json_parse_error = null;
$text string = $input;
$doc = json_document_parse($text, $diagnostic);
if (!isset($doc)) {
    $err json_parse_error = $diagnostic;
    echo "json ", $err->category, " at byte ", $err->byte_offset, "\n";
    return 1;
}
$document json_document = $doc;
$root json_node = json_document_root($document);
$kind string = json_node_kind($root);
if ($kind !== "array") { echo "expected array\n"; return 1; }
$rows vector<Product> = [];
$count int = json_node_size($root);
for ($i = 0; $i < $count; $i += 1) {
    $node json_node = json_node_at($root, $i);
    $row = read_product($node);
    if ($row === null) { echo "invalid product ", $i, "\n"; return 1; }
    $product Product = $row;
    $rows[] = $product;
}
echo "<ul>\n";
foreach ($rows as $product) { echo $product->render(); }
echo "</ul>\n";
return 0;
