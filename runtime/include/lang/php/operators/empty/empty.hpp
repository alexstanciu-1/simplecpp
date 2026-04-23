#pragma once

#include "operators/empty/empty.hpp"

#include <utility>

namespace scpp::php {

template <typename... Args>
inline auto empty(Args &&...args) {
	return ::scpp::empty(std::forward<Args>(args)...);
}

} // namespace scpp::php
