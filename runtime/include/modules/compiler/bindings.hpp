#pragma once

// v0.2 adapters retain the provider's shared membership and owning record reads.
#include "scpp/compiler.hpp"
#include "scpp/keyed_path.hpp"
#include "scpp/value_iteration.hpp"

namespace scpp::compiler {
template<class T> int_t<> storage_count(const Storage<T>& value) { return int_t<>(value.count()); }
template<class T> int_t<> storage_count(const Keyed_Storage<T>& value) { return int_t<>(value.count()); }
template<class T> bool_t storage_empty(const Storage<T>& value) { return bool_t(value.is_empty()); }
template<class T> bool_t storage_empty(const Keyed_Storage<T>& value) { return bool_t(value.is_empty()); }

namespace detail {
template<class Collection> class storage_value_range {
	Collection source_;
public:
	explicit storage_value_range(Collection source) : source_(std::move(source)) {}
	class iterator {
		typename Collection::iterator position_;
	public:
		explicit iterator(typename Collection::iterator position) : position_(std::move(position)) {}
		struct entry {
			typename Collection::key_type index;
			typename Collection::record_handle record;
			auto key() const {
				if constexpr (std::same_as<typename Collection::key_type, std::string>) return string_t(index);
				else return int_t<>(index);
			}
			auto value_copy() const { return record; }
		};
		entry operator*() const { auto [key, record] = *position_; return {std::move(key), std::move(record)}; }
		iterator& operator++() { ++position_; return *this; }
		bool operator==(const iterator& other) const { return position_ == other.position_; }
	};
	iterator begin() const { return iterator(source_.begin()); }
	iterator end() const { return iterator(source_.end()); }
};
}
}

namespace scpp {
template<class T, class K> auto checked_index_read(const compiler::Storage<T>& source, const K& key) { return source.read(key); }
template<class T, class K> auto checked_index_read(const compiler::Keyed_Storage<T>& source, const K& key) { return source.read(key); }
template<class T, class K> void index_store(compiler::Storage<T>& source, const K& key, const shared_p<T>& value) { source.replace(key, value); }
template<class T, class K> void index_store(compiler::Keyed_Storage<T>& source, const K& key, const shared_p<T>& value) { source.set(key, value); }
template<class T> void index_append(compiler::Storage<T>& source, const shared_p<T>& value) { (void)source.append(value); }
template<class T> auto value_iteration(const compiler::Storage<T>& source) { return compiler::detail::storage_value_range(source); }
template<class T> auto value_iteration(const compiler::Keyed_Storage<T>& source) { return compiler::detail::storage_value_range(source); }
template<class T, class K> bool keyed_present(const compiler::Storage<T>* source, const K& key, bool) { return source && source->contains(key); }
template<class T, class K> bool keyed_present(const compiler::Keyed_Storage<T>* source, const K& key, bool) { return source && source->contains(key); }
// Presence is sufficient: these collections never contain null record handles.
template<class T, class K> bool_t keyed_probe(const compiler::Storage<T>* source, const K& key, bool emptiness) {
	const bool present = source && source->contains(key);
	if (!present && emptiness) detail::throw_invalid_empty();
	return bool_t(emptiness ? false : present);
}
template<class T, class K> bool_t keyed_probe(const compiler::Keyed_Storage<T>* source, const K& key, bool emptiness) {
	const bool present = source && source->contains(key);
	if (!present && emptiness) detail::throw_invalid_empty();
	return bool_t(emptiness ? false : present);
}
template<class T, class K> void keyed_erase(compiler::Storage<T>& source, const K& key) { source.unset(key); }
template<class T, class K> void keyed_erase(compiler::Keyed_Storage<T>& source, const K& key) { source.unset(key); }
}
