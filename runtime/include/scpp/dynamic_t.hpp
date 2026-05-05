#pragma once

#include "scpp/detail.hpp"
#include "scpp/shared_p.hpp"

namespace scpp {

// dynamic_t is the shared-identity handle form of hash_t.
// The default dynamic runtime surface remains dynamic_t<mixed_t>.
template <typename T_VALUE = mixed_t, typename T_KEY = typename default_hash_key<T_VALUE>::type>
using dynamic_t = shared_p<hash_t<T_VALUE, T_KEY>>;

template <typename T_VALUE = mixed_t, typename T_KEY = typename default_hash_key<T_VALUE>::type>
struct dynamic_init_t_of final {
	dynamic_t<T_VALUE, T_KEY> value;
};

using dynamic_init_t = dynamic_init_t_of<>;

template <typename T_VALUE = mixed_t, typename T_KEY = typename default_hash_key<T_VALUE>::type>
[[nodiscard]] inline dynamic_init_t_of<T_VALUE, T_KEY> dynamic_box(dynamic_t<T_VALUE, T_KEY> value) {
	return dynamic_init_t_of<T_VALUE, T_KEY>{std::move(value)};
}

} // namespace scpp
