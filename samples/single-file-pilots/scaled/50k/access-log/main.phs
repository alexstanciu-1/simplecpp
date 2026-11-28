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

function process_input(string &$input, Dashboard $book, bool $details): void {
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
                if ($details) {
                    record_request($book, $record);
                }
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
    public int $status;
    public string $method;
    public string $cache;
    public string $category;
    public int $elapsed;
    public int $bytes;
    public int $depth;
}

function record_request(Dashboard $book, AccessRecord $record): void {
    $event = new ReportEvent();
    $event->status = $record->status;
    $event->method = $record->method;
    $event->cache = $record->cache;
    $event->bytes = $record->bytes;
    $event->elapsed = $record->elapsed;
    $event->depth = substr_count($record->path, "/");
    if ($record->path === "/health" || $record->path === "/ready") {
        $event->category = "health";
    } else if (str_starts_with($record->path, "/assets/")) {
        $event->category = "assets";
    } else if (str_starts_with($record->path, "/api/")) {
        $event->category = "api";
    } else {
        $event->category = "other";
    }
    update_dashboard($book, $event);
}

function elapsed_by_status(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        observe($book, "elapsed/status=success", $event->elapsed);
    } else if ($event->status <= 399) {
        observe($book, "elapsed/status=redirect", $event->elapsed);
    } else if ($event->status <= 499) {
        observe($book, "elapsed/status=client_error", $event->elapsed);
    } else {
        observe($book, "elapsed/status=server_error", $event->elapsed);
    }
}

function bytes_by_method(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        observe($book, "bytes/method=get", $event->bytes);
    } else if ($event->method === "HEAD") {
        observe($book, "bytes/method=head", $event->bytes);
    } else if ($event->method === "POST") {
        observe($book, "bytes/method=post", $event->bytes);
    } else if ($event->method === "PUT") {
        observe($book, "bytes/method=put", $event->bytes);
    } else if ($event->method === "PATCH") {
        observe($book, "bytes/method=patch", $event->bytes);
    } else if ($event->method === "DELETE") {
        observe($book, "bytes/method=delete", $event->bytes);
    }
}

function depth_by_cache(Dashboard $book, ReportEvent $event): void {
    if ($event->cache === "HIT") {
        observe($book, "depth/cache=hit", $event->depth);
    } else if ($event->cache === "MISS") {
        observe($book, "depth/cache=miss", $event->depth);
    } else if ($event->cache === "BYPASS") {
        observe($book, "depth/cache=bypass", $event->depth);
    }
}

function elapsed_by_route(Dashboard $book, ReportEvent $event): void {
    if ($event->category === "health") {
        observe($book, "elapsed/route=health", $event->elapsed);
    } else if ($event->category === "assets") {
        observe($book, "elapsed/route=assets", $event->elapsed);
    } else if ($event->category === "api") {
        observe($book, "elapsed/route=api", $event->elapsed);
    } else if ($event->category === "other") {
        observe($book, "elapsed/route=other", $event->elapsed);
    }
}

function bytes_by_latency(Dashboard $book, ReportEvent $event): void {
    if ($event->elapsed <= 50) {
        observe($book, "bytes/latency=fast", $event->bytes);
    } else if ($event->elapsed <= 250) {
        observe($book, "bytes/latency=interactive", $event->bytes);
    } else if ($event->elapsed <= 1000) {
        observe($book, "bytes/latency=delayed", $event->bytes);
    } else {
        observe($book, "bytes/latency=slow", $event->bytes);
    }
}

function depth_by_response(Dashboard $book, ReportEvent $event): void {
    if ($event->bytes <= 0) {
        observe($book, "depth/response=empty", $event->depth);
    } else if ($event->bytes <= 255) {
        observe($book, "depth/response=small", $event->depth);
    } else if ($event->bytes <= 4095) {
        observe($book, "depth/response=medium", $event->depth);
    } else {
        observe($book, "depth/response=large", $event->depth);
    }
}

function elapsed_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->depth <= 1) {
        observe($book, "elapsed/depth=root", $event->elapsed);
    } else if ($event->depth <= 2) {
        observe($book, "elapsed/depth=resource", $event->elapsed);
    } else {
        observe($book, "elapsed/depth=nested", $event->elapsed);
    }
}

