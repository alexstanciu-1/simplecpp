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

function dispatch(RequestCase $request, vector<RouteRule> &$rules, Validation $state, Dashboard $book, bool $details): void {
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
    if ($details) {
        record_resolution($book, $request, $rules, $index);
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

function process_input(string &$input, Dashboard $book, bool $details): void {
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
        dispatch($request, $rules, $state, $book, $details);
    }
    foreach ($backends as $backend) {
        echo "backend ", $backend->name, " ", $backend->host, ":", $backend->port, " routes ", $backend->uses, "\n";
    }
    echo "matched ", $state->matched, " unmatched ", $state->unmatched,
        " errors ", $state->errors, " warnings ", $state->warnings, "\n";
}


// Accumulate only occupied report cells, preserving their first-observed order.
// The metric named in each key determines the unit of sum, minimum and maximum.
class MetricCell {
    public string $key;
    public int $samples = 0;
    public int $total = 0;
    public int $low = 0;
    public int $high = 0;
}

class Dashboard {
    public hash<int,string> $positions;
    public vector<MetricCell> $cells;
}

function observe(Dashboard $book, string $key, int $value): void {
    if (!hash_contains($book->positions, $key)) {
        $fresh = new MetricCell();
        $fresh->key = $key;
        $fresh->low = $value;
        $fresh->high = $value;
        $book->positions[$key] = count($book->cells);
        $book->cells[] = $fresh;
    }
    $index = $book->positions[$key];
    $cell = $book->cells[$index];
    $cell->samples += 1;
    $cell->total += $value;
    if ($value < $cell->low) {
        $cell->low = $value;
    }
    if ($value > $cell->high) {
        $cell->high = $value;
    }
}

function print_dashboard(Dashboard $book): void {
    echo "dashboard ", count($book->cells), " occupied cells\n";
    foreach ($book->cells as $cell) {
        echo $cell->key, " ", $cell->samples, " ", $cell->total, " ",
            $cell->low, " ", $cell->high, "\n";
    }
}

class ReportEvent {
    public string $method;
    public string $resolution;
    public string $scope;
    public int $depth;
    public int $path_bytes;
    public int $covered;
    public int $candidates;
    public int $matched_bytes;
}

function record_resolution(Dashboard $book, RequestCase $request, vector<RouteRule> &$rules, int $selected): void {
    $event = new ReportEvent();
    $event->method = $request->method;
    $event->depth = substr_count($request->path, "/");
    $event->path_bytes = strlen($request->path);
    $event->candidates = count($rules);
    $event->resolution = "unmatched";
    $event->scope = "missing";
    if ($selected >= 0) {
        $chosen = $rules[$selected];
        $event->matched_bytes = strlen($chosen->path);
        $event->covered = 100 * $event->matched_bytes / $event->path_bytes;
        $event->resolution = "exact";
        if ($chosen->prefix) {
            $event->resolution = "prefix";
        }
        $event->scope = "method";
        if ($chosen->method === "ANY") {
            $event->scope = "any";
        }
    }
    update_dashboard($book, $event);
}

function path_bytes_by_method(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        observe($book, "path_bytes/method=get", $event->path_bytes);
    } else if ($event->method === "HEAD") {
        observe($book, "path_bytes/method=head", $event->path_bytes);
    } else if ($event->method === "POST") {
        observe($book, "path_bytes/method=post", $event->path_bytes);
    } else if ($event->method === "PUT") {
        observe($book, "path_bytes/method=put", $event->path_bytes);
    } else if ($event->method === "PATCH") {
        observe($book, "path_bytes/method=patch", $event->path_bytes);
    } else if ($event->method === "DELETE") {
        observe($book, "path_bytes/method=delete", $event->path_bytes);
    }
}

function matched_bytes_by_resolution(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        observe($book, "matched_bytes/resolution=unmatched", $event->matched_bytes);
    } else if ($event->resolution === "exact") {
        observe($book, "matched_bytes/resolution=exact", $event->matched_bytes);
    } else if ($event->resolution === "prefix") {
        observe($book, "matched_bytes/resolution=prefix", $event->matched_bytes);
    }
}

