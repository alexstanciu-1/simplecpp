"""Materialize six standalone PHP-flavor sources with compiled dashboard reports."""
from pathlib import Path
from catalog import DOMAINS, reports
from extended import EXTENDED

HERE = Path(__file__).resolve().parent
BASE = HERE.parent

CELL = '''
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
    echo "dashboard ", count($book->cells), " occupied cells\\n";
    foreach ($book->cells as $cell) {
        echo $cell->key, " ", $cell->samples, " ", $cell->total, " ",
            $cell->low, " ", $cell->high, "\\n";
    }
}
'''

# Feed the dashboard after validation so rejected syntax cannot enter metrics.
ADAPTERS = {
    'access-log': '''
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
''',
    'stock-ledger': '''
function record_movement(Dashboard $book, ItemBalance $item, Movement $entry, bool $posted): void {
    $event = new ReportEvent();
    $event->kind = $entry->kind;
    $event->quantity = $entry->quantity;
    $event->price = $entry->price;
    $event->available = available_units($item);
    $event->on_hand = $item->on_hand;
    $event->reserved = $item->reserved;
    if ($posted) {
        $event->decision = "posted";
    } else {
        $event->decision = "refused";
    }
    update_dashboard($book, $event);
}
''',
    'route-table': '''
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
''',
}


def source_parts(name):
    text = (BASE / name / 'main.php').read_text()
    # A separate entry keeps the original task and its output directly comparable.
    text = text.replace('function analyze(string &$input): void {',
                        'function process_input(string &$input, Dashboard $book, bool $details): void {')
    if name == 'access-log':
        text = text.replace('                count_response(', '''                if ($details) {
                    record_request($book, $record);
                }
                count_response(''')
    elif name == 'stock-ledger':
        text = text.replace('if (!post_entry($item, $entry, $totals)) {', '''$posted = post_entry($item, $entry, $totals);
                if ($details) {
                    record_movement($book, $item, $entry, $posted);
                }
                if (!$posted) {''')
    else:
        text = text.replace('function dispatch(RequestCase $request, vector<RouteRule> &$rules, Validation $state)',
                            'function dispatch(RequestCase $request, vector<RouteRule> &$rules, Validation $state, Dashboard $book, bool $details)')
        text = text.replace('    if ($index < 0) {', '''    if ($details) {
        record_resolution($book, $request, $rules, $index);
    }
    if ($index < 0) {''')
        text = text.replace('dispatch($request, $rules, $state);', 'dispatch($request, $rules, $state, $book, $details);')
    text, entry = text.split('$source result<string>')
    adapter = '''
function analyze(string &$input): void {
    $book = new Dashboard();
    $details = str_starts_with($input, "# dashboard\\n");
    process_input($input, $book, $details);
    if ($details) {
        print_dashboard($book);
    }
}
'''
    axes, measures = DOMAINS[name]
    fields = {field: 'string' if isinstance(values[0], str) else 'int' for field, values in axes.values()}
    for field in measures:
        fields.setdefault(field, 'int')
    event = 'class ReportEvent {\n' + ''.join(f'    public {t} ${k};\n' for k,t in fields.items()) + '}\n'
    return text + CELL + '\n' + event + ADAPTERS[name], adapter + '\n$source result<string>' + entry


def report_function(axes, keys, measure):
    title = '_by_'.join([measure, *keys])
    lines = [f'function {title}(Dashboard $book, ReportEvent $event): void {{']
    def branch(depth, labels):
        indent = '    ' * (depth + 1)
        if depth == len(keys):
            path = '/'.join([measure, *labels])
            lines.append(f'{indent}observe($book, "{path}", $event->{measure});')
            return
        key = keys[depth]
        field, options = axes[key]
        for i, option in enumerate(options):
            if isinstance(option, str):
                label = option.lower()
                condition = f'($event->{field} === "{option}")'
            else:
                label, bound = option
                condition = f'($event->{field} <= {bound})' if bound is not None else ''
            prefix = 'if ' if i == 0 else '} else if ' if condition else '} else'
            lines.append(f'{indent}{prefix}{condition} {{')
            branch(depth+1, [*labels, f'{key}={label}'])
        lines.append(indent + '}')
    branch(0, [])
    lines.append('}')
    return title, '\n'.join(lines) + '\n'


def materialize(name, target):
    start, end = source_parts(name)
    if target == 100000:
        fields, sequence = EXTENDED[name]
        start = start.replace('class Dashboard {', 'class Dashboard {\n' + fields.rstrip())
        if name == 'stock-ledger':
            start = start.replace('class ReportEvent {', 'class ReportEvent {\n    public string $sku;')
            start = start.replace('$event->kind =', '$event->sku = $entry->sku;\n    $event->kind =')
        if name == 'route-table':
            start = start.replace('class ReportEvent {', 'class ReportEvent {\n    public string $target;')
            start = start.replace('$event->matched_bytes =', '$event->target = $chosen->target;\n        $event->matched_bytes =')
        start += sequence
        start = start.replace('    update_dashboard($book, $event);',
                              '    track_sequence($book, $event);\n    update_dashboard($book, $event);')
        end = end.replace('        print_dashboard($book);','        print_dashboard($book);\n        summarize_sequence($book);')
    units, calls, used = [], [], []
    axes, _ = DOMAINS[name]
    def assemble():
        dispatch = 'function update_dashboard(Dashboard $book, ReportEvent $event): void {\n'
        return start + '\n' + '\n'.join(units) + '\n' + dispatch + ''.join(calls) + '}\n' + end
    for keys, measure in reports(name):
        title, unit = report_function(axes, keys, measure)
        line = f'    {title}($book, $event);\n'
        size = len(assemble().encode())
        after = size + len(unit) + len(line) + 1
        if size >= target * .95 and abs(target-size) < abs(target-after):
            break
        if after > target * 1.05:
            continue
        calls.append(line)
        units.append(unit)
        used.append((keys, measure))
    text = assemble()
    assert .95*target <= len(text.encode()) <= 1.05*target
    return text, used


if __name__ == '__main__':
    for size in (50,100):
        for name in DOMAINS:
            directory = HERE / f'{size}k' / name
            directory.mkdir(parents=True, exist_ok=True)
            text, used = materialize(name, size*1000)
            (directory / 'main.php').write_text(text)
            (directory / 'module.json').write_bytes((BASE / name / 'module.json').read_bytes())
            print(size, name, len(text.encode()), len(used))
