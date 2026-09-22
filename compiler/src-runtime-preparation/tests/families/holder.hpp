#pragma once
#include <cstdint>
namespace fixture {
template<class First, class Second>
struct holder {
    First first;
    Second second;
    holder(const First& a, const Second& b) : first(a), second(b) {}
};
template<class Holder>
std::int64_t difference(const Holder& value) {
    return static_cast<std::int64_t>(value.first) - static_cast<std::int64_t>(value.second);
}
}
