#include "money_abi.h"
#include <stdio.h>
#include <inttypes.h>
int main(void) {
    int op;
    int64_t a, b, c;
    while (scanf("%d %" SCNd64 " %" SCNd64 " %" SCNd64, &op, &a, &b, &c) == 4) {
        MoneyResult out;
        switch (op) {
            case 0: money_add(a, b, &out); break;
            case 1: money_multiply(a, b, &out); break;
            case 2: money_rate(a, b, &out); break;
            case 3: money_prorate(a, b, c, &out); break;
            default: return 2;
        }
        printf("%" PRId64 " %" PRId32 " %" PRId32 "\n", out.amount, out.valid, out.reason);
    }
    return ferror(stdin) ? 1 : 0;
}
