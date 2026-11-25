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

function process_input(string &$input, Dashboard $book, bool $details): void {
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
                $posted = post_entry($item, $entry, $totals);
                if ($details) {
                    record_movement($book, $item, $entry, $posted);
                }
                if (!$posted) {
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
    public string $kind;
    public string $decision;
    public int $quantity;
    public int $available;
    public int $on_hand;
    public int $reserved;
    public int $price;
}

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

function quantity_by_movement(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        observe($book, "quantity/movement=receive", $event->quantity);
    } else if ($event->kind === "HOLD") {
        observe($book, "quantity/movement=hold", $event->quantity);
    } else if ($event->kind === "RELEASE") {
        observe($book, "quantity/movement=release", $event->quantity);
    } else if ($event->kind === "SHIP") {
        observe($book, "quantity/movement=ship", $event->quantity);
    } else if ($event->kind === "COUNT") {
        observe($book, "quantity/movement=count", $event->quantity);
    }
}

function available_by_decision(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        observe($book, "available/decision=posted", $event->available);
    } else if ($event->decision === "refused") {
        observe($book, "available/decision=refused", $event->available);
    }
}

function reserved_by_order_size(Dashboard $book, ReportEvent $event): void {
    if ($event->quantity <= 0) {
        observe($book, "reserved/order_size=zero", $event->reserved);
    } else if ($event->quantity <= 1) {
        observe($book, "reserved/order_size=single", $event->reserved);
    } else if ($event->quantity <= 10) {
        observe($book, "reserved/order_size=case", $event->reserved);
    } else {
        observe($book, "reserved/order_size=bulk", $event->reserved);
    }
}

function on_hand_by_availability(Dashboard $book, ReportEvent $event): void {
    if ($event->available <= 0) {
        observe($book, "on_hand/availability=none", $event->on_hand);
    } else if ($event->available <= 5) {
        observe($book, "on_hand/availability=low", $event->on_hand);
    } else if ($event->available <= 50) {
        observe($book, "on_hand/availability=standard", $event->on_hand);
    } else {
        observe($book, "on_hand/availability=surplus", $event->on_hand);
    }
}

function quantity_by_balance(Dashboard $book, ReportEvent $event): void {
    if ($event->on_hand <= 0) {
        observe($book, "quantity/balance=empty", $event->quantity);
    } else if ($event->on_hand <= 10) {
        observe($book, "quantity/balance=low", $event->quantity);
    } else if ($event->on_hand <= 100) {
        observe($book, "quantity/balance=normal", $event->quantity);
    } else {
        observe($book, "quantity/balance=high", $event->quantity);
    }
}

function available_by_reservation(Dashboard $book, ReportEvent $event): void {
    if ($event->reserved <= 0) {
        observe($book, "available/reservation=none", $event->available);
    } else if ($event->reserved <= 5) {
        observe($book, "available/reservation=small", $event->available);
    } else if ($event->reserved <= 20) {
        observe($book, "available/reservation=case", $event->available);
    } else {
        observe($book, "available/reservation=bulk", $event->available);
    }
}

function reserved_by_unit_price(Dashboard $book, ReportEvent $event): void {
    if ($event->price <= 0) {
        observe($book, "reserved/unit_price=unpriced", $event->reserved);
    } else if ($event->price <= 100) {
        observe($book, "reserved/unit_price=minor", $event->reserved);
    } else if ($event->price <= 1000) {
        observe($book, "reserved/unit_price=standard", $event->reserved);
    } else {
        observe($book, "reserved/unit_price=high", $event->reserved);
    }
}

