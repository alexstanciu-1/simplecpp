#pragma once

#include "scpp/data_initializer_input.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/shared_p.hpp"

namespace scpp {

template <typename T>
struct data_initializer<vector_t<T>> final {
	static vector_t<T> from_list(initializer_list<T> input) {
		vector_t<T> result;
		initialization_detail::populate_list(result, input);
		return result;
	}
};

template <typename V, typename K>
struct data_initializer<hash_t<V, K>> final {
	static hash_t<V, K> from_list(initializer_list<V> input) {
		hash_t<V, K> result;
		initialization_detail::populate_list(result, input);
		return result;
	}
	static hash_t<V, K> from_key_value(initializer_list<initializer_entry<V, K>> input) {
		hash_t<V, K> result;
		initialization_detail::populate_keyed(result, input);
		return result;
	}
};

template <typename V, typename K>
struct data_initializer<shared_p<hash_t<V, K>>> final {
	static shared_p<hash_t<V, K>> from_list(initializer_list<V> input) {
		auto storage = std::make_shared<hash_t<V, K>>();
		initialization_detail::populate_list(*storage, input);
		return shared_p<hash_t<V, K>>(std::move(storage));
	}
	static shared_p<hash_t<V, K>> from_key_value(initializer_list<initializer_entry<V, K>> input) {
		auto storage = std::make_shared<hash_t<V, K>>();
		initialization_detail::populate_keyed(*storage, input);
		return shared_p<hash_t<V, K>>(std::move(storage));
	}
};

} // namespace scpp
