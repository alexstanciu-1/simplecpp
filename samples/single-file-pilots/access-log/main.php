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

// Each gateway exports one space-delimited row per request:
// service method request-target status response-bytes elapsed-ms cache-status.
class AccessRecord {
    public string $service;
    public string $method;
    public string $path;
    public int $status;
    public int $bytes;
    public int $elapsed;
    public string $cache;
}

class TrafficReport {
    public int $accepted = 0;
    public int $rejected = 0;
    public int $bytes = 0;
    public int $elapsed = 0;
    public int $success = 0;
    public int $redirect = 0;
    public int $client_errors = 0;
    public int $server_errors = 0;
    public int $cache_hits = 0;
    public int $cache_misses = 0;
    public int $slow_requests = 0;
    public int $writes = 0;
    public int $health_checks = 0;
    public int $static_requests = 0;
    public int $api_requests = 0;
    public string $slowest_path = "";
    public int $slowest_ms = 0;
    public string $largest_path = "";
    public int $largest_bytes = 0;
}

function known_method(string $method): bool {
    return $method === "GET" || $method === "HEAD" || $method === "POST"
        || $method === "PUT" || $method === "PATCH" || $method === "DELETE";
}

function clean_target(string $target): string {
    if (!str_starts_with($target, "/") || str_contains($target, "#")) {
        return "";
    }
    // Query parameters can contain identifiers and credentials. Group by path
    // and keep the report independent of query order.
    $parts = explode("?", $target, 2);
    $path = $parts[0];
    if (str_contains($path, "//")) {
        return "";
    }
    return $path;
}

function decode_access(string $line, AccessRecord $record): bool {
    $fields = explode(" ", $line);
    if (count($fields) !== 7) {
        return false;
    }
    $service = $fields[0];
    $method = $fields[1];
    $path = clean_target($fields[2]);
    if (!identifier($service) || !known_method($method) || $path === "") {
        return false;
    }
    $status = decimal_field($fields[3], 599);
    $bytes = decimal_field($fields[4], 1000000000);
    $elapsed = decimal_field($fields[5], 86400000);
    if ($status < 200 || $bytes < 0 || $elapsed < 0) {
        return false;
    }
    $cache = $fields[6];
    if ($cache !== "HIT" && $cache !== "MISS" && $cache !== "BYPASS") {
        return false;
    }
    $record->service = $service;
    $record->method = $method;
    $record->path = $path;
    $record->status = $status;
    $record->bytes = $bytes;
    $record->elapsed = $elapsed;
    $record->cache = $cache;
    return true;
}

function count_response(TrafficReport $report, AccessRecord $record): void {
    $report->accepted += 1;
    $report->bytes += $record->bytes;
    $report->elapsed += $record->elapsed;
    if ($record->status >= 500) {
        $report->server_errors += 1;
    } else if ($record->status >= 400) {
        $report->client_errors += 1;
    } else if ($record->status >= 300) {
        $report->redirect += 1;
    } else {
        $report->success += 1;
    }
    if ($record->cache === "HIT") {
        $report->cache_hits += 1;
    } else if ($record->cache === "MISS") {
        $report->cache_misses += 1;
    }
    if ($record->method !== "GET" && $record->method !== "HEAD") {
        $report->writes += 1;
    }
}

function count_route(TrafficReport $report, AccessRecord $record): void {
    if ($record->elapsed >= 1000) {
        $report->slow_requests += 1;
    }
    if ($report->accepted === 1 || $record->elapsed > $report->slowest_ms) {
        $report->slowest_ms = $record->elapsed;
        $report->slowest_path = $record->path;
    }
    if ($report->accepted === 1 || $record->bytes > $report->largest_bytes) {
        $report->largest_bytes = $record->bytes;
        $report->largest_path = $record->path;
    }
    if ($record->path === "/health" || $record->path === "/ready") {
        $report->health_checks += 1;
    } else if (str_starts_with($record->path, "/assets/")) {
        $report->static_requests += 1;
    } else if (str_starts_with($record->path, "/api/")) {
        $report->api_requests += 1;
    }
}

