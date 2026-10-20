#include "money_abi.h"
static int accepts(int64_t a) { return a >= -1000000000 && a <= 1000000000; }
static void result(MoneyResult *out, int64_t amount, int32_t reason) {
    out->amount = amount;
    out->valid = reason == 0;
    out->reason = reason;
}
void money_add(int64_t a, int64_t b, MoneyResult *out) {
    if (!accepts(a) || !accepts(b)) { result(out, 0, 1); return; }
    if (!accepts(a + b)) { result(out, 0, 2); return; }
    result(out, a + b, 0);
}
void money_multiply(int64_t a, int64_t b, MoneyResult *out) {
    if (!accepts(a) || b < 0 || b > 1000000) { result(out, 0, 3); return; }
    if (!accepts(a * b)) { result(out, 0, 2); return; }
    result(out, a * b, 0);
}
void money_rate(int64_t a, int64_t b, MoneyResult *out) {
    if (!accepts(a) || b < 0 || b > 10000) { result(out, 0, 4); return; }
    int64_t scaled = (a < 0 ? -a : a) * b;
    int64_t remainder = scaled % 10000;
    int64_t rounded = (scaled - remainder) / 10000;
    if (remainder >= 5000) rounded += 1;
    result(out, a < 0 ? -rounded : rounded, 0);
}
void money_prorate(int64_t a, int64_t b, int64_t c, MoneyResult *out) {
    if (!accepts(a) || b < 0 || c <= 0 || b > c || c > 1000000) {
        result(out, 0, 5); return;
    }
    int64_t scaled = (a < 0 ? -a : a) * b;
    int64_t remainder = scaled % c;
    int64_t rounded = (scaled - remainder) / c;
    if (remainder * 2 >= c) rounded += 1;
    result(out, a < 0 ? -rounded : rounded, 0);
}
