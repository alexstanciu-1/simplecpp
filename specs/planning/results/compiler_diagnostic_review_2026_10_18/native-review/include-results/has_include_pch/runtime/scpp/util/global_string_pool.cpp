#include "scpp/util/global_string_pool.hpp"

#include <stdexcept>

namespace scpp {

global_string_pool &global_string_pool::instance() {
	static global_string_pool pool;
	return pool;
}

global_string_pool::global_string_pool() {
	lookup_.max_load_factor(0.4f);
}

std::uint32_t global_string_pool::intern(const string_t &value) {
	auto it = lookup_.find(value.native_value());
	if (it != lookup_.end()) {
		return it->second | string_key_flag;
	}

	const auto id = static_cast<std::uint32_t>(strings_.size());
	if (id > string_id_mask) {
		throw std::overflow_error("global_string_pool capacity exceeded");
	}

	auto [inserted_it, success] = lookup_.emplace(value.native_value(), id);
	(void)success;
	strings_.push_back(&inserted_it->first);
	return id | string_key_flag;
}

string_t global_string_pool::resolve(std::uint32_t tagged_id) const {
	if (!is_string_id(tagged_id)) {
		throw std::logic_error("global_string_pool::resolve: key is not a string id");
	}

	const auto id = tagged_id & string_id_mask;
	if (id >= strings_.size()) {
		throw std::out_of_range("global_string_pool::resolve: invalid string id");
	}

	return string_t(*strings_[id]);
}

bool global_string_pool::is_string_id(std::uint32_t tagged_id) {
	return (tagged_id & string_key_flag) != 0;
}

} // namespace scpp
