#pragma once

#include "scpp/data_initializer_input.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"

namespace scpp {

template <>
struct data_initializer<detail::dynamic_value> final {
	using value_type = detail::dynamic_value;
	static value_type from_list(initializer_list<value_type> input) {
		auto result = value_type::make_container();
		initialization_detail::populate_list(result.get_table(), input);
		return result;
	}
	static value_type from_key_value(initializer_list<initializer_entry<value_type, mixed_t>> input) {
		auto result = value_type::make_container();
		initialization_detail::populate_keyed(result.get_table(), input);
		return result;
	}
};

} // namespace scpp