function elapsed_by_status_by_method(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->method === "GET") {
            observe($book, "elapsed/status=success/method=get", $event->elapsed);
        } else if ($event->method === "HEAD") {
            observe($book, "elapsed/status=success/method=head", $event->elapsed);
        } else if ($event->method === "POST") {
            observe($book, "elapsed/status=success/method=post", $event->elapsed);
        } else if ($event->method === "PUT") {
            observe($book, "elapsed/status=success/method=put", $event->elapsed);
        } else if ($event->method === "PATCH") {
            observe($book, "elapsed/status=success/method=patch", $event->elapsed);
        } else if ($event->method === "DELETE") {
            observe($book, "elapsed/status=success/method=delete", $event->elapsed);
        }
    } else if ($event->status <= 399) {
        if ($event->method === "GET") {
            observe($book, "elapsed/status=redirect/method=get", $event->elapsed);
        } else if ($event->method === "HEAD") {
            observe($book, "elapsed/status=redirect/method=head", $event->elapsed);
        } else if ($event->method === "POST") {
            observe($book, "elapsed/status=redirect/method=post", $event->elapsed);
        } else if ($event->method === "PUT") {
            observe($book, "elapsed/status=redirect/method=put", $event->elapsed);
        } else if ($event->method === "PATCH") {
            observe($book, "elapsed/status=redirect/method=patch", $event->elapsed);
        } else if ($event->method === "DELETE") {
            observe($book, "elapsed/status=redirect/method=delete", $event->elapsed);
        }
    } else if ($event->status <= 499) {
        if ($event->method === "GET") {
            observe($book, "elapsed/status=client_error/method=get", $event->elapsed);
        } else if ($event->method === "HEAD") {
            observe($book, "elapsed/status=client_error/method=head", $event->elapsed);
        } else if ($event->method === "POST") {
            observe($book, "elapsed/status=client_error/method=post", $event->elapsed);
        } else if ($event->method === "PUT") {
            observe($book, "elapsed/status=client_error/method=put", $event->elapsed);
        } else if ($event->method === "PATCH") {
            observe($book, "elapsed/status=client_error/method=patch", $event->elapsed);
        } else if ($event->method === "DELETE") {
            observe($book, "elapsed/status=client_error/method=delete", $event->elapsed);
        }
    } else {
        if ($event->method === "GET") {
            observe($book, "elapsed/status=server_error/method=get", $event->elapsed);
        } else if ($event->method === "HEAD") {
            observe($book, "elapsed/status=server_error/method=head", $event->elapsed);
        } else if ($event->method === "POST") {
            observe($book, "elapsed/status=server_error/method=post", $event->elapsed);
        } else if ($event->method === "PUT") {
            observe($book, "elapsed/status=server_error/method=put", $event->elapsed);
        } else if ($event->method === "PATCH") {
            observe($book, "elapsed/status=server_error/method=patch", $event->elapsed);
        } else if ($event->method === "DELETE") {
            observe($book, "elapsed/status=server_error/method=delete", $event->elapsed);
        }
    }
}

function bytes_by_status_by_cache(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->cache === "HIT") {
            observe($book, "bytes/status=success/cache=hit", $event->bytes);
        } else if ($event->cache === "MISS") {
            observe($book, "bytes/status=success/cache=miss", $event->bytes);
        } else if ($event->cache === "BYPASS") {
            observe($book, "bytes/status=success/cache=bypass", $event->bytes);
        }
    } else if ($event->status <= 399) {
        if ($event->cache === "HIT") {
            observe($book, "bytes/status=redirect/cache=hit", $event->bytes);
        } else if ($event->cache === "MISS") {
            observe($book, "bytes/status=redirect/cache=miss", $event->bytes);
        } else if ($event->cache === "BYPASS") {
            observe($book, "bytes/status=redirect/cache=bypass", $event->bytes);
        }
    } else if ($event->status <= 499) {
        if ($event->cache === "HIT") {
            observe($book, "bytes/status=client_error/cache=hit", $event->bytes);
        } else if ($event->cache === "MISS") {
            observe($book, "bytes/status=client_error/cache=miss", $event->bytes);
        } else if ($event->cache === "BYPASS") {
            observe($book, "bytes/status=client_error/cache=bypass", $event->bytes);
        }
    } else {
        if ($event->cache === "HIT") {
            observe($book, "bytes/status=server_error/cache=hit", $event->bytes);
        } else if ($event->cache === "MISS") {
            observe($book, "bytes/status=server_error/cache=miss", $event->bytes);
        } else if ($event->cache === "BYPASS") {
            observe($book, "bytes/status=server_error/cache=bypass", $event->bytes);
        }
    }
}

