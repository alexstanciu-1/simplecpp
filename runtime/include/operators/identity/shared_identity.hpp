#pragma once

#include "scpp/shared_p.hpp"
#include <type_traits>

namespace scpp {

// Compare compatible views of one object, preserving C++ base-pointer adjustment.
// This does not permit downcasts or establish identity between unrelated interfaces.
template <typename T, typename U>
requires (std::is_convertible_v<T *, U *> || std::is_convertible_v<U *, T *>)
inline bool_t identical(const shared_p<T> &left, const shared_p<U> &right) {
	return bool_t(left.get() == right.get());
}


} // namespace scpp
