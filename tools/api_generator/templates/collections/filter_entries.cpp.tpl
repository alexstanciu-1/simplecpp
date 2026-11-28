#pragma once

namespace scpp::detail {
template<class Key, class Slot, class Next, class Predicate, class Insert>
inline void filter_entries(Next next, Predicate keep, Insert insert) {
    Key key{};
    Slot item{};
    while (next(key, item)) {
        if (keep(item)) insert(key, item);
    }
}
}