function depth_by_status_by_route(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->category === "health") {
            observe($book, "depth/status=success/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/status=success/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/status=success/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/status=success/route=other", $event->depth);
        }
    } else if ($event->status <= 399) {
        if ($event->category === "health") {
            observe($book, "depth/status=redirect/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/status=redirect/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/status=redirect/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/status=redirect/route=other", $event->depth);
        }
    } else if ($event->status <= 499) {
        if ($event->category === "health") {
            observe($book, "depth/status=client_error/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/status=client_error/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/status=client_error/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/status=client_error/route=other", $event->depth);
        }
    } else {
        if ($event->category === "health") {
            observe($book, "depth/status=server_error/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/status=server_error/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/status=server_error/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/status=server_error/route=other", $event->depth);
        }
    }
}

function elapsed_by_status_by_latency(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/status=success/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/status=success/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/status=success/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/status=success/latency=slow", $event->elapsed);
        }
    } else if ($event->status <= 399) {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/status=redirect/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/status=redirect/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/status=redirect/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/status=redirect/latency=slow", $event->elapsed);
        }
    } else if ($event->status <= 499) {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/status=client_error/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/status=client_error/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/status=client_error/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/status=client_error/latency=slow", $event->elapsed);
        }
    } else {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/status=server_error/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/status=server_error/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/status=server_error/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/status=server_error/latency=slow", $event->elapsed);
        }
    }
}

function bytes_by_status_by_response(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->bytes <= 0) {
            observe($book, "bytes/status=success/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/status=success/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/status=success/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/status=success/response=large", $event->bytes);
        }
    } else if ($event->status <= 399) {
        if ($event->bytes <= 0) {
            observe($book, "bytes/status=redirect/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/status=redirect/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/status=redirect/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/status=redirect/response=large", $event->bytes);
        }
    } else if ($event->status <= 499) {
        if ($event->bytes <= 0) {
            observe($book, "bytes/status=client_error/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/status=client_error/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/status=client_error/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/status=client_error/response=large", $event->bytes);
        }
    } else {
        if ($event->bytes <= 0) {
            observe($book, "bytes/status=server_error/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/status=server_error/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/status=server_error/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/status=server_error/response=large", $event->bytes);
        }
    }
}

function depth_by_status_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->status <= 299) {
        if ($event->depth <= 1) {
            observe($book, "depth/status=success/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/status=success/depth=resource", $event->depth);
        } else {
            observe($book, "depth/status=success/depth=nested", $event->depth);
        }
    } else if ($event->status <= 399) {
        if ($event->depth <= 1) {
            observe($book, "depth/status=redirect/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/status=redirect/depth=resource", $event->depth);
        } else {
            observe($book, "depth/status=redirect/depth=nested", $event->depth);
        }
    } else if ($event->status <= 499) {
        if ($event->depth <= 1) {
            observe($book, "depth/status=client_error/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/status=client_error/depth=resource", $event->depth);
        } else {
            observe($book, "depth/status=client_error/depth=nested", $event->depth);
        }
    } else {
        if ($event->depth <= 1) {
            observe($book, "depth/status=server_error/depth=root", $event->depth);
        } else if ($event->depth <= 2) {
            observe($book, "depth/status=server_error/depth=resource", $event->depth);
        } else {
            observe($book, "depth/status=server_error/depth=nested", $event->depth);
        }
    }
}

function elapsed_by_method_by_cache(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=get/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=get/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=get/cache=bypass", $event->elapsed);
        }
    } else if ($event->method === "HEAD") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=head/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=head/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=head/cache=bypass", $event->elapsed);
        }
    } else if ($event->method === "POST") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=post/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=post/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=post/cache=bypass", $event->elapsed);
        }
    } else if ($event->method === "PUT") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=put/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=put/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=put/cache=bypass", $event->elapsed);
        }
    } else if ($event->method === "PATCH") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=patch/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=patch/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=patch/cache=bypass", $event->elapsed);
        }
    } else if ($event->method === "DELETE") {
        if ($event->cache === "HIT") {
            observe($book, "elapsed/method=delete/cache=hit", $event->elapsed);
        } else if ($event->cache === "MISS") {
            observe($book, "elapsed/method=delete/cache=miss", $event->elapsed);
        } else if ($event->cache === "BYPASS") {
            observe($book, "elapsed/method=delete/cache=bypass", $event->elapsed);
        }
    }
}

