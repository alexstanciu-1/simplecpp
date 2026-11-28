// Numeric fields in the input are bounded unsigned decimal integers. Accumulate
// only after checking the next digit so malformed input cannot overflow.
function decimal_field(string $text, int $maximum): int {
    $length = strlen($text);
    if ($length === 0) {
        return -1;
    }
    $number = 0;
    for ($i = 0; $i < $length; $i += 1) {
        $digit = (int)$text[$i] - 48;
        if ($digit < 0 || $digit > 9) {
            return -1;
        }
        if ($number > ($maximum - $digit) / 10 || $digit > $maximum) {
            return -1;
        }
        $number = $number * 10 + $digit;
    }
    return $number;
}

function identifier(string $text): bool {
    $size = strlen($text);
    if ($size === 0 || $size > 64) {
        return false;
    }
    for ($i = 0; $i < $size; $i += 1) {
        $byte = (int)$text[$i];
        $upper = $byte >= 65 && $byte <= 90;
        $lower = $byte >= 97 && $byte <= 122;
        $digit = $byte >= 48 && $byte <= 57;
        if (!$upper && !$lower && !$digit && $byte !== 45 && $byte !== 95) {
            return false;
        }
    }
    return true;
}

// Validate gateway configuration and resolve example requests against it.
// Backend and route definitions may be interleaved. Resolve references after
// parsing so moving a definition within a file does not change its meaning.
class Backend {
    public string $name;
    public string $host;
    public int $port;
    public bool $enabled;
    public int $uses = 0;
}

class RouteRule {
    public string $method;
    public string $path;
    public string $target;
    public bool $prefix;
    public int $line;
    public int $hits = 0;
    public bool $active = false;
}

class RequestCase {
    public string $method;
    public string $path;
}

class Validation {
    public int $errors = 0;
    public int $warnings = 0;
    public int $matched = 0;
    public int $unmatched = 0;
}

function valid_host(string $host): bool {
    if (strlen($host) > 253) {
        return false;
    }
    $labels = explode(".", $host);
    foreach ($labels as $label) {
        if (!identifier($label) || strlen($label) > 63 || str_contains($label, "_")) {
            return false;
        }
        if (str_starts_with($label, "-") || str_ends_with($label, "-")) {
            return false;
        }
    }
    return true;
}

function valid_path(string $path): bool {
    if (!str_starts_with($path, "/") || strlen($path) > 2048) {
        return false;
    }
    if (str_contains($path, "//") || str_contains($path, "?") || str_contains($path, "#")) {
        return false;
    }
    $parts = explode("/", $path);
    foreach ($parts as $part) {
        if ($part === "." || $part === "..") {
            return false;
        }
    }
    for ($i = 0; $i < strlen($path); $i += 1) {
        $byte = (int)$path[$i];
        if ($byte < 33 || $byte > 126 || $byte === 92 || $byte === 42) {
            return false;
        }
    }
    return true;
}

function valid_method(string $method): bool {
    return $method === "GET" || $method === "HEAD" || $method === "POST"
        || $method === "PUT" || $method === "DELETE" || $method === "PATCH";
}

function diagnostic(Validation $state, int $line, string $reason): void {
    $state->errors += 1;
    echo "error ", $line, " ", $reason, "\n";
}

function parse_backend(vector<string> &$fields, Backend $backend): bool {
    if (count($fields) !== 5) {
        return false;
    }
    $name = $fields[1];
    $host = strtolower($fields[2]);
    $port = decimal_field($fields[3], 65535);
    $flag = $fields[4];
    if (!identifier($name) || !valid_host($host) || $port <= 0) {
        return false;
    }
    if ($flag !== "on" && $flag !== "off") {
        return false;
    }
    $backend->name = $name;
    $backend->host = $host;
    $backend->port = $port;
    $backend->enabled = $flag === "on";
    return true;
}

function parse_rule(vector<string> &$fields, RouteRule $rule): bool {
    if (count($fields) !== 5) {
        return false;
    }
    $method = strtoupper($fields[1]);
    $pattern = $fields[2];
    $path = $fields[3];
    $backend = $fields[4];
    if ($method !== "ANY" && !valid_method($method)) {
        return false;
    }
    if ($pattern !== "exact" && $pattern !== "prefix") {
        return false;
    }
    if (!valid_path($path) || !identifier($backend)) {
        return false;
    }
    // A prefix ends at a segment boundary. Canonicalize trailing slashes
    // so duplicate prefix rules are caught before building the route table.
    if ($pattern === "prefix" && $path !== "/" && str_ends_with($path, "/")) {
        $path = substr($path, 0, strlen($path) - 1);
    }
    $rule->method = $method;
    $rule->path = $path;
    $rule->target = $backend;
    $rule->prefix = $pattern === "prefix";
    return true;
}

