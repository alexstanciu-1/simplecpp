#pragma once

#include "scpp/data_initializer_input.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"

namespace scpp {

template <>
struct data_initializer<mixed_t> final {
	static mixed_t from_list(initializer_list<mixed_t> input) {
		return from_list_adapted(input, [](const mixed_t &value) -> const mixed_t & { return value; });
	}
	template <typename Input, typename Adapt>
	static mixed_t from_list_adapted(initializer_list<Input> input, const Adapt &adapt) {
		auto storage = std::make_unique<hash_t<mixed_t>>();
		initialization_detail::populate_list_adapted(*storage, input, adapt);
		return mixed_t{std::move(storage)};
	}
	static mixed_t from_key_value(initializer_list<initializer_entry<mixed_t, mixed_t>> input) {
		return from_key_value_adapted(input, [](const initializer_entry<mixed_t, mixed_t> &entry) -> const initializer_entry<mixed_t, mixed_t> & { return entry; });
	}
	template <typename Input, typename Adapt>
	static mixed_t from_key_value_adapted(initializer_list<Input> input, const Adapt &adapt) {
		auto storage = std::make_unique<hash_t<mixed_t>>();
		initialization_detail::populate_keyed_adapted(*storage, input, adapt);
		return mixed_t{std::move(storage)};
	}
};

} // namespace scpp
