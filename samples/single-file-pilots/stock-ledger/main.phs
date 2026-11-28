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

// Journal columns: document, SKU, movement, units and unit price in cents.
// A receipt establishes inventory cost. A reservation holds stock until
// shipment or release. A stock count reconciles the physical balance.
class ItemBalance {
    public string $sku = "";
    public int $on_hand = 0;
    public int $reserved = 0;
    public int $value = 0;
    public int $received = 0;
    public int $shipped = 0;
    public int $adjustment = 0;
    public int $revenue = 0;
    public int $cost_of_sales = 0;
    public int $shortfalls = 0;
}

class Movement {
    public string $document;
    public string $sku;
    public string $kind;
    public int $quantity;
    public int $price;
}

class JournalTotals {
    public int $posted = 0;
    public int $invalid = 0;
    public int $duplicates = 0;
    public int $refused = 0;
    public int $counts = 0;
    public int $receipts = 0;
    public int $shipments = 0;
    public int $reservations = 0;
    public int $releases = 0;
}

function decode_movement(string $line, Movement $entry): bool {
    $columns = explode("|", $line);
    if (count($columns) !== 5) {
        return false;
    }
    $document = trim($columns[0]);
    $sku = trim($columns[1]);
    if (!identifier($document) || !identifier($sku)) {
        return false;
    }
    $kind = strtoupper(trim($columns[2]));
    $quantity = decimal_field(trim($columns[3]), 1000000);
    $price = decimal_field(trim($columns[4]), 100000000);
    if ($quantity < 0 || $price < 0) {
        return false;
    }
    if ($kind !== "COUNT" && $quantity === 0) {
        return false;
    }
    if ($kind === "HOLD" || $kind === "RELEASE" || $kind === "COUNT") {
        if ($price !== 0) {
            return false;
        }
    } else if ($kind !== "RECEIVE" && $kind !== "SHIP") {
        return false;
    }
    $entry->document = $document;
    $entry->sku = $sku;
    $entry->kind = $kind;
    $entry->quantity = $quantity;
    $entry->price = $price;
    return true;
}

function available_units(ItemBalance $item): int {
    return $item->on_hand - $item->reserved;
}

// Partial removals use moving-average cost rounded down to cents. Clearing
// the last unit also clears the rounding remainder from carrying value.
function removal_cost(ItemBalance $item, int $quantity): int {
    if ($quantity === $item->on_hand) {
        return $item->value;
    }
    if ($quantity === 0) {
        return 0;
    }
    $whole = $item->value / $item->on_hand;
    $remainder = $item->value % $item->on_hand;
    return $whole * $quantity + $remainder * $quantity / $item->on_hand;
}

function receive_stock(ItemBalance $item, Movement $entry): bool {
    // Keep the book within the operating limits of a single warehouse.
    // The same bound keeps proportional cost arithmetic within int64.
    if ($entry->quantity > 1000000 - $item->on_hand) {
        return false;
    }
    $item->on_hand += $entry->quantity;
    $item->received += $entry->quantity;
    $item->value += $entry->quantity * $entry->price;
    return true;
}

function reserve_stock(ItemBalance $item, int $quantity): bool {
    if ($quantity > available_units($item)) {
        $item->shortfalls += 1;
        return false;
    }
    $item->reserved += $quantity;
    return true;
}

function release_stock(ItemBalance $item, int $quantity): bool {
    if ($quantity > $item->reserved) {
        return false;
    }
    $item->reserved -= $quantity;
    return true;
}

function ship_stock(ItemBalance $item, Movement $entry): bool {
    $quantity = $entry->quantity;
    if ($quantity > $item->on_hand) {
        $item->shortfalls += 1;
        return false;
    }
    $cost = removal_cost($item, $quantity);
    $item->cost_of_sales += $cost;
    $item->value -= $cost;
    $item->revenue += $entry->price * $quantity;
    $item->shipped += $quantity;
    $item->on_hand -= $quantity;
    // Pick from held stock first. An unreserved balance is available for
    // immediate counter sales after all reservations have been fulfilled.
    if ($quantity >= $item->reserved) {
        $item->reserved = 0;
    } else {
        $item->reserved -= $quantity;
    }
    return true;
}