function depth_by_scope(Dashboard $book, ReportEvent $event): void {
    if ($event->scope === "missing") {
        observe($book, "depth/scope=missing", $event->depth);
    } else if ($event->scope === "method") {
        observe($book, "depth/scope=method", $event->depth);
    } else if ($event->scope === "any") {
        observe($book, "depth/scope=any", $event->depth);
    }
}

function candidates_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->depth <= 1) {
        observe($book, "candidates/depth=root", $event->candidates);
    } else if ($event->depth <= 2) {
        observe($book, "candidates/depth=resource", $event->candidates);
    } else {
        observe($book, "candidates/depth=nested", $event->candidates);
    }
}

function path_bytes_by_path_size(Dashboard $book, ReportEvent $event): void {
    if ($event->path_bytes <= 8) {
        observe($book, "path_bytes/path_size=short", $event->path_bytes);
    } else if ($event->path_bytes <= 24) {
        observe($book, "path_bytes/path_size=medium", $event->path_bytes);
    } else if ($event->path_bytes <= 64) {
        observe($book, "path_bytes/path_size=long", $event->path_bytes);
    } else {
        observe($book, "path_bytes/path_size=extended", $event->path_bytes);
    }
}

function matched_bytes_by_coverage(Dashboard $book, ReportEvent $event): void {
    if ($event->covered <= 0) {
        observe($book, "matched_bytes/coverage=none", $event->matched_bytes);
    } else if ($event->covered <= 50) {
        observe($book, "matched_bytes/coverage=partial", $event->matched_bytes);
    } else if ($event->covered <= 99) {
        observe($book, "matched_bytes/coverage=most", $event->matched_bytes);
    } else {
        observe($book, "matched_bytes/coverage=complete", $event->matched_bytes);
    }
}

function depth_by_table(Dashboard $book, ReportEvent $event): void {
    if ($event->candidates <= 0) {
        observe($book, "depth/table=empty", $event->depth);
    } else if ($event->candidates <= 8) {
        observe($book, "depth/table=small", $event->depth);
    } else if ($event->candidates <= 64) {
        observe($book, "depth/table=medium", $event->depth);
    } else {
        observe($book, "depth/table=large", $event->depth);
    }
}

function path_bytes_by_method_by_resolution(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=get/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=get/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=get/resolution=prefix", $event->path_bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=head/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=head/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=head/resolution=prefix", $event->path_bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=post/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=post/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=post/resolution=prefix", $event->path_bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=put/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=put/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=put/resolution=prefix", $event->path_bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=patch/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=patch/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=patch/resolution=prefix", $event->path_bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->resolution === "unmatched") {
            observe($book, "path_bytes/method=delete/resolution=unmatched", $event->path_bytes);
        } else if ($event->resolution === "exact") {
            observe($book, "path_bytes/method=delete/resolution=exact", $event->path_bytes);
        } else if ($event->resolution === "prefix") {
            observe($book, "path_bytes/method=delete/resolution=prefix", $event->path_bytes);
        }
    }
}

function matched_bytes_by_method_by_scope(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=get/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=get/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=get/scope=any", $event->matched_bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=head/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=head/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=head/scope=any", $event->matched_bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=post/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=post/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=post/scope=any", $event->matched_bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=put/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=put/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=put/scope=any", $event->matched_bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=patch/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=patch/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=patch/scope=any", $event->matched_bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->scope === "missing") {
            observe($book, "matched_bytes/method=delete/scope=missing", $event->matched_bytes);
        } else if ($event->scope === "method") {
            observe($book, "matched_bytes/method=delete/scope=method", $event->matched_bytes);
        } else if ($event->scope === "any") {
            observe($book, "matched_bytes/method=delete/scope=any", $event->matched_bytes);
        }
    }
}

