#include "generated_money.hpp"
#include "handle_abi.h"
struct AmountHandle { scpp::shared_p<scpp::AmountResult> value; };
extern "C" AmountHandle *amount_create(void) {
    try { return new AmountHandle{scpp::create<scpp::AmountResult>()}; }
    catch (...) { return nullptr; }
}
extern "C" AmountHandle *amount_retain(const AmountHandle *value) {
    if (!value) return nullptr;
    try { return new AmountHandle{value->value}; }
    catch (...) { return nullptr; }
}
extern "C" void amount_release(AmountHandle *value) { delete value; }
extern "C" int32_t amount_same(const AmountHandle *a, const AmountHandle *b) {
    return a && b && a->value.get() == b->value.get();
}
extern "C" int32_t amount_write(AmountHandle *value, const MoneyResult *fields) {
    if (!value || !fields || fields->reason < 0 || fields->reason > 5) return -1;
    static const char *reasons[] = {"", "amount.operand", "amount.overflow",
        "amount.quantity", "amount.rate", "amount.ratio"};
    try {
        value->value->reason = scpp::string_t(reasons[fields->reason]);
        value->value->amount = scpp::int_t<>(fields->amount);
        value->value->valid = scpp::bool_t(fields->valid != 0);
        return 0;
    } catch (...) { return -1; }
}
extern "C" int32_t amount_read(const AmountHandle *value, MoneyResult *fields) {
    if (!value || !fields) return -1;
    try {
        fields->amount = value->value->amount.native_value();
        fields->valid = static_cast<bool>(value->value->valid);
        const auto& reason = value->value->reason.native_value();
        fields->reason = reason.empty() ? 0 : reason == "amount.operand" ? 1 :
            reason == "amount.overflow" ? 2 : reason == "amount.quantity" ? 3 :
            reason == "amount.rate" ? 4 : reason == "amount.ratio" ? 5 : -2;
        return fields->reason < 0 ? -1 : 0;
    } catch (...) { return -1; }
}
