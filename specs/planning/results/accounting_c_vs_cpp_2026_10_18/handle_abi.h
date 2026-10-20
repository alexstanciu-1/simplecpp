#ifndef HANDLE_ABI_H
#define HANDLE_ABI_H
#include "money_abi.h"
typedef struct AmountHandle AmountHandle;
#ifdef __cplusplus
extern "C" {
#endif
/* Handles own a shared runtime AmountResult. Retain makes a new owning handle
 * to the same payload. Null denotes bridge failure; release accepts null.
 * read/write return 0 on success, -1 on failure. No exceptions cross this ABI. */
AmountHandle *amount_create(void);
AmountHandle *amount_retain(const AmountHandle *value);
void amount_release(AmountHandle *value);
int32_t amount_same(const AmountHandle *a, const AmountHandle *b);
int32_t amount_write(AmountHandle *value, const MoneyResult *fields);
int32_t amount_read(const AmountHandle *value, MoneyResult *fields);
AmountHandle *handle_add(int64_t a, int64_t b);
AmountHandle *handle_multiply(int64_t a, int64_t b);
AmountHandle *handle_rate(int64_t a, int64_t b);
AmountHandle *handle_prorate(int64_t a, int64_t b, int64_t c);
#ifdef __cplusplus
}
#endif
#endif
