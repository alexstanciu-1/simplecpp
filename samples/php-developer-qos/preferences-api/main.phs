// One boolean preference per request. An absent query uses the default scope.
function respond(fcgi_exchange $exchange, fcgi_request $request): void {
    if ($request->path !== "/preference") {
        fcgi_reply($exchange, 404, "{\"error\":\"route\"}");
        return;
    }
    if ($request->method !== "POST") {
        fcgi_response_header($exchange, "Allow", "POST");
        fcgi_reply($exchange, 405, "{\"error\":\"method\"}");
        return;
    }
    $scope = "default";
    if ($request->query_string === "scope=team") { $scope = "team"; }
    else if ($request->query_string !== "") {
        fcgi_reply($exchange, 400, "{\"error\":\"scope\"}");
        return;
    }
    $diagnostic ?json_parse_error = null;
    $parsed = json_document_parse($request->body, $diagnostic);
    if (!isset($parsed)) { fcgi_reply($exchange, 400, "{\"error\":\"syntax\"}"); return; }
    $document json_document = $parsed;
    $node json_node = json_document_root($document);
    $value = json_node_boolean($node);
    if (!isset($value)) { fcgi_reply($exchange, 422, "{\"error\":\"expected boolean\"}"); return; }
    $enabled bool = $value;
    $response mixed = ["scope" => $scope, "enabled" => $enabled];
    $body string = json_encode($response);
    fcgi_reply($exchange, 200, $body);
}
$args = cli_arguments();
$once = count($args) === 3 && $args[2] === "--once";
if (!$once && count($args) !== 2) { echo "usage: preferences-api SOCKET [--once]\n"; return 2; }
$listener = fcgi_listen($args[1], 4096);
$handled = 0;
while (true) {
    $pending = fcgi_accept($listener, 200);
    if (!isset($pending)) { continue; }
    $exchange fcgi_exchange = $pending;
    $handled += 1;
    fcgi_response_header($exchange, "X-Request-Number", "$handled");
    $request = fcgi_request_read($exchange);
    respond($exchange, $request);
    if ($once) { break; }
}
fcgi_close($listener);
return 0;
