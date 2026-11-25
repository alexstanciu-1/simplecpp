// Forward a JSON preference to a configured endpoint. Distinguish HTTP and transport errors.
$args = cli_args();
if (cli_argc() !== 3) { echo "usage: preference-client URL JSON\n"; return 2; }
$url string = $args[1];
$data string = $args[2];
$opened = curl_init($url);
if (!isset($opened)) { echo "client setup failed\n"; return 1; }
$client curl_handle = $opened;
$headers vector<string> = ["Content-Type: application/json"];
if (!curl_setopt($client, CURLOPT_HTTPHEADER, $headers)
    || !curl_setopt($client, CURLOPT_TIMEOUT, 3)
    || !curl_setopt($client, CURLOPT_POSTFIELDS, $data)) {
    echo "client options failed\n";
    curl_close($client);
    return 1;
}
$result = curl_exec($client);
if (!isset($result)) { curl_close($client); echo "transport error\n"; return 1; }
$code int = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
curl_close($client);
$body string = $result;
if ($code === 413) { echo "413 request too large\n"; return 1; }
$diag ?json_parse_error = null;
$decoded = json_document_parse($body, $diag);
if (!isset($decoded)) { echo "invalid response ", $code, "\n"; return 1; }
$doc json_document = $decoded;
$root json_node = json_document_root($doc);
if ($code !== 200) {
    $member = json_node_member($root, "error");
    if (isset($member)) {
        $node json_node = $member;
        $message = json_node_string($node);
        if (isset($message)) {
            $reason string = $message;
            echo $code, " ", $reason, "\n";
            return 1;
        }
    }
    echo "invalid response ", $code, "\n";
    return 1;
}
$scope_node = json_node_member($root, "scope");
$flag_node = json_node_member($root, "enabled");
if (isset($scope_node) && isset($flag_node)) {
    $name json_node = $scope_node;
    $enabled json_node = $flag_node;
    $scope_result = json_node_string($name);
    $flag_result = json_node_boolean($enabled);
    if (isset($scope_result) && isset($flag_result)) {
        $scope string = $scope_result;
        $flag bool = $flag_result;
        echo "$scope $flag\n";
        return 0;
    }
}
echo "invalid response ", $code, "\n";
return 1;
