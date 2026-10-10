#pragma once
#include "scpp/int_t.hpp"

namespace runtime_type_test {
struct receipt { scpp::int_t<> number; };
template<class T> struct parcel {
	T value;
	static parcel make(T input) { return {input}; }
	T read() const { return value; }
	void replace(T input) { value = input; }
	template<class U> U echo_value(U input) { return input; }
};
inline receipt make_receipt(scpp::int_t<> number) { return {number}; }
inline scpp::int_t<> receipt_number(const receipt& value) { return value.number; }
template<class T> parcel<T> pack(T value) { return {value}; }
template<class T> T unpack(const parcel<T>& value) { return value.value; }
inline parcel<receipt> default_parcel(scpp::int_t<> number) { return {{number}}; }
}
