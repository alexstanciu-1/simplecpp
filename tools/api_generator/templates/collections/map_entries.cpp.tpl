#pragma once

namespace scpp::detail {
template<class Key, class Slot, class Next, class Map, class Insert>
inline void map_entries(Next next, Map map, Insert insert) {
    Key key{};
    Slot item{};
    while (next(key, item)) {
        insert(key, map(item));
    }
}
}
