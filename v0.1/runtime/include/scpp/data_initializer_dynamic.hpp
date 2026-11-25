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
		return from_list_adapted(input, [](const value_type &value) -> const value_type & { return value; });
	}
	template <typename Input, typename Adapt>
	static value_type from_list_adapted(initializer_list<Input> input, const Adapt &adapt) {
		auto result = value_type::make_container();
		initialization_detail::populate_list_adapted(result.get_table(), input, adapt);
		return result;
	}
	static value_type from_key_value(initializer_list<initializer_entry<value_type, mixed_t>> input) {
		return from_key_value_adapted(input, [](const initializer_entry<value_type, mixed_t> &entry) -> const initializer_entry<value_type, mixed_t> & { return entry; });
	}
	template <typename Input, typename Adapt>
	static value_type from_key_value_adapted(initializer_list<Input> input, const Adapt &adapt) {
		auto result = value_type::make_container();
		initialization_detail::populate_keyed_adapted(result.get_table(), input, adapt);
		return result;
	}
};

} // namespace scpp
