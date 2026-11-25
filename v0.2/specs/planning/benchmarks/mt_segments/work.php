function reconcile(segment<Stock> $stock, segment<int> $adjustments): int {
    $i = 0;
    $subtotal = 0;
    while ($i < count($stock)) {
        $quantity = $stock[$i]->on_hand + $adjustments[$i];
        $available = $quantity - $stock[$i]->reserved;
        $value = inventory_value($available, $stock[$i]->unit_cost_cents);
        $stock[$i]->on_hand = $quantity;
        $stock[$i]->available = $available;
        $stock[$i]->value_cents = $value;
        $stock[$i]->revision = $stock[$i]->revision + 1;
        $adjustments[$i] = 0;
        $subtotal += $value;
        $i += 1;
    }
    return $subtotal;
}
