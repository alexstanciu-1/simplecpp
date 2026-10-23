#pragma once
#include "scpp/index_write.hpp"
#include "scpp/value_iteration.hpp"
#include "scpp/data_initializer.hpp"

namespace protocol_proof {
// A distinct provider type, not an alias or a compiler-recognized container name.
template <typename T> struct tray { scpp::vector_t<T> entries; };
template <typename T> tray<T> make_tray(const T &value) {
	tray<T> result;
	result.entries.append(value);
	return result;
}
template <typename Target> struct initializer;
template <typename T> struct initializer<tray<T>> {
	static tray<T> from_list(scpp::initializer_list<T> input) {
		tray<T> result;
		for (auto value : input) result.entries.append(value);
		return result;
	}
	static tray<T> from_key_value(scpp::initializer_list<scpp::initializer_entry<T, scpp::int_t<>>> input) {
		tray<T> result;
		std::int64_t expected = 0;
		for (auto entry : input) {
			// This provider requires consecutive keys; duplicates fail rather than overwrite.
			if (entry.key.native_value() != expected++) throw std::runtime_error("tray requires consecutive unique keys");
			result.entries.append(entry.value);
		}
		return result;
	}
};
}

// The provider implements only the operations promised by its metadata.
namespace scpp {
template <typename T, typename K> T checked_index_read(const protocol_proof::tray<T> &source, const K &key) {
	return source.entries.at(key);
}
template <typename T> void index_store(protocol_proof::tray<T> &target, const int_t<> &key, const T &value) {
	target.entries.at(key) = value;
}
template <typename T> void index_append(protocol_proof::tray<T> &target, const T &value) {
	target.entries.append(value);
}
template <typename T> auto value_iteration(const protocol_proof::tray<T> &source) {
	return foreach_range(source.entries);
}
}
