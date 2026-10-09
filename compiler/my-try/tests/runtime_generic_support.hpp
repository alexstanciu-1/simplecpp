#pragma once
#include "scpp/vector_t.hpp"
#include "scpp/bool_t.hpp"
#include <utility>

namespace runtime_generic_test {
template<class T> T identity(T value) { return value; }
template<class T> scpp::bool_t same(T left, T right) { return scpp::bool_t{left == right}; }
template<class T> scpp::vector_t<T> wrap(T value) {
	return scpp::vector_t<T>{std::move(value)};
}
}
