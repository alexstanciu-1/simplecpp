#include "concrete_amount.hpp"
#include <cassert>
int main() {
    AmountHandle *survivor = nullptr;
    try {
        ConcreteAmount first;
        MoneyResult fields{42, 1, 0};
        assert(amount_write(first.get(), &fields) == 0);
        ConcreteAmount copy(first);
        assert(amount_same(first.get(), copy.get()));
        ConcreteAmount moved(static_cast<ConcreteAmount&&>(copy));
        assert(copy.get() == nullptr);
        survivor = amount_retain(moved.get());
        throw 7;
    } catch (int value) { assert(value == 7); }
    MoneyResult fields;
    assert(amount_read(survivor, &fields) == 0 && fields.amount == 42);
    amount_release(survivor);
}
