#pragma once

#include "scpp/shared_p.hpp"

namespace scpp {

class mixed_t;
template <typename T_VALUE> class hash_t;

// dynamic_t is the public runtime handle for shared dynamic-object storage.
// v1 intentionally aliases the shared hash payload while keeping runtime semantics distinct.
using dynamic_t = shared_p<hash_t<mixed_t>>;

struct dynamic_init_t final {
	dynamic_t value;
};

[[nodiscard]] inline dynamic_init_t dynamic_box(dynamic_t value) {
	return dynamic_init_t{std::move(value)};
}

} // namespace scpp
