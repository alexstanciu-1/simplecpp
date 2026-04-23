#pragma once

#include "operators/isset/isset.hpp"

#include <utility>

namespace scpp::php {

template <typename... Args>
inline auto isset(Args &&...args) {
	return ::scpp::isset(std::forward<Args>(args)...);
}

} // namespace scpp::php