function parse_case(vector<string> &$fields, RequestCase $request): bool {
    if (count($fields) !== 3) {
        return false;
    }
    $method = strtoupper($fields[1]);
    $target = explode("?", $fields[2], 2);
    $path = $target[0];
    if (!valid_method($method) || !valid_path($path)) {
        return false;
    }
    $request->method = $method;
    $request->path = $path;
    return true;
}

function path_matches(RouteRule $rule, string $path): bool {
    if ($rule->path === $path) {
        return true;
    }
    if (!$rule->prefix || !str_starts_with($path, $rule->path)) {
        return false;
    }
    if ($rule->path === "/") {
        return true;
    }
    $boundary = strlen($rule->path);
    return strlen($path) > $boundary && $path[$boundary] === (byte)47;
}

// Longest path wins; exact wins ties over prefix, then a specific method
// wins over ANY. Rows with identical precedence and identity are duplicates.
function match_score(RouteRule $rule, RequestCase $request): int {
    if (!$rule->active || ($rule->method !== "ANY" && $rule->method !== $request->method)) {
        return -1;
    }
    if (!path_matches($rule, $request->path)) {
        return -1;
    }
    $score = strlen($rule->path) * 4;
    if (!$rule->prefix) {
        $score += 2;
    }
    if ($rule->method !== "ANY") {
        $score += 1;
    }
    return $score;
}

function resolve_rules(vector<RouteRule> &$rules, vector<Backend> &$backends,
    hash<int,string> &$positions, Validation $state): void {
    foreach ($rules as $rule) {
        $name = $rule->target;
        if (!hash_contains($positions, $name)) {
            diagnostic($state, $rule->line, "unknown-backend");
        } else {
            $backend = $backends[$positions[$name]];
            if ($backend->enabled) {
                $backend->uses += 1;
                $rule->active = true;
            } else {
                $state->warnings += 1;
                echo "warning ", $rule->line, " disabled-backend\n";
            }
        }
    }
}

function dispatch(RequestCase $request, vector<RouteRule> &$rules, Validation $state): void {
    $best = -1;
    $index = -1;
    for ($i = 0; $i < count($rules); $i += 1) {
        $rule = $rules[$i];
        $score = match_score($rule, $request);
        if ($score > $best) {
            $index = $i;
            $best = $score;
        }
    }
    if ($index < 0) {
        $state->unmatched += 1;
        echo "unmatched ", $request->method, " ", $request->path, "\n";
    } else {
        $chosen = $rules[$index];
        $chosen->hits += 1;
        $state->matched += 1;
        echo "match ", $request->method, " ", $request->path, " ", $chosen->target, "\n";
    }
}

function analyze(string &$input): void {
    $rows = explode("\n", $input);
    $backends vector<Backend> = [];
    $rules vector<RouteRule> = [];
    $requests vector<RequestCase> = [];
    $positions hash<int,string> = [];
    $identities hash<int,string> = [];
    $state = new Validation();
    $line = 0;
    foreach ($rows as $row) {
        $line += 1;
        $text = trim($row);
        if ($text !== "" && !str_starts_with($text, "#")) {
            $tokens = explode(" ", $text);
            $fields vector<string> = [];
            foreach ($tokens as $token) {
                if ($token !== "") {
                    $fields[] = $token;
                }
            }
            $directive = $fields[0];
            if ($directive === "backend") {
                $backend = new Backend();
                if (!parse_backend($fields, $backend)) {
                    diagnostic($state, $line, "invalid-backend");
                } else if (hash_contains($positions, $backend->name)) {
                    diagnostic($state, $line, "duplicate-backend");
                } else {
                    $positions[$backend->name] = count($backends);
                    $backends[] = $backend;
                }
            } else if ($directive === "route") {
                $rule = new RouteRule();
                $rule->line = $line;
                if (!parse_rule($fields, $rule)) {
                    diagnostic($state, $line, "invalid-route");
                } else {
                    $key = $rule->method . " " . $fields[2] . " " . $rule->path;
                    if (hash_contains($identities, $key)) {
                        diagnostic($state, $line, "duplicate-route");
                    } else {
                        $identities[$key] = $line;
                        $rules[] = $rule;
                    }
                }
            } else if ($directive === "test") {
                $request = new RequestCase();
                if (parse_case($fields, $request)) {
                    $requests[] = $request;
                } else {
                    diagnostic($state, $line, "invalid-request");
                }
            } else {
                diagnostic($state, $line, "unknown-directive");
            }
        }
    }
    resolve_rules($rules, $backends, $positions, $state);
    foreach ($requests as $request) {
        dispatch($request, $rules, $state);
    }
    foreach ($backends as $backend) {
        echo "backend ", $backend->name, " ", $backend->host, ":", $backend->port, " routes ", $backend->uses, "\n";
    }
    echo "matched ", $state->matched, " unmatched ", $state->unmatched,
        " errors ", $state->errors, " warnings ", $state->warnings, "\n";
}

$source result<string> = fs_get("input.txt");
if (!isset($source)) {
    echo "unable to read input.txt\n";
    return 2;
}
$input string = $source;
analyze($input);
return 0;
