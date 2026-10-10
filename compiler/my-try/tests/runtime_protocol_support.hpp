#pragma once
#include "scpp/index_write.hpp"
#include "scpp/value_iteration.hpp"

namespace protocol_proof {
// A distinct provider type, not an alias or a compiler-recognized container name.
template <typename T> struct tray { scpp::vector_t<T> entries; };
template <typename T> tray<T> make_tray(const T &value) {
	tray<T> result;
	result.entries.append(value);
	return result;
}
}

// The provider implements only the operations promised by its metadata.
namespace scpp {
template <typename T> T checked_index_read(const protocol_proof::tray<T> &source, const int_t<> &key) {
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