function percentage(int $part, int $total): int {
    if ($total === 0) {
        return 0;
    }
    return $part * 100 / $total;
}

function service_grade(TrafficReport $report): string {
    if ($report->accepted === 0) {
        return "no-data";
    }
    if (percentage($report->server_errors, $report->accepted) >= 5) {
        return "unhealthy";
    }
    if ($report->server_errors > 0 || percentage($report->slow_requests, $report->accepted) >= 10) {
        return "degraded";
    }
    return "healthy";
}

// Accumulate histogram buckets in ascending order. Percentiles use nearest
// rank and report a bucket ceiling, with the observed maximum for overflow.
function latency_bucket(int $elapsed): int {
    if ($elapsed <= 10) { return 0; }
    if ($elapsed <= 50) { return 1; }
    if ($elapsed <= 100) { return 2; }
    if ($elapsed <= 250) { return 3; }
    if ($elapsed <= 500) { return 4; }
    if ($elapsed <= 1000) { return 5; }
    return 6;
}

function percentile(vector<int> &$histogram, int $total, int $rank, int $maximum): int {
    if ($total === 0) {
        return 0;
    }
    $ceilings vector<int> = [10, 50, 100, 250, 500, 1000, $maximum];
    $required = ($total * $rank + 99) / 100;
    $seen = 0;
    for ($i = 0; $i < count($histogram); $i += 1) {
        $seen += $histogram[$i];
        if ($seen >= $required) {
            return $ceilings[$i];
        }
    }
    return $maximum;
}

// Return the error-budget balance in requests for a 99% success target.
// Client-side failures are excluded from the population the service owns.
function error_budget_remaining(TrafficReport $report): int {
    $eligible = $report->accepted - $report->client_errors;
    $allowance = $eligible / 100;
    return $allowance - $report->server_errors;
}

function render_traffic(TrafficReport $report, vector<int> &$histogram): void {
    $average = 0;
    if ($report->accepted > 0) {
        $average = $report->elapsed / $report->accepted;
    }
    echo "requests ", $report->accepted, " rejected ", $report->rejected, "\n";
    echo "responses ", $report->success, " ", $report->redirect, " ",
        $report->client_errors, " ", $report->server_errors, "\n";
    echo "bytes ", $report->bytes, " writes ", $report->writes, "\n";
    echo "routes health ", $report->health_checks, " static ", $report->static_requests,
        " api ", $report->api_requests, "\n";
    echo "cache-percent ", percentage($report->cache_hits, $report->cache_hits + $report->cache_misses), "\n";
    echo "latency-ms mean ", $average, " p50 ", percentile($histogram, $report->accepted, 50, $report->slowest_ms),
        " p95 ", percentile($histogram, $report->accepted, 95, $report->slowest_ms), "\n";
    echo "slowest ", $report->slowest_path, " ", $report->slowest_ms, "\n";
    echo "largest ", $report->largest_path, " ", $report->largest_bytes, "\n";
    echo "error-budget ", error_budget_remaining($report), "\n";
    echo "grade ", service_grade($report), "\n";
}

function analyze(string &$input): void {
    $lines = explode("\n", $input);
    $report = new TrafficReport();
    $histogram vector<int> = [0, 0, 0, 0, 0, 0, 0];
    $services hash<int,string> = [];
    $names vector<string> = [];
    foreach ($lines as $line) {
        $text = trim($line);
        if ($text !== "" && !str_starts_with($text, "#")) {
            $record = new AccessRecord();
            if (decode_access($text, $record)) {
                count_response($report, $record);
                count_route($report, $record);
                $bucket = latency_bucket($record->elapsed);
                $histogram[$bucket] = $histogram[$bucket] + 1;
                $name = $record->service;
                if (!hash_contains($services, $name)) {
                    $names[] = $name;
                    $services[$name] = 0;
                }
                $services[$name] = $services[$name] + 1;
            } else {
                $report->rejected += 1;
            }
        }
    }
    render_traffic($report, $histogram);
    foreach ($names as $name) {
        echo "service ", $name, " ", $services[$name], "\n";
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
