#pragma once
#include "scpp/int_t.hpp"

namespace runtime_type_test {
struct receipt { scpp::int_t<> number; };
template<class T> struct parcel { T value; };
inline receipt make_receipt(scpp::int_t<> number) { return {number}; }
inline scpp::int_t<> receipt_number(const receipt& value) { return value.number; }
template<class T> parcel<T> pack(T value) { return {value}; }
template<class T> T unpack(const parcel<T>& value) { return value.value; }
inline parcel<receipt> default_parcel(scpp::int_t<> number) { return {{number}}; }
}
