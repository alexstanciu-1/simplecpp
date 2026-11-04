namespace Bench.Batch5 {
function line_0(price: int, quantity: int = 3): int {
    let subtotal: int = price * 22 + quantity;
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
    return subtotal;
}
function line_1(price: int, quantity: int = 3): int {
    let subtotal: int = price * 23 + quantity;
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
    return subtotal;
}
function line_2(price: int, quantity: int = 3): int {
    let subtotal: int = price * 24 + quantity;
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
    return subtotal;
}
function line_3(price: int, quantity: int = 3): int {
    let subtotal: int = price * 25 + quantity;
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
    return subtotal;
}
}
