#pragma once

#include "modules/compiler/detail/position_store.hpp"
#include <optional>
#include <string>
#include <unordered_map>

namespace scpp::compiler::detail {

template<bool StringKey> class primary_index;

// Empty policy: numeric Storage carries neither string slots nor hash buckets.
template<> class primary_index<false> final {
public:
	void validate_append(const std::optional<std::string> &key) const {
		if (key) throw std::invalid_argument("Numeric Storage does not accept a string key");
	}
	void reserve(storage_position) {}
	void prepare_append() {}
	storage_position candidate(storage_position position, const std::optional<std::string> &) const { return position; }
	storage_position key_at(storage_position position) const { return position; }
	void append(storage_position, storage_position) {}
	void remove(storage_position, storage_position) {}
	std::optional<storage_position> find(const std::string &) const {
		throw std::invalid_argument("Numeric Storage requires an integer primary key");
	}
};

template<> class primary_index<true> final {
	std::unordered_map<std::string, storage_position> index_;
	position_store<std::string> keys_;

	void reserve_index(std::size_t size) {
		if (size > index_.max_size()) throw std::length_error("Compiler key index allocation limit");
		// Never shrink on reserve; unordered_map::reserve alone may shrink buckets.
		if (static_cast<long double>(size) >
			static_cast<long double>(index_.bucket_count()) * index_.max_load_factor()) index_.reserve(size);
	}
public:
	void validate_append(const std::optional<std::string> &key) const {
		if (!key) throw std::invalid_argument("String Storage requires an explicit key");
		if (index_.contains(*key)) throw std::invalid_argument("Duplicate compiler Storage key");
	}
	void reserve(storage_position capacity) {
		const auto size = position_store<std::string>::checked_size(capacity);
		keys_.reserve(capacity);
		reserve_index(size);
	}
	void prepare_append() {
		keys_.prepare_append();
		if (index_.size() == index_.max_size()) throw std::length_error("Compiler key index allocation limit");
		if (static_cast<long double>(index_.size() + 1) >
			static_cast<long double>(index_.bucket_count()) * index_.max_load_factor()) {
			const auto size = index_.size();
			reserve_index(size > index_.max_size() / 2 ? index_.max_size()
				: std::min(index_.max_size(), std::max<std::size_t>(4, size * 2)));
		}
	}
	std::string candidate(storage_position, const std::optional<std::string> &key) const { return *key; }
	std::string key_at(storage_position position) const { return keys_.snapshot(position); }
	void append(storage_position position, const std::string &key) {
		// Node/key allocation is inside the guarded write phase. A failure leaves
		// the owner failed, even if this particular insertion changed no entries.
		index_.emplace(key, position);
		keys_.append(key);
	}
	void remove(storage_position position, const std::string &key) {
		index_.erase(key);
		keys_.remove(position);
	}
	std::optional<storage_position> find(const std::string &key) const {
		const auto entry = index_.find(key);
		if (entry == index_.end()) return std::nullopt;
		return entry->second;
	}
};

} // namespace scpp::compiler::detail
