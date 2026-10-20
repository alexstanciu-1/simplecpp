/* Hand-lowered experiment: arithmetic remains in the application unit;
 * runtime allocation, shared ownership and strings stay behind the C ABI. */
#include "handle_abi.h"
#define money_add kernel_add
#define money_multiply kernel_multiply
#define money_rate kernel_rate
#define money_prorate kernel_prorate
#include "money.c"
#undef money_add
#undef money_multiply
#undef money_rate
#undef money_prorate
static void bridge_failure(MoneyResult *out) {
    out->amount = 0; out->valid = 0; out->reason = -1;
}
AmountHandle *handle_add(int64_t a, int64_t b) {
    AmountHandle *handle = amount_create();
    if (!handle) return 0;
    MoneyResult fields;
    kernel_add(a, b, &fields);
    if (amount_write(handle, &fields) != 0) { amount_release(handle); return 0; }
    return handle;
}
void money_add(int64_t a, int64_t b, MoneyResult *out) {
    AmountHandle *handle = handle_add(a, b);
    if (!handle || amount_read(handle, out) != 0) bridge_failure(out);
    amount_release(handle);
}
AmountHandle *handle_multiply(int64_t a, int64_t b) {
    AmountHandle *handle = amount_create();
    if (!handle) return 0;
    MoneyResult fields;
    kernel_multiply(a, b, &fields);
    if (amount_write(handle, &fields) != 0) { amount_release(handle); return 0; }
    return handle;
}
void money_multiply(int64_t a, int64_t b, MoneyResult *out) {
    AmountHandle *handle = handle_multiply(a, b);
    if (!handle || amount_read(handle, out) != 0) bridge_failure(out);
    amount_release(handle);
}
AmountHandle *handle_rate(int64_t a, int64_t b) {
    AmountHandle *handle = amount_create();
    if (!handle) return 0;
    MoneyResult fields;
    kernel_rate(a, b, &fields);
    if (amount_write(handle, &fields) != 0) { amount_release(handle); return 0; }
    return handle;
}
void money_rate(int64_t a, int64_t b, MoneyResult *out) {
    AmountHandle *handle = handle_rate(a, b);
    if (!handle || amount_read(handle, out) != 0) bridge_failure(out);
    amount_release(handle);
}
AmountHandle *handle_prorate(int64_t a, int64_t b, int64_t c) {
    AmountHandle *handle = amount_create();
    if (!handle) return 0;
    MoneyResult fields;
    kernel_prorate(a, b, c, &fields);
    if (amount_write(handle, &fields) != 0) { amount_release(handle); return 0; }
    return handle;
}
void money_prorate(int64_t a, int64_t b, int64_t c, MoneyResult *out) {
    AmountHandle *handle = handle_prorate(a, b, c);
    if (!handle || amount_read(handle, out) != 0) bridge_failure(out);
    amount_release(handle);
}
