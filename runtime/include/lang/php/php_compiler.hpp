#pragma once

#include "scpp/compiler.hpp"
#include "operators/probe/probe.hpp"

namespace scpp::detail {
template<class T> struct is_countable_lookup_target<compiler::Storage<T>> : std::true_type {};
template<class T> struct is_countable_lookup_target<compiler::Keyed_Storage<T>> : std::true_type {};
}

// PHP iteration adapts keys to source scalar wrappers; records remain shared handles.
namespace scpp::compiler {
namespace detail {
template<class Collection> class source_range {
	Collection collection_;
public:
	explicit source_range(Collection collection) : collection_(std::move(collection)) {}
	class iterator {
		typename Collection::iterator current_;
	public:
		explicit iterator(typename Collection::iterator current) : current_(std::move(current)) {}
		struct entry {
			typename Collection::key_type index;
			typename Collection::record_handle record;
			auto key() const {
				if constexpr (std::same_as<typename Collection::key_type, std::string>) return scpp::string_t(index);
				else return scpp::int_t<>(index);
			}
			auto value_copy() const { return record; }
		};
		entry operator*() const { auto [key, record] = *current_; return {std::move(key), std::move(record)}; }
		iterator &operator++() { ++current_; return *this; }
		bool operator==(const iterator &other) const { return current_ == other.current_; }
	};
	iterator begin() const { return iterator(collection_.begin()); }
	iterator end() const { return iterator(collection_.end()); }
};
} // namespace detail

template<class T> auto foreach_range(const Storage<T> &collection) { return detail::source_range(collection); }
template<class T> auto foreach_range(const Keyed_Storage<T> &collection) { return detail::source_range(collection); }
} // namespace scpp::compiler

namespace scpp {
template<class T> int_t<> count(const compiler::Storage<T> &collection) { return int_t<>(collection.count()); }
template<class T> int_t<> count(const compiler::Keyed_Storage<T> &collection) { return int_t<>(collection.count()); }
template<class T, class K> bool_t isset(const compiler::Storage<T> &collection, const K &key) { return bool_t(collection.contains(key)); }
template<class T, class K> bool_t isset(const compiler::Keyed_Storage<T> &collection, const K &key) { return bool_t(collection.contains(key)); }
template<class T> bool_t empty(const compiler::Storage<T> &collection) { return bool_t(collection.is_empty()); }
template<class T> bool_t empty(const compiler::Keyed_Storage<T> &collection) { return bool_t(collection.is_empty()); }
template<class T, class K> bool_t empty(const compiler::Storage<T> &collection, const K &key) { return bool_t(!collection.contains(key)); }
template<class T, class K> bool_t empty(const compiler::Keyed_Storage<T> &collection, const K &key) { return bool_t(!collection.contains(key)); }
} // namespace scpp