function quantity_by_movement_by_decision(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->decision === "posted") {
            observe($book, "quantity/movement=receive/decision=posted", $event->quantity);
        } else if ($event->decision === "refused") {
            observe($book, "quantity/movement=receive/decision=refused", $event->quantity);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->decision === "posted") {
            observe($book, "quantity/movement=hold/decision=posted", $event->quantity);
        } else if ($event->decision === "refused") {
            observe($book, "quantity/movement=hold/decision=refused", $event->quantity);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->decision === "posted") {
            observe($book, "quantity/movement=release/decision=posted", $event->quantity);
        } else if ($event->decision === "refused") {
            observe($book, "quantity/movement=release/decision=refused", $event->quantity);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->decision === "posted") {
            observe($book, "quantity/movement=ship/decision=posted", $event->quantity);
        } else if ($event->decision === "refused") {
            observe($book, "quantity/movement=ship/decision=refused", $event->quantity);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->decision === "posted") {
            observe($book, "quantity/movement=count/decision=posted", $event->quantity);
        } else if ($event->decision === "refused") {
            observe($book, "quantity/movement=count/decision=refused", $event->quantity);
        }
    }
}

function available_by_movement_by_order_size(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->quantity <= 0) {
            observe($book, "available/movement=receive/order_size=zero", $event->available);
        } else if ($event->quantity <= 1) {
            observe($book, "available/movement=receive/order_size=single", $event->available);
        } else if ($event->quantity <= 10) {
            observe($book, "available/movement=receive/order_size=case", $event->available);
        } else {
            observe($book, "available/movement=receive/order_size=bulk", $event->available);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->quantity <= 0) {
            observe($book, "available/movement=hold/order_size=zero", $event->available);
        } else if ($event->quantity <= 1) {
            observe($book, "available/movement=hold/order_size=single", $event->available);
        } else if ($event->quantity <= 10) {
            observe($book, "available/movement=hold/order_size=case", $event->available);
        } else {
            observe($book, "available/movement=hold/order_size=bulk", $event->available);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->quantity <= 0) {
            observe($book, "available/movement=release/order_size=zero", $event->available);
        } else if ($event->quantity <= 1) {
            observe($book, "available/movement=release/order_size=single", $event->available);
        } else if ($event->quantity <= 10) {
            observe($book, "available/movement=release/order_size=case", $event->available);
        } else {
            observe($book, "available/movement=release/order_size=bulk", $event->available);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->quantity <= 0) {
            observe($book, "available/movement=ship/order_size=zero", $event->available);
        } else if ($event->quantity <= 1) {
            observe($book, "available/movement=ship/order_size=single", $event->available);
        } else if ($event->quantity <= 10) {
            observe($book, "available/movement=ship/order_size=case", $event->available);
        } else {
            observe($book, "available/movement=ship/order_size=bulk", $event->available);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->quantity <= 0) {
            observe($book, "available/movement=count/order_size=zero", $event->available);
        } else if ($event->quantity <= 1) {
            observe($book, "available/movement=count/order_size=single", $event->available);
        } else if ($event->quantity <= 10) {
            observe($book, "available/movement=count/order_size=case", $event->available);
        } else {
            observe($book, "available/movement=count/order_size=bulk", $event->available);
        }
    }
}

function reserved_by_movement_by_availability(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->available <= 0) {
            observe($book, "reserved/movement=receive/availability=none", $event->reserved);
        } else if ($event->available <= 5) {
            observe($book, "reserved/movement=receive/availability=low", $event->reserved);
        } else if ($event->available <= 50) {
            observe($book, "reserved/movement=receive/availability=standard", $event->reserved);
        } else {
            observe($book, "reserved/movement=receive/availability=surplus", $event->reserved);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->available <= 0) {
            observe($book, "reserved/movement=hold/availability=none", $event->reserved);
        } else if ($event->available <= 5) {
            observe($book, "reserved/movement=hold/availability=low", $event->reserved);
        } else if ($event->available <= 50) {
            observe($book, "reserved/movement=hold/availability=standard", $event->reserved);
        } else {
            observe($book, "reserved/movement=hold/availability=surplus", $event->reserved);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->available <= 0) {
            observe($book, "reserved/movement=release/availability=none", $event->reserved);
        } else if ($event->available <= 5) {
            observe($book, "reserved/movement=release/availability=low", $event->reserved);
        } else if ($event->available <= 50) {
            observe($book, "reserved/movement=release/availability=standard", $event->reserved);
        } else {
            observe($book, "reserved/movement=release/availability=surplus", $event->reserved);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->available <= 0) {
            observe($book, "reserved/movement=ship/availability=none", $event->reserved);
        } else if ($event->available <= 5) {
            observe($book, "reserved/movement=ship/availability=low", $event->reserved);
        } else if ($event->available <= 50) {
            observe($book, "reserved/movement=ship/availability=standard", $event->reserved);
        } else {
            observe($book, "reserved/movement=ship/availability=surplus", $event->reserved);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->available <= 0) {
            observe($book, "reserved/movement=count/availability=none", $event->reserved);
        } else if ($event->available <= 5) {
            observe($book, "reserved/movement=count/availability=low", $event->reserved);
        } else if ($event->available <= 50) {
            observe($book, "reserved/movement=count/availability=standard", $event->reserved);
        } else {
            observe($book, "reserved/movement=count/availability=surplus", $event->reserved);
        }
    }
}