function reconcile_count(ItemBalance $item, int $physical): bool {
    if ($physical < $item->reserved) {
        return false;
    }
    $difference = $physical - $item->on_hand;
    if ($difference > 0) {
        if ($item->on_hand === 0) {
            // A positive opening balance needs a priced receipt.
            return false;
        }
        $unit_cost = $item->value / $item->on_hand;
        $item->value += $difference * $unit_cost;
    } else if ($difference < 0) {
        $item->value -= removal_cost($item, 0 - $difference);
    }
    $item->on_hand = $physical;
    $item->adjustment += $difference;
    return true;
}

function post_entry(ItemBalance $item, Movement $entry, JournalTotals $totals): bool {
    if ($entry->kind === "RECEIVE") {
        if (!receive_stock($item, $entry)) {
            return false;
        }
        $totals->receipts += 1;
    } else if ($entry->kind === "HOLD") {
        if (!reserve_stock($item, $entry->quantity)) {
            return false;
        }
        $totals->reservations += 1;
    } else if ($entry->kind === "RELEASE") {
        if (!release_stock($item, $entry->quantity)) {
            return false;
        }
        $totals->releases += 1;
    } else if ($entry->kind === "SHIP") {
        if (!ship_stock($item, $entry)) {
            return false;
        }
        $totals->shipments += 1;
    } else {
        if (!reconcile_count($item, $entry->quantity)) {
            return false;
        }
        $totals->counts += 1;
    }
    $totals->posted += 1;
    return true;
}

function stock_status(ItemBalance $item): string {
    if ($item->on_hand === 0) {
        return "empty";
    }
    if (available_units($item) === 0) {
        return "allocated";
    }
    if ($item->shortfalls > 0) {
        return "review";
    }
    return "available";
}

function render_item(ItemBalance $item): void {
    $gross = $item->revenue - $item->cost_of_sales;
    echo "item ", $item->sku, " ", stock_status($item), "\n";
    echo "  stock ", $item->on_hand, " held ", $item->reserved,
        " available ", available_units($item), " cents ", $item->value, "\n";
    echo "  received ", $item->received, " shipped ", $item->shipped,
        " variance ", $item->adjustment, " shortfalls ", $item->shortfalls, "\n";
    echo "  sales ", $item->revenue, " cost ", $item->cost_of_sales,
        " margin ", $gross, "\n";
}

function analyze(string &$input): void {
    $lines = explode("\n", $input);
    $totals = new JournalTotals();
    $seen hash<int,string> = [];
    $positions hash<int,string> = [];
    $items vector<ItemBalance> = [];
    foreach ($lines as $line) {
        $text = trim($line);
        if ($text !== "" && !str_starts_with($text, "#")) {
            $entry = new Movement();
            if (!decode_movement($text, $entry)) {
                $totals->invalid += 1;
            } else if (hash_contains($seen, $entry->document)) {
                $totals->duplicates += 1;
            } else {
                $seen[$entry->document] = 1;
                $sku = $entry->sku;
                if (!hash_contains($positions, $sku)) {
                    $positions[$sku] = count($items);
                    $created = new ItemBalance();
                    $created->sku = $sku;
                    $items[] = $created;
                }
                $position = $positions[$sku];
                $item = $items[$position];
                if (!post_entry($item, $entry, $totals)) {
                    $totals->refused += 1;
                }
            }
        }
    }
    echo "posted ", $totals->posted, " invalid ", $totals->invalid,
        " duplicates ", $totals->duplicates, " refused ", $totals->refused, "\n";
    echo "receipts ", $totals->receipts, " holds ", $totals->reservations,
        " releases ", $totals->releases, " shipments ", $totals->shipments,
        " counts ", $totals->counts, "\n";
    $stock_value = 0;
    $available = 0;
    foreach ($items as $item) {
        render_item($item);
        $stock_value += $item->value;
        $available += available_units($item);
    }
    echo "warehouse cents ", $stock_value, " available ", $available, "\n";
}

$source result<string> = fs_get("input.txt");
if (!isset($source)) {
    echo "unable to read input.txt\n";
    return 2;
}
$input string = $source;
analyze($input);
return 0;
