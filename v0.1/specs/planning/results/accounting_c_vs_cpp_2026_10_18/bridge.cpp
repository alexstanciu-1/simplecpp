#include "generated_money.hpp"
#include "money_abi.h"
static void copy_result(const scpp::shared_p<scpp::AmountResult>& value, MoneyResult *out) {
    out->amount = value->amount.native_value();
    out->valid = static_cast<bool>(value->valid);
    const auto& reason = value->reason.native_value();
    out->reason = reason.empty() ? 0 : reason == "amount.operand" ? 1 :
        reason == "amount.overflow" ? 2 : reason == "amount.quantity" ? 3 :
        reason == "amount.rate" ? 4 : reason == "amount.ratio" ? 5 : -2;
}
extern "C" void money_add(int64_t a, int64_t b, MoneyResult *out) {
    try { copy_result(scpp::MoneyRules::add(scpp::int_t<>(a), scpp::int_t<>(b)), out); }
    catch (...) { out->amount = 0; out->valid = 0; out->reason = -1; }
}
extern "C" void money_multiply(int64_t a, int64_t b, MoneyResult *out) {
    try { copy_result(scpp::MoneyRules::multiply(scpp::int_t<>(a), scpp::int_t<>(b)), out); }
    catch (...) { out->amount = 0; out->valid = 0; out->reason = -1; }
}
extern "C" void money_rate(int64_t a, int64_t b, MoneyResult *out) {
    try { copy_result(scpp::MoneyRules::rate(scpp::int_t<>(a), scpp::int_t<>(b)), out); }
    catch (...) { out->amount = 0; out->valid = 0; out->reason = -1; }
}
extern "C" void money_prorate(int64_t a, int64_t b, int64_t c, MoneyResult *out) {
    try { copy_result(scpp::MoneyRules::prorate(scpp::int_t<>(a), scpp::int_t<>(b), scpp::int_t<>(c)), out); }
    catch (...) { out->amount = 0; out->valid = 0; out->reason = -1; }
}