function on_hand_by_movement_by_balance(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/movement=receive/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/movement=receive/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/movement=receive/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/movement=receive/balance=high", $event->on_hand);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/movement=hold/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/movement=hold/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/movement=hold/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/movement=hold/balance=high", $event->on_hand);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/movement=release/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/movement=release/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/movement=release/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/movement=release/balance=high", $event->on_hand);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/movement=ship/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/movement=ship/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/movement=ship/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/movement=ship/balance=high", $event->on_hand);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/movement=count/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/movement=count/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/movement=count/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/movement=count/balance=high", $event->on_hand);
        }
    }
}

function quantity_by_movement_by_reservation(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->reserved <= 0) {
            observe($book, "quantity/movement=receive/reservation=none", $event->quantity);
        } else if ($event->reserved <= 5) {
            observe($book, "quantity/movement=receive/reservation=small", $event->quantity);
        } else if ($event->reserved <= 20) {
            observe($book, "quantity/movement=receive/reservation=case", $event->quantity);
        } else {
            observe($book, "quantity/movement=receive/reservation=bulk", $event->quantity);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->reserved <= 0) {
            observe($book, "quantity/movement=hold/reservation=none", $event->quantity);
        } else if ($event->reserved <= 5) {
            observe($book, "quantity/movement=hold/reservation=small", $event->quantity);
        } else if ($event->reserved <= 20) {
            observe($book, "quantity/movement=hold/reservation=case", $event->quantity);
        } else {
            observe($book, "quantity/movement=hold/reservation=bulk", $event->quantity);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->reserved <= 0) {
            observe($book, "quantity/movement=release/reservation=none", $event->quantity);
        } else if ($event->reserved <= 5) {
            observe($book, "quantity/movement=release/reservation=small", $event->quantity);
        } else if ($event->reserved <= 20) {
            observe($book, "quantity/movement=release/reservation=case", $event->quantity);
        } else {
            observe($book, "quantity/movement=release/reservation=bulk", $event->quantity);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->reserved <= 0) {
            observe($book, "quantity/movement=ship/reservation=none", $event->quantity);
        } else if ($event->reserved <= 5) {
            observe($book, "quantity/movement=ship/reservation=small", $event->quantity);
        } else if ($event->reserved <= 20) {
            observe($book, "quantity/movement=ship/reservation=case", $event->quantity);
        } else {
            observe($book, "quantity/movement=ship/reservation=bulk", $event->quantity);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->reserved <= 0) {
            observe($book, "quantity/movement=count/reservation=none", $event->quantity);
        } else if ($event->reserved <= 5) {
            observe($book, "quantity/movement=count/reservation=small", $event->quantity);
        } else if ($event->reserved <= 20) {
            observe($book, "quantity/movement=count/reservation=case", $event->quantity);
        } else {
            observe($book, "quantity/movement=count/reservation=bulk", $event->quantity);
        }
    }
}