function depth_by_method_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=get/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=get/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=get/depth=nested", $event->depth);
        }
    } else if ($event->method === "HEAD") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=head/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=head/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=head/depth=nested", $event->depth);
        }
    } else if ($event->method === "POST") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=post/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=post/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=post/depth=nested", $event->depth);
        }
    } else if ($event->method === "PUT") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=put/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=put/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=put/depth=nested", $event->depth);
        }
    } else if ($event->method === "PATCH") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=patch/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=patch/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=patch/depth=nested", $event->depth);
        }
    } else if ($event->method === "DELETE") {
        if ($event->depth <= 1) {
            observe($book, "depth/method=delete/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/method=delete/depth=resource", $event->depth);
        } else {
            observe($book, "depth/method=delete/depth=nested", $event->depth);
        }
    }
}

function candidates_by_method_by_path_size(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=get/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=get/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=get/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=get/path_size=extended", $event->candidates);
        }
    } else if ($event->method === "HEAD") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=head/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=head/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=head/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=head/path_size=extended", $event->candidates);
        }
    } else if ($event->method === "POST") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=post/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=post/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=post/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=post/path_size=extended", $event->candidates);
        }
    } else if ($event->method === "PUT") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=put/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=put/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=put/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=put/path_size=extended", $event->candidates);
        }
    } else if ($event->method === "PATCH") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=patch/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=patch/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=patch/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=patch/path_size=extended", $event->candidates);
        }
    } else if ($event->method === "DELETE") {
        if ($event->path_bytes <= 8) {
            observe($book, "candidates/method=delete/path_size=short", $event->candidates);
        } else if ($event->path_bytes <= 24) {
            observe($book, "candidates/method=delete/path_size=medium", $event->candidates);
        } else if ($event->path_bytes <= 64) {
            observe($book, "candidates/method=delete/path_size=long", $event->candidates);
        } else {
            observe($book, "candidates/method=delete/path_size=extended", $event->candidates);
        }
    }
}

function path_bytes_by_method_by_coverage(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=get/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=get/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=get/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=get/coverage=complete", $event->path_bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=head/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=head/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=head/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=head/coverage=complete", $event->path_bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=post/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=post/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=post/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=post/coverage=complete", $event->path_bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=put/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=put/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=put/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=put/coverage=complete", $event->path_bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=patch/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=patch/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=patch/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=patch/coverage=complete", $event->path_bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->covered <= 0) {
            observe($book, "path_bytes/method=delete/coverage=none", $event->path_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "path_bytes/method=delete/coverage=partial", $event->path_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "path_bytes/method=delete/coverage=most", $event->path_bytes);
        } else {
            observe($book, "path_bytes/method=delete/coverage=complete", $event->path_bytes);
        }
    }
}

function matched_bytes_by_method_by_table(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=get/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=get/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=get/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=get/table=large", $event->matched_bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=head/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=head/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=head/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=head/table=large", $event->matched_bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=post/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=post/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=post/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=post/table=large", $event->matched_bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=put/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=put/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=put/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=put/table=large", $event->matched_bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=patch/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=patch/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=patch/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=patch/table=large", $event->matched_bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->candidates <= 0) {
            observe($book, "matched_bytes/method=delete/table=empty", $event->matched_bytes);
        } else if ($event->candidates <= 8) {
            observe($book, "matched_bytes/method=delete/table=small", $event->matched_bytes);
        } else if ($event->candidates <= 64) {
            observe($book, "matched_bytes/method=delete/table=medium", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/method=delete/table=large", $event->matched_bytes);
        }
    }
}

function depth_by_resolution_by_scope(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        if ($event->scope === "missing") {
            observe($book, "depth/resolution=unmatched/scope=missing", $event->depth);
        } else if ($event->scope === "method") {
            observe($book, "depth/resolution=unmatched/scope=method", $event->depth);
        } else if ($event->scope === "any") {
            observe($book, "depth/resolution=unmatched/scope=any", $event->depth);
        }
    } else if ($event->resolution === "exact") {
        if ($event->scope === "missing") {
            observe($book, "depth/resolution=exact/scope=missing", $event->depth);
        } else if ($event->scope === "method") {
            observe($book, "depth/resolution=exact/scope=method", $event->depth);
        } else if ($event->scope === "any") {
            observe($book, "depth/resolution=exact/scope=any", $event->depth);
        }
    } else if ($event->resolution === "prefix") {
        if ($event->scope === "missing") {
            observe($book, "depth/resolution=prefix/scope=missing", $event->depth);
        } else if ($event->scope === "method") {
            observe($book, "depth/resolution=prefix/scope=method", $event->depth);
        } else if ($event->scope === "any") {
            observe($book, "depth/resolution=prefix/scope=any", $event->depth);
        }
    }
}

