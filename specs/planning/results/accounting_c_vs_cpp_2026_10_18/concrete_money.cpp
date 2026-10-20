// Hand-lowered probe. Same bounded arithmetic and runtime adapter as C control.
#ifdef EXPOSE_RUNTIME
#include "generated_money.hpp"
#endif
#include "concrete_amount.hpp"
#define money_add kernel_add
#define money_multiply kernel_multiply
#define money_rate kernel_rate
#define money_prorate kernel_prorate
#include "money.c"
#undef money_add
#undef money_multiply
#undef money_rate
#undef money_prorate
AmountHandle *handle_add(int64_t a, int64_t b) {
    ConcreteAmount result;
    if (!result.get()) return nullptr;
    MoneyResult fields;
#ifdef USE_LAMBDAS
    ([&]() { const auto arg0 = a; const auto arg1 = b; kernel_add(arg0, arg1, &fields); }());
    const auto status = ([&]() { auto *receiver = result.get(); const auto *value = &fields; return amount_write(receiver, value); }());
#else
    const auto arg0 = a; const auto arg1 = b;
    kernel_add(arg0, arg1, &fields);
    auto *receiver = result.get(); const auto *value = &fields;
    const auto status = amount_write(receiver, value);
#endif
    if (status != 0) return nullptr;
    return result.release();
}
void money_add(int64_t a, int64_t b, MoneyResult *out) {
    ConcreteAmount result(handle_add(a, b));
    if (!result.get() || amount_read(result.get(), out) != 0) {
        out->amount = 0; out->valid = 0; out->reason = -1;
    }
}
AmountHandle *handle_multiply(int64_t a, int64_t b) {
    ConcreteAmount result;
    if (!result.get()) return nullptr;
    MoneyResult fields;
#ifdef USE_LAMBDAS
    ([&]() { const auto arg0 = a; const auto arg1 = b; kernel_multiply(arg0, arg1, &fields); }());
    const auto status = ([&]() { auto *receiver = result.get(); const auto *value = &fields; return amount_write(receiver, value); }());
#else
    const auto arg0 = a; const auto arg1 = b;
    kernel_multiply(arg0, arg1, &fields);
    auto *receiver = result.get(); const auto *value = &fields;
    const auto status = amount_write(receiver, value);
#endif
    if (status != 0) return nullptr;
    return result.release();
}
void money_multiply(int64_t a, int64_t b, MoneyResult *out) {
    ConcreteAmount result(handle_multiply(a, b));
    if (!result.get() || amount_read(result.get(), out) != 0) {
        out->amount = 0; out->valid = 0; out->reason = -1;
    }
}
AmountHandle *handle_rate(int64_t a, int64_t b) {
    ConcreteAmount result;
    if (!result.get()) return nullptr;
    MoneyResult fields;
#ifdef USE_LAMBDAS
    ([&]() { const auto arg0 = a; const auto arg1 = b; kernel_rate(arg0, arg1, &fields); }());
    const auto status = ([&]() { auto *receiver = result.get(); const auto *value = &fields; return amount_write(receiver, value); }());
#else
    const auto arg0 = a; const auto arg1 = b;
    kernel_rate(arg0, arg1, &fields);
    auto *receiver = result.get(); const auto *value = &fields;
    const auto status = amount_write(receiver, value);
#endif
    if (status != 0) return nullptr;
    return result.release();
}
void money_rate(int64_t a, int64_t b, MoneyResult *out) {
    ConcreteAmount result(handle_rate(a, b));
    if (!result.get() || amount_read(result.get(), out) != 0) {
        out->amount = 0; out->valid = 0; out->reason = -1;
    }
}
AmountHandle *handle_prorate(int64_t a, int64_t b, int64_t c) {
    ConcreteAmount result;
    if (!result.get()) return nullptr;
    MoneyResult fields;
#ifdef USE_LAMBDAS
    ([&]() { const auto arg0 = a; const auto arg1 = b; const auto arg2 = c; kernel_prorate(arg0, arg1, arg2, &fields); }());
    const auto status = ([&]() { auto *receiver = result.get(); const auto *value = &fields; return amount_write(receiver, value); }());
#else
    const auto arg0 = a; const auto arg1 = b; const auto arg2 = c;
    kernel_prorate(arg0, arg1, arg2, &fields);
    auto *receiver = result.get(); const auto *value = &fields;
    const auto status = amount_write(receiver, value);
#endif
    if (status != 0) return nullptr;
    return result.release();
}
void money_prorate(int64_t a, int64_t b, int64_t c, MoneyResult *out) {
    ConcreteAmount result(handle_prorate(a, b, c));
    if (!result.get() || amount_read(result.get(), out) != 0) {
        out->amount = 0; out->valid = 0; out->reason = -1;
    }
}
