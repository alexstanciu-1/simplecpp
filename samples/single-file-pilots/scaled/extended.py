"""Stateful analyses enabled in the 100 KB applications' dashboard mode."""

# Extra Dashboard fields, event processing and final report for each family.
EXTENDED = {
    'access-log': (
'''    public int $open_count = 0;
    public int $open_latency = 0;
    public int $open_peak = 0;
    public int $incidents = 0;
    public int $worst_streak = 0;
    public int $affected_ms = 0;
    public int $peak_ms = 0;
''',
'''// An incident is a consecutive run of slow or unsuccessful server responses.
// A healthy response closes the incident; the last incident closes at EOF.
function close_incident(Dashboard $book): void {
    if ($book->open_count === 0) {
        return;
    }
    $book->incidents += 1;
    $book->affected_ms += $book->open_latency;
    if ($book->open_count > $book->worst_streak) {
        $book->worst_streak = $book->open_count;
    }
    if ($book->open_peak > $book->peak_ms) {
        $book->peak_ms = $book->open_peak;
    }
    $book->open_count = 0;
    $book->open_latency = 0;
    $book->open_peak = 0;
}

function track_sequence(Dashboard $book, ReportEvent $event): void {
    if ($event->status < 500 && $event->elapsed <= 1000) {
        close_incident($book);
        return;
    }
    $book->open_count += 1;
    $book->open_latency += $event->elapsed;
    if ($event->elapsed > $book->open_peak) {
        $book->open_peak = $event->elapsed;
    }
}

function summarize_sequence(Dashboard $book): void {
    close_incident($book);
    echo "incidents ", $book->incidents, " longest ", $book->worst_streak,
        " affected-ms ", $book->affected_ms, " peak-ms ", $book->peak_ms, "\\n";
}
'''),
    'stock-ledger': (
'''    public hash<int,string> $last_available;
    public hash<int,string> $entries_per_sku;
    public vector<string> $sku_order;
    public int $depletions = 0;
    public int $restocks = 0;
    public int $stockout_attempts = 0;
''',
'''// Track changes in free stock independently per SKU. First observations
// establish a baseline, so the opening receipt does not count as a restock.
function track_sequence(Dashboard $book, ReportEvent $event): void {
    $sku = $event->sku;
    if (hash_contains($book->last_available, $sku)) {
        $previous = $book->last_available[$sku];
        if ($previous > 0 && $event->available === 0) {
            $book->depletions += 1;
        }
        if ($previous === 0 && $event->available > 0) {
            $book->restocks += 1;
        }
    } else {
        $book->sku_order[] = $sku;
        $book->entries_per_sku[$sku] = 0;
    }
    $book->last_available[$sku] = $event->available;
    $book->entries_per_sku[$sku] = $book->entries_per_sku[$sku] + 1;
    if ($event->decision === "refused" && $event->available === 0) {
        $book->stockout_attempts += 1;
    }
}

function summarize_sequence(Dashboard $book): void {
    $busiest = "";
    $maximum = 0;
    foreach ($book->sku_order as $sku) {
        $entries = $book->entries_per_sku[$sku];
        if ($entries > $maximum) {
            $busiest = $sku;
            $maximum = $entries;
        }
    }
    echo "depletions ", $book->depletions, " restocks ", $book->restocks,
        " refused-at-zero ", $book->stockout_attempts, " busiest ", $busiest,
        " entries ", $maximum, "\\n";
}
'''),
    'route-table': (
'''    public hash<int,string> $target_counts;
    public vector<string> $target_names;
    public string $previous_target = "";
    public int $target_switches = 0;
    public int $unmatched_run = 0;
    public int $longest_gap = 0;
''',
'''// Measure routing continuity across the test trace. A miss breaks the
// backend sequence; two matches separated by a miss are not a switch.
function track_sequence(Dashboard $book, ReportEvent $event): void {
    if ($event->resolution === "unmatched") {
        $book->unmatched_run += 1;
        if ($book->unmatched_run > $book->longest_gap) {
            $book->longest_gap = $book->unmatched_run;
        }
        $book->previous_target = "";
        return;
    }
    $book->unmatched_run = 0;
    $target = $event->target;
    if ($book->previous_target !== "" && $target !== $book->previous_target) {
        $book->target_switches += 1;
    }
    $book->previous_target = $target;
    if (!hash_contains($book->target_counts, $target)) {
        $book->target_names[] = $target;
        $book->target_counts[$target] = 0;
    }
    $book->target_counts[$target] = $book->target_counts[$target] + 1;
}

function summarize_sequence(Dashboard $book): void {
    echo "target-switches ", $book->target_switches, " longest-gap ", $book->longest_gap, "\\n";
    foreach ($book->target_names as $name) {
        echo "target-load ", $name, " ", $book->target_counts[$name], "\\n";
    }
}
'''),
}