function candidates_by_resolution_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        if ($event->depth <= 1) {
            observe($book, "candidates/resolution=unmatched/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/resolution=unmatched/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/resolution=unmatched/depth=nested", $event->candidates);
        }
    } else if ($event->resolution === "exact") {
        if ($event->depth <= 1) {
            observe($book, "candidates/resolution=exact/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/resolution=exact/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/resolution=exact/depth=nested", $event->candidates);
        }
    } else if ($event->resolution === "prefix") {
        if ($event->depth <= 1) {
            observe($book, "candidates/resolution=prefix/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/resolution=prefix/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/resolution=prefix/depth=nested", $event->candidates);
        }
    }
}

function path_bytes_by_resolution_by_path_size(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/resolution=unmatched/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/resolution=unmatched/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/resolution=unmatched/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/resolution=unmatched/path_size=extended", $event->path_bytes);
        }
    } else if ($event->resolution === "exact") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/resolution=exact/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/resolution=exact/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/resolution=exact/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/resolution=exact/path_size=extended", $event->path_bytes);
        }
    } else if ($event->resolution === "prefix") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/resolution=prefix/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/resolution=prefix/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/resolution=prefix/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/resolution=prefix/path_size=extended", $event->path_bytes);
        }
    }
}

function matched_bytes_by_resolution_by_coverage(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/resolution=unmatched/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/resolution=unmatched/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/resolution=unmatched/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/resolution=unmatched/coverage=complete", $event->matched_bytes);
        }
    } else if ($event->resolution === "exact") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/resolution=exact/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/resolution=exact/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/resolution=exact/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/resolution=exact/coverage=complete", $event->matched_bytes);
        }
    } else if ($event->resolution === "prefix") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/resolution=prefix/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/resolution=prefix/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/resolution=prefix/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/resolution=prefix/coverage=complete", $event->matched_bytes);
        }
    }
}

function depth_by_resolution_by_table(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        if ($event->candidates <= 0) {
            observe($book, "depth/resolution=unmatched/table=empty", $event->depth);
        } else if ($event->candidates <= 8) {
            observe($book, "depth/resolution=unmatched/table=small", $event->depth);
        } else if ($event->candidates <= 64) {
            observe($book, "depth/resolution=unmatched/table=medium", $event->depth);
        } else {
            observe($book, "depth/resolution=unmatched/table=large", $event->depth);
        }
    } else if ($event->resolution === "exact") {
        if ($event->candidates <= 0) {
            observe($book, "depth/resolution=exact/table=empty", $event->depth);
        } else if ($event->candidates <= 8) {
            observe($book, "depth/resolution=exact/table=small", $event->depth);
        } else if ($event->candidates <= 64) {
            observe($book, "depth/resolution=exact/table=medium", $event->depth);
        } else {
            observe($book, "depth/resolution=exact/table=large", $event->depth);
        }
    } else if ($event->resolution === "prefix") {
        if ($event->candidates <= 0) {
            observe($book, "depth/resolution=prefix/table=empty", $event->depth);
        } else if ($event->candidates <= 8) {
            observe($book, "depth/resolution=prefix/table=small", $event->depth);
        } else if ($event->candidates <= 64) {
            observe($book, "depth/resolution=prefix/table=medium", $event->depth);
        } else {
            observe($book, "depth/resolution=prefix/table=large", $event->depth);
        }
    }
}

