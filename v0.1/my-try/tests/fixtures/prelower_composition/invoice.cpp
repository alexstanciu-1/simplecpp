#include "interface.hpp"

long Invoice::total() const {
    long result = 0;
    for (std::size_t i = 0; i < amounts.size(); ++i) {
        result += amounts.at(i).native_value();
    }
    return result;
}

void mutate_amounts(Vector_Int& amounts) {
    amounts.at(0) = Integer(41);
}

Invoice make_invoice() {
    Invoice result;
    result.amounts.append(Integer(10));
    result.amounts.append(Integer(20));
    result.payloads.append(Tracked(7));
    return result;
}
