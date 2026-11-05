namespace Bench.Batch2 {
const EXTRA: int = 1;
function line_0(price: int, quantity: int = 3): int {
    let subtotal: int = price * 10 + quantity;
    let index: int = 0;
    while (index < quantity) {
        subtotal = subtotal + index;
        index = index + 1;
    }
    if (subtotal % 2 == 0) {
        subtotal = subtotal / 2;
    } else if (subtotal > 100) {
        subtotal = subtotal - 7;
    } else {
        subtotal = subtotal + 5;
    }
    do { subtotal = subtotal + 1; } while (false);
    let ticks: int = 0;
    for (let loop: int = 0; loop < quantity; loop++) {
        if (loop == 1) { continue; }
        if (loop > 2) { break; }
        ++ticks;
    }
    if (!(ticks == 2) || !(quantity > 0 && price > 0)) { return 0; }
    subtotal += (int) (byte) 2;
    subtotal -= 1;
    subtotal *= 2;
    subtotal /= 2;
    subtotal %= 1000000;
    --subtotal;
    for (index = 0; index < 1; index += 1) { subtotal += EXTRA; }
    return subtotal;
}
function line_1(price: int, quantity: int = 3): int {
    let subtotal: int = price * 11 + quantity;
    let index: int = 0;
    while (index < quantity) {
        subtotal = subtotal + index;
        index = index + 1;
    }
    if (subtotal % 2 == 0) {
        subtotal = subtotal / 2;
    } else if (subtotal > 100) {
        subtotal = subtotal - 7;
    } else {
        subtotal = subtotal + 5;
    }
    do { subtotal = subtotal + 1; } while (false);
    let ticks: int = 0;
    for (let loop: int = 0; loop < quantity; loop++) {
        if (loop == 1) { continue; }
        if (loop > 2) { break; }
        ++ticks;
    }
    if (!(ticks == 2) || !(quantity > 0 && price > 0)) { return 0; }
    subtotal += (int) (byte) 2;
    subtotal -= 1;
    subtotal *= 2;
    subtotal /= 2;
    subtotal %= 1000000;
    --subtotal;
    for (index = 0; index < 1; index += 1) { subtotal += EXTRA; }
    return subtotal;
}
function line_2(price: int, quantity: int = 3): int {
    let subtotal: int = price * 12 + quantity;
    let index: int = 0;
    while (index < quantity) {
        subtotal = subtotal + index;
        index = index + 1;
    }
    if (subtotal % 2 == 0) {
        subtotal = subtotal / 2;
    } else if (subtotal > 100) {
        subtotal = subtotal - 7;
    } else {
        subtotal = subtotal + 5;
    }
    do { subtotal = subtotal + 1; } while (false);
    let ticks: int = 0;
    for (let loop: int = 0; loop < quantity; loop++) {
        if (loop == 1) { continue; }
        if (loop > 2) { break; }
        ++ticks;
    }
    if (!(ticks == 2) || !(quantity > 0 && price > 0)) { return 0; }
    subtotal += (int) (byte) 2;
    subtotal -= 1;
    subtotal *= 2;
    subtotal /= 2;
    subtotal %= 1000000;
    --subtotal;
    for (index = 0; index < 1; index += 1) { subtotal += EXTRA; }
    return subtotal;
}
function line_3(price: int, quantity: int = 3): int {
    let subtotal: int = price * 13 + quantity;
    let index: int = 0;
    while (index < quantity) {
        subtotal = subtotal + index;
        index = index + 1;
    }
    if (subtotal % 2 == 0) {
        subtotal = subtotal / 2;
    } else if (subtotal > 100) {
        subtotal = subtotal - 7;
    } else {
        subtotal = subtotal + 5;
    }
    do { subtotal = subtotal + 1; } while (false);
    let ticks: int = 0;
    for (let loop: int = 0; loop < quantity; loop++) {
        if (loop == 1) { continue; }
        if (loop > 2) { break; }
        ++ticks;
    }
    if (!(ticks == 2) || !(quantity > 0 && price > 0)) { return 0; }
    subtotal += (int) (byte) 2;
    subtotal -= 1;
    subtotal *= 2;
    subtotal /= 2;
    subtotal %= 1000000;
    --subtotal;
    for (index = 0; index < 1; index += 1) { subtotal += EXTRA; }
    return subtotal;
}
}