function candidates_by_scope_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->scope === "missing") {
        if ($event->depth <= 1) {
            observe($book, "candidates/scope=missing/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/scope=missing/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/scope=missing/depth=nested", $event->candidates);
        }
    } else if ($event->scope === "method") {
        if ($event->depth <= 1) {
            observe($book, "candidates/scope=method/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/scope=method/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/scope=method/depth=nested", $event->candidates);
        }
    } else if ($event->scope === "any") {
        if ($event->depth <= 1) {
            observe($book, "candidates/scope=any/depth=root", $event->candidates);
        } else if ($event->depth <= 2) {
            observe($book, "candidates/scope=any/depth=resource", $event->candidates);
        } else {
            observe($book, "candidates/scope=any/depth=nested", $event->candidates);
        }
    }
}

function path_bytes_by_scope_by_path_size(Dashboard $book, ReportEvent $event): void {
    if ($event->scope === "missing") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/scope=missing/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/scope=missing/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/scope=missing/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/scope=missing/path_size=extended", $event->path_bytes);
        }
    } else if ($event->scope === "method") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/scope=method/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/scope=method/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/scope=method/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/scope=method/path_size=extended", $event->path_bytes);
        }
    } else if ($event->scope === "any") {
        if ($event->path_bytes <= 8) {
            observe($book, "path_bytes/scope=any/path_size=short", $event->path_bytes);
        } else if ($event->path_bytes <= 24) {
            observe($book, "path_bytes/scope=any/path_size=medium", $event->path_bytes);
        } else if ($event->path_bytes <= 64) {
            observe($book, "path_bytes/scope=any/path_size=long", $event->path_bytes);
        } else {
            observe($book, "path_bytes/scope=any/path_size=extended", $event->path_bytes);
        }
    }
}

function matched_bytes_by_scope_by_coverage(Dashboard $book, ReportEvent $event): void {
    if ($event->scope === "missing") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/scope=missing/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/scope=missing/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/scope=missing/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/scope=missing/coverage=complete", $event->matched_bytes);
        }
    } else if ($event->scope === "method") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/scope=method/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/scope=method/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/scope=method/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/scope=method/coverage=complete", $event->matched_bytes);
        }
    } else if ($event->scope === "any") {
        if ($event->covered <= 0) {
            observe($book, "matched_bytes/scope=any/coverage=none", $event->matched_bytes);
        } else if ($event->covered <= 50) {
            observe($book, "matched_bytes/scope=any/coverage=partial", $event->matched_bytes);
        } else if ($event->covered <= 99) {
            observe($book, "matched_bytes/scope=any/coverage=most", $event->matched_bytes);
        } else {
            observe($book, "matched_bytes/scope=any/coverage=complete", $event->matched_bytes);
        }
    }
}

function update_dashboard(Dashboard $book, ReportEvent $event): void {
    path_bytes_by_method($book, $event);
    matched_bytes_by_resolution($book, $event);
    depth_by_scope($book, $event);
    candidates_by_depth($book, $event);
    path_bytes_by_path_size($book, $event);
    matched_bytes_by_coverage($book, $event);
    depth_by_table($book, $event);
    path_bytes_by_method_by_resolution($book, $event);
    matched_bytes_by_method_by_scope($book, $event);
    depth_by_method_by_depth($book, $event);
    candidates_by_method_by_path_size($book, $event);
    path_bytes_by_method_by_coverage($book, $event);
    matched_bytes_by_method_by_table($book, $event);
    depth_by_resolution_by_scope($book, $event);
    candidates_by_resolution_by_depth($book, $event);
    path_bytes_by_resolution_by_path_size($book, $event);
    matched_bytes_by_resolution_by_coverage($book, $event);
    depth_by_resolution_by_table($book, $event);
    candidates_by_scope_by_depth($book, $event);
    path_bytes_by_scope_by_path_size($book, $event);
    matched_bytes_by_scope_by_coverage($book, $event);
}

function analyze(string &$input): void {
    $book = new Dashboard();
    $details = str_starts_with($input, "# dashboard\n");
    process_input($input, $book, $details);
    if ($details) {
        print_dashboard($book);
    }
}

$source result<string> = fs_get("input.txt");
if (!isset($source)) {
    echo "unable to read input.txt\n";
    return 2;
}
$input string = $source;
analyze($input);
return 0;
