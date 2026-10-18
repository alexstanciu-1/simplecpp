#pragma once

#include "modules/compiler/detail/storage_common.hpp"
#include <list>
#include <memory>
#include <string>
#include <unordered_map>

namespace scpp::compiler {

template<class T>
class Keyed_Storage {
public:
	using record_handle = typename detail::storage_record<T>::handle;
	using key_type = std::string;
private:
	using rows = std::list<std::pair<key_type, record_handle>>;
	struct string_hash {
		using is_transparent = void;
		std::size_t operator()(std::string_view key) const noexcept {
			return std::hash<std::string_view>{}(key);
		}
	};
	struct state {
		rows ordered;
		std::unordered_map<key_type, typename rows::iterator, string_hash, std::equal_to<>> index;
	};
	std::shared_ptr<state> data_;

	void insert(std::string_view key, record_handle record) {
		data_->ordered.emplace_back(key, std::move(record));
		auto row = std::prev(data_->ordered.end());
		try {
			data_->index.emplace(row->first, row);
		} catch (...) {
			// Complete one container insertion atomically across its two structures.
			data_->ordered.pop_back();
			throw;
		}
	}
public:
	template<detail::storage_integer I = std::int64_t>
	explicit Keyed_Storage(I capacity = 0) : data_(std::make_shared<state>()) { reserve(capacity); }
	Keyed_Storage(const Keyed_Storage &) = default;
	Keyed_Storage &operator=(const Keyed_Storage &) = default;

	void reserve(detail::storage_integer auto capacity) {
		const auto size = detail::storage_capacity(capacity);
		if (size > data_->index.bucket_count() * data_->index.max_load_factor()) data_->index.reserve(size);
	}
	[[nodiscard]] std::size_t count() const noexcept { return data_->index.size(); }
	[[nodiscard]] bool is_empty() const noexcept { return count() == 0; }
	[[nodiscard]] bool contains(const detail::storage_string auto &key) const {
		return data_->index.contains(detail::storage_key(key));
	}
	[[nodiscard]] record_handle read(const detail::storage_string auto &key) const {
		const auto found = data_->index.find(detail::storage_key(key));
		if (found == data_->index.end()) throw std::out_of_range("Keyed_Storage key is absent");
		return found->second->second;
	}
	void add(const detail::storage_string auto &key, record_handle record) {
		detail::storage_record<T>::validate(record);
		if (contains(key)) throw std::invalid_argument("Keyed_Storage key already exists");
		insert(detail::storage_key(key), std::move(record));
	}
	// Native entry point for keyed assignment (insert or replace).
	void set(const detail::storage_string auto &key, record_handle record) {
		detail::storage_record<T>::validate(record);
		const auto found = data_->index.find(detail::storage_key(key));
		if (found == data_->index.end()) insert(detail::storage_key(key), std::move(record));
		else found->second->second = std::move(record);
	}
	void replace(const detail::storage_string auto &key, record_handle record) {
		detail::storage_record<T>::validate(record);
		const auto found = data_->index.find(detail::storage_key(key));
		if (found == data_->index.end()) throw std::out_of_range("Keyed_Storage key is absent");
		found->second->second = std::move(record);
	}
	record_handle assign(const detail::storage_string auto &key, record_handle record) {
		set(key, record);
		return record;
	}
	void remove(const detail::storage_string auto &key) {
		if (!contains(key)) throw std::out_of_range("Keyed_Storage key is absent");
		unset(key);
	}
	void unset(const detail::storage_string auto &key) {
		const auto found = data_->index.find(detail::storage_key(key));
		if (found == data_->index.end()) return;
		data_->ordered.erase(found->second);
		data_->index.erase(found);
	}
	class iterator {
		std::shared_ptr<state> data_;
		typename rows::const_iterator row_;
	public:
		iterator(std::shared_ptr<state> data, typename rows::const_iterator row) : data_(std::move(data)), row_(row) {}
		auto operator*() const { return *row_; }
		iterator &operator++() { ++row_; return *this; }
		bool operator==(const iterator &other) const { return data_ == other.data_ && row_ == other.row_; }
	};
	iterator begin() const { return iterator(data_, data_->ordered.begin()); }
	iterator end() const { return iterator(data_, data_->ordered.end()); }

	template<class F> void for_each(F &&visit) const {
		for (auto [key, record] : *this) visit(std::move(key), std::move(record));
	}
};

} // namespace scpp::compiler
