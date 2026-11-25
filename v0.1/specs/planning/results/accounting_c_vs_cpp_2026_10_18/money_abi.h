#ifndef MONEY_ABI_H
#define MONEY_ABI_H
#include <stdint.h>
/* Caller-owned result; reason: 0 success, 1 operand, 2 overflow,
 * 3 quantity, 4 rate, 5 ratio, -1 bridge exception. No owned pointers. */
typedef struct MoneyResult { int64_t amount; int32_t valid; int32_t reason; } MoneyResult;
#ifdef __cplusplus
extern "C" {
#endif
void money_add(int64_t a, int64_t b, MoneyResult *out);
void money_multiply(int64_t a, int64_t b, MoneyResult *out);
void money_rate(int64_t a, int64_t b, MoneyResult *out);
void money_prorate(int64_t a, int64_t b, int64_t c, MoneyResult *out);
#ifdef __cplusplus
}
#endif
#endif
