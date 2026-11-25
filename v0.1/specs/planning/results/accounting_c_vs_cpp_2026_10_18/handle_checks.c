#include "handle_abi.h"
#include <assert.h>
int main(void) {
    AmountHandle *first = handle_add(20, 22);
    assert(first);
    AmountHandle *alias = amount_retain(first);
    assert(alias && amount_same(first, alias));
    AmountHandle *other = handle_add(20, 22);
    assert(other && !amount_same(first, other));
    MoneyResult fields = {99, 1, 0};
    assert(amount_write(alias, &fields) == 0);
    assert(amount_read(first, &fields) == 0 && fields.amount == 99);
    amount_release(first);
    assert(amount_read(alias, &fields) == 0 && fields.amount == 99);
    fields.amount = 0; fields.valid = 0; fields.reason = 5;
    assert(amount_write(alias, &fields) == 0);
    assert(amount_read(alias, &fields) == 0 && fields.reason == 5 && !fields.valid);
    amount_release(alias);
    amount_release(other);
    amount_release(0);
    assert(!amount_retain(0));
    assert(amount_read(0, &fields) == -1);
    return 0;
}