function available_by_movement_by_unit_price(Dashboard $book, ReportEvent $event): void {
    if ($event->kind === "RECEIVE") {
        if ($event->price <= 0) {
            observe($book, "available/movement=receive/unit_price=unpriced", $event->available);
        } else if ($event->price <= 100) {
            observe($book, "available/movement=receive/unit_price=minor", $event->available);
        } else if ($event->price <= 1000) {
            observe($book, "available/movement=receive/unit_price=standard", $event->available);
        } else {
            observe($book, "available/movement=receive/unit_price=high", $event->available);
        }
    } else if ($event->kind === "HOLD") {
        if ($event->price <= 0) {
            observe($book, "available/movement=hold/unit_price=unpriced", $event->available);
        } else if ($event->price <= 100) {
            observe($book, "available/movement=hold/unit_price=minor", $event->available);
        } else if ($event->price <= 1000) {
            observe($book, "available/movement=hold/unit_price=standard", $event->available);
        } else {
            observe($book, "available/movement=hold/unit_price=high", $event->available);
        }
    } else if ($event->kind === "RELEASE") {
        if ($event->price <= 0) {
            observe($book, "available/movement=release/unit_price=unpriced", $event->available);
        } else if ($event->price <= 100) {
            observe($book, "available/movement=release/unit_price=minor", $event->available);
        } else if ($event->price <= 1000) {
            observe($book, "available/movement=release/unit_price=standard", $event->available);
        } else {
            observe($book, "available/movement=release/unit_price=high", $event->available);
        }
    } else if ($event->kind === "SHIP") {
        if ($event->price <= 0) {
            observe($book, "available/movement=ship/unit_price=unpriced", $event->available);
        } else if ($event->price <= 100) {
            observe($book, "available/movement=ship/unit_price=minor", $event->available);
        } else if ($event->price <= 1000) {
            observe($book, "available/movement=ship/unit_price=standard", $event->available);
        } else {
            observe($book, "available/movement=ship/unit_price=high", $event->available);
        }
    } else if ($event->kind === "COUNT") {
        if ($event->price <= 0) {
            observe($book, "available/movement=count/unit_price=unpriced", $event->available);
        } else if ($event->price <= 100) {
            observe($book, "available/movement=count/unit_price=minor", $event->available);
        } else if ($event->price <= 1000) {
            observe($book, "available/movement=count/unit_price=standard", $event->available);
        } else {
            observe($book, "available/movement=count/unit_price=high", $event->available);
        }
    }
}

function reserved_by_decision_by_order_size(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        if ($event->quantity <= 0) {
            observe($book, "reserved/decision=posted/order_size=zero", $event->reserved);
        } else if ($event->quantity <= 1) {
            observe($book, "reserved/decision=posted/order_size=single", $event->reserved);
        } else if ($event->quantity <= 10) {
            observe($book, "reserved/decision=posted/order_size=case", $event->reserved);
        } else {
            observe($book, "reserved/decision=posted/order_size=bulk", $event->reserved);
        }
    } else if ($event->decision === "refused") {
        if ($event->quantity <= 0) {
            observe($book, "reserved/decision=refused/order_size=zero", $event->reserved);
        } else if ($event->quantity <= 1) {
            observe($book, "reserved/decision=refused/order_size=single", $event->reserved);
        } else if ($event->quantity <= 10) {
            observe($book, "reserved/decision=refused/order_size=case", $event->reserved);
        } else {
            observe($book, "reserved/decision=refused/order_size=bulk", $event->reserved);
        }
    }
}

function on_hand_by_decision_by_availability(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        if ($event->available <= 0) {
            observe($book, "on_hand/decision=posted/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/decision=posted/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/decision=posted/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/decision=posted/availability=surplus", $event->on_hand);
        }
    } else if ($event->decision === "refused") {
        if ($event->available <= 0) {
            observe($book, "on_hand/decision=refused/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/decision=refused/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/decision=refused/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/decision=refused/availability=surplus", $event->on_hand);
        }
    }
}

function quantity_by_decision_by_balance(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/decision=posted/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/decision=posted/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/decision=posted/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/decision=posted/balance=high", $event->quantity);
        }
    } else if ($event->decision === "refused") {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/decision=refused/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/decision=refused/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/decision=refused/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/decision=refused/balance=high", $event->quantity);
        }
    }
}

function available_by_decision_by_reservation(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        if ($event->reserved <= 0) {
            observe($book, "available/decision=posted/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/decision=posted/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/decision=posted/reservation=case", $event->available);
        } else {
            observe($book, "available/decision=posted/reservation=bulk", $event->available);
        }
    } else if ($event->decision === "refused") {
        if ($event->reserved <= 0) {
            observe($book, "available/decision=refused/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/decision=refused/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/decision=refused/reservation=case", $event->available);
        } else {
            observe($book, "available/decision=refused/reservation=bulk", $event->available);
        }
    }
}