function bytes_by_method_by_route(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->category === "health") {
            observe($book, "bytes/method=get/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=get/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=get/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=get/route=other", $event->bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->category === "health") {
            observe($book, "bytes/method=head/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=head/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=head/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=head/route=other", $event->bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->category === "health") {
            observe($book, "bytes/method=post/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=post/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=post/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=post/route=other", $event->bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->category === "health") {
            observe($book, "bytes/method=put/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=put/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=put/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=put/route=other", $event->bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->category === "health") {
            observe($book, "bytes/method=patch/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=patch/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=patch/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=patch/route=other", $event->bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->category === "health") {
            observe($book, "bytes/method=delete/route=health", $event->bytes);
        } else if ($event->category === "assets") {
            observe($book, "bytes/method=delete/route=assets", $event->bytes);
        } else if ($event->category === "api") {
            observe($book, "bytes/method=delete/route=api", $event->bytes);
        } else if ($event->category === "other") {
            observe($book, "bytes/method=delete/route=other", $event->bytes);
        }
    }
}

function depth_by_method_by_latency(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=get/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=get/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=get/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=get/latency=slow", $event->depth);
        }
    } else if ($event->method === "HEAD") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=head/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=head/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=head/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=head/latency=slow", $event->depth);
        }
    } else if ($event->method === "POST") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=post/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=post/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=post/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=post/latency=slow", $event->depth);
        }
    } else if ($event->method === "PUT") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=put/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=put/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=put/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=put/latency=slow", $event->depth);
        }
    } else if ($event->method === "PATCH") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=patch/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=patch/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=patch/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=patch/latency=slow", $event->depth);
        }
    } else if ($event->method === "DELETE") {
        if ($event->elapsed <= 50) {
            observe($book, "depth/method=delete/latency=fast", $event->depth);
        } else if ($event->elapsed <= 250) {
            observe($book, "depth/method=delete/latency=interactive", $event->depth);
        } else if ($event->elapsed <= 1000) {
            observe($book, "depth/method=delete/latency=delayed", $event->depth);
        } else {
            observe($book, "depth/method=delete/latency=slow", $event->depth);
        }
    }
}

function elapsed_by_method_by_response(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=get/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=get/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=get/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=get/response=large", $event->elapsed);
        }
    } else if ($event->method === "HEAD") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=head/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=head/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=head/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=head/response=large", $event->elapsed);
        }
    } else if ($event->method === "POST") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=post/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=post/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=post/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=post/response=large", $event->elapsed);
        }
    } else if ($event->method === "PUT") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=put/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=put/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=put/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=put/response=large", $event->elapsed);
        }
    } else if ($event->method === "PATCH") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=patch/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=patch/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=patch/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=patch/response=large", $event->elapsed);
        }
    } else if ($event->method === "DELETE") {
        if ($event->bytes <= 0) {
            observe($book, "elapsed/method=delete/response=empty", $event->elapsed);
        } else if ($event->bytes <= 255) {
            observe($book, "elapsed/method=delete/response=small", $event->elapsed);
        } else if ($event->bytes <= 4095) {
            observe($book, "elapsed/method=delete/response=medium", $event->elapsed);
        } else {
            observe($book, "elapsed/method=delete/response=large", $event->elapsed);
        }
    }
}

function bytes_by_method_by_depth(Dashboard $book, ReportEvent $event): void {
    if ($event->method === "GET") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=get/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=get/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=get/depth=nested", $event->bytes);
        }
    } else if ($event->method === "HEAD") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=head/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=head/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=head/depth=nested", $event->bytes);
        }
    } else if ($event->method === "POST") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=post/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=post/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=post/depth=nested", $event->bytes);
        }
    } else if ($event->method === "PUT") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=put/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=put/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=put/depth=nested", $event->bytes);
        }
    } else if ($event->method === "PATCH") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=patch/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=patch/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=patch/depth=nested", $event->bytes);
        }
    } else if ($event->method === "DELETE") {
        if ($event->depth <= 1) {
            observe($book, "bytes/method=delete/depth=root", $event->bytes);
        } else if ($event->depth <= 2) {
            observe($book, "bytes/method=delete/depth=resource", $event->bytes);
        } else {
            observe($book, "bytes/method=delete/depth=nested", $event->bytes);
        }
    }
}

