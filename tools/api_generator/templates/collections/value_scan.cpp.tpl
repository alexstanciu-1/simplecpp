#pragma once

namespace scpp::detail {
template<class Output, class Next, class Predicate>
inline bool any_value(Next next, Predicate matches) {
    Output item{};
    while (next(item)) {
        if (matches(item)) return true;
    }
    return false;
}
}