function reserved_by_decision_by_unit_price(Dashboard $book, ReportEvent $event): void {
    if ($event->decision === "posted") {
        if ($event->price <= 0) {
            observe($book, "reserved/decision=posted/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/decision=posted/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/decision=posted/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/decision=posted/unit_price=high", $event->reserved);
        }
    } else if ($event->decision === "refused") {
        if ($event->price <= 0) {
            observe($book, "reserved/decision=refused/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/decision=refused/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/decision=refused/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/decision=refused/unit_price=high", $event->reserved);
        }
    }
}

function on_hand_by_order_size_by_availability(Dashboard $book, ReportEvent $event): void {
    if ($event->quantity <= 0) {
        if ($event->available <= 0) {
            observe($book, "on_hand/order_size=zero/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/order_size=zero/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/order_size=zero/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/order_size=zero/availability=surplus", $event->on_hand);
        }
    } else if ($event->quantity <= 1) {
        if ($event->available <= 0) {
            observe($book, "on_hand/order_size=single/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/order_size=single/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/order_size=single/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/order_size=single/availability=surplus", $event->on_hand);
        }
    } else if ($event->quantity <= 10) {
        if ($event->available <= 0) {
            observe($book, "on_hand/order_size=case/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/order_size=case/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/order_size=case/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/order_size=case/availability=surplus", $event->on_hand);
        }
    } else {
        if ($event->available <= 0) {
            observe($book, "on_hand/order_size=bulk/availability=none", $event->on_hand);
        } else if ($event->available <= 5) {
            observe($book, "on_hand/order_size=bulk/availability=low", $event->on_hand);
        } else if ($event->available <= 50) {
            observe($book, "on_hand/order_size=bulk/availability=standard", $event->on_hand);
        } else {
            observe($book, "on_hand/order_size=bulk/availability=surplus", $event->on_hand);
        }
    }
}

function quantity_by_order_size_by_balance(Dashboard $book, ReportEvent $event): void {
    if ($event->quantity <= 0) {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/order_size=zero/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/order_size=zero/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/order_size=zero/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/order_size=zero/balance=high", $event->quantity);
        }
    } else if ($event->quantity <= 1) {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/order_size=single/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/order_size=single/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/order_size=single/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/order_size=single/balance=high", $event->quantity);
        }
    } else if ($event->quantity <= 10) {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/order_size=case/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/order_size=case/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/order_size=case/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/order_size=case/balance=high", $event->quantity);
        }
    } else {
        if ($event->on_hand <= 0) {
            observe($book, "quantity/order_size=bulk/balance=empty", $event->quantity);
        } else if ($event->on_hand <= 10) {
            observe($book, "quantity/order_size=bulk/balance=low", $event->quantity);
        } else if ($event->on_hand <= 100) {
            observe($book, "quantity/order_size=bulk/balance=normal", $event->quantity);
        } else {
            observe($book, "quantity/order_size=bulk/balance=high", $event->quantity);
        }
    }
}

function available_by_order_size_by_reservation(Dashboard $book, ReportEvent $event): void {
    if ($event->quantity <= 0) {
        if ($event->reserved <= 0) {
            observe($book, "available/order_size=zero/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/order_size=zero/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/order_size=zero/reservation=case", $event->available);
        } else {
            observe($book, "available/order_size=zero/reservation=bulk", $event->available);
        }
    } else if ($event->quantity <= 1) {
        if ($event->reserved <= 0) {
            observe($book, "available/order_size=single/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/order_size=single/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/order_size=single/reservation=case", $event->available);
        } else {
            observe($book, "available/order_size=single/reservation=bulk", $event->available);
        }
    } else if ($event->quantity <= 10) {
        if ($event->reserved <= 0) {
            observe($book, "available/order_size=case/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/order_size=case/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/order_size=case/reservation=case", $event->available);
        } else {
            observe($book, "available/order_size=case/reservation=bulk", $event->available);
        }
    } else {
        if ($event->reserved <= 0) {
            observe($book, "available/order_size=bulk/reservation=none", $event->available);
        } else if ($event->reserved <= 5) {
            observe($book, "available/order_size=bulk/reservation=small", $event->available);
        } else if ($event->reserved <= 20) {
            observe($book, "available/order_size=bulk/reservation=case", $event->available);
        } else {
            observe($book, "available/order_size=bulk/reservation=bulk", $event->available);
        }
    }
}