function depth_by_cache_by_route(Dashboard $book, ReportEvent $event): void {
    if ($event->cache === "HIT") {
        if ($event->category === "health") {
            observe($book, "depth/cache=hit/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/cache=hit/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/cache=hit/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/cache=hit/route=other", $event->depth);
        }
    } else if ($event->cache === "MISS") {
        if ($event->category === "health") {
            observe($book, "depth/cache=miss/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/cache=miss/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/cache=miss/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/cache=miss/route=other", $event->depth);
        }
    } else if ($event->cache === "BYPASS") {
        if ($event->category === "health") {
            observe($book, "depth/cache=bypass/route=health", $event->depth);
        } else if ($event->category === "assets") {
            observe($book, "depth/cache=bypass/route=assets", $event->depth);
        } else if ($event->category === "api") {
            observe($book, "depth/cache=bypass/route=api", $event->depth);
        } else if ($event->category === "other") {
            observe($book, "depth/cache=bypass/route=other", $event->depth);
        }
    }
}

function elapsed_by_cache_by_latency(Dashboard $book, ReportEvent $event): void {
    if ($event->cache === "HIT") {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/cache=hit/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/cache=hit/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/cache=hit/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/cache=hit/latency=slow", $event->elapsed);
        }
    } else if ($event->cache === "MISS") {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/cache=miss/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/cache=miss/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/cache=miss/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/cache=miss/latency=slow", $event->elapsed);
        }
    } else if ($event->cache === "BYPASS") {
        if ($event->elapsed <= 50) {
            observe($book, "elapsed/cache=bypass/latency=fast", $event->elapsed);
        } else if ($event->elapsed <= 250) {
            observe($book, "elapsed/cache=bypass/latency=interactive", $event->elapsed);
        } else if ($event->elapsed <= 1000) {
            observe($book, "elapsed/cache=bypass/latency=delayed", $event->elapsed);
        } else {
            observe($book, "elapsed/cache=bypass/latency=slow", $event->elapsed);
        }
    }
}

function bytes_by_cache_by_response(Dashboard $book, ReportEvent $event): void {
    if ($event->cache === "HIT") {
        if ($event->bytes <= 0) {
            observe($book, "bytes/cache=hit/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/cache=hit/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/cache=hit/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/cache=hit/response=large", $event->bytes);
        }
    } else if ($event->cache === "MISS") {
        if ($event->bytes <= 0) {
            observe($book, "bytes/cache=miss/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/cache=miss/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/cache=miss/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/cache=miss/response=large", $event->bytes);
        }
    } else if ($event->cache === "BYPASS") {
        if ($event->bytes <= 0) {
            observe($book, "bytes/cache=bypass/response=empty", $event->bytes);
        } else if ($event->bytes <= 255) {
            observe($book, "bytes/cache=bypass/response=small", $event->bytes);
        } else if ($event->bytes <= 4095) {
            observe($book, "bytes/cache=bypass/response=medium", $event->bytes);
        } else {
            observe($book, "bytes/cache=bypass/response=large", $event->bytes);
        }
    }
}

function update_dashboard(Dashboard $book, ReportEvent $event): void {
    elapsed_by_status($book, $event);
    bytes_by_method($book, $event);
    depth_by_cache($book, $event);
    elapsed_by_route($book, $event);
    bytes_by_latency($book, $event);
    depth_by_response($book, $event);
    elapsed_by_depth($book, $event);
    elapsed_by_status_by_method($book, $event);
    bytes_by_status_by_cache($book, $event);
    depth_by_status_by_route($book, $event);
    elapsed_by_status_by_latency($book, $event);
    bytes_by_status_by_response($book, $event);
    depth_by_status_by_depth($book, $event);
    elapsed_by_method_by_cache($book, $event);
    bytes_by_method_by_route($book, $event);
    depth_by_method_by_latency($book, $event);
    elapsed_by_method_by_response($book, $event);
    bytes_by_method_by_depth($book, $event);
    depth_by_cache_by_route($book, $event);
    elapsed_by_cache_by_latency($book, $event);
    bytes_by_cache_by_response($book, $event);
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