function reserved_by_order_size_by_unit_price(Dashboard $book, ReportEvent $event): void {
    if ($event->quantity <= 0) {
        if ($event->price <= 0) {
            observe($book, "reserved/order_size=zero/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/order_size=zero/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/order_size=zero/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/order_size=zero/unit_price=high", $event->reserved);
        }
    } else if ($event->quantity <= 1) {
        if ($event->price <= 0) {
            observe($book, "reserved/order_size=single/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/order_size=single/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/order_size=single/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/order_size=single/unit_price=high", $event->reserved);
        }
    } else if ($event->quantity <= 10) {
        if ($event->price <= 0) {
            observe($book, "reserved/order_size=case/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/order_size=case/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/order_size=case/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/order_size=case/unit_price=high", $event->reserved);
        }
    } else {
        if ($event->price <= 0) {
            observe($book, "reserved/order_size=bulk/unit_price=unpriced", $event->reserved);
        } else if ($event->price <= 100) {
            observe($book, "reserved/order_size=bulk/unit_price=minor", $event->reserved);
        } else if ($event->price <= 1000) {
            observe($book, "reserved/order_size=bulk/unit_price=standard", $event->reserved);
        } else {
            observe($book, "reserved/order_size=bulk/unit_price=high", $event->reserved);
        }
    }
}

function on_hand_by_availability_by_balance(Dashboard $book, ReportEvent $event): void {
    if ($event->available <= 0) {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/availability=none/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/availability=none/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/availability=none/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/availability=none/balance=high", $event->on_hand);
        }
    } else if ($event->available <= 5) {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/availability=low/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/availability=low/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/availability=low/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/availability=low/balance=high", $event->on_hand);
        }
    } else if ($event->available <= 50) {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/availability=standard/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/availability=standard/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/availability=standard/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/availability=standard/balance=high", $event->on_hand);
        }
    } else {
        if ($event->on_hand <= 0) {
            observe($book, "on_hand/availability=surplus/balance=empty", $event->on_hand);
        } else if ($event->on_hand <= 10) {
            observe($book, "on_hand/availability=surplus/balance=low", $event->on_hand);
        } else if ($event->on_hand <= 100) {
            observe($book, "on_hand/availability=surplus/balance=normal", $event->on_hand);
        } else {
            observe($book, "on_hand/availability=surplus/balance=high", $event->on_hand);
        }
    }
}

function update_dashboard(Dashboard $book, ReportEvent $event): void {
    quantity_by_movement($book, $event);
    available_by_decision($book, $event);
    reserved_by_order_size($book, $event);
    on_hand_by_availability($book, $event);
    quantity_by_balance($book, $event);
    available_by_reservation($book, $event);
    reserved_by_unit_price($book, $event);
    quantity_by_movement_by_decision($book, $event);
    available_by_movement_by_order_size($book, $event);
    reserved_by_movement_by_availability($book, $event);
    on_hand_by_movement_by_balance($book, $event);
    quantity_by_movement_by_reservation($book, $event);
    available_by_movement_by_unit_price($book, $event);
    reserved_by_decision_by_order_size($book, $event);
    on_hand_by_decision_by_availability($book, $event);
    quantity_by_decision_by_balance($book, $event);
    available_by_decision_by_reservation($book, $event);
    reserved_by_decision_by_unit_price($book, $event);
    on_hand_by_order_size_by_availability($book, $event);
    quantity_by_order_size_by_balance($book, $event);
    available_by_order_size_by_reservation($book, $event);
    reserved_by_order_size_by_unit_price($book, $event);
    on_hand_by_availability_by_balance($book, $event);
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
