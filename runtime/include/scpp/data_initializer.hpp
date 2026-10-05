#pragma once

#include "scpp/vector_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/support/dynamic_value.hpp"
#include <array>
#include <memory>
#include <optional>
#include <stdexcept>
#include <utility>

namespace scpp {

template <typename T> class initializer_buffer;

// Read-only, non-owning call input. Source exposure requires compiler non-escape checks.
template <typename T>
class initializer_list final {
	const std::optional<T> *items_ = nullptr;
	std::size_t size_ = 0;
	friend class initializer_buffer<T>;
	initializer_list(const std::optional<T> *items, std::size_t size) : items_(items), size_(size) {}

public:
	initializer_list() = default;
	[[nodiscard]] std::size_t size() const noexcept { return size_; }
	[[nodiscard]] int_t<> count() const { return int_t<>{static_cast<std::int64_t>(size_)}; }
	[[nodiscard]] T at(int_t<> index) const {
		const auto position = index.native_value();
		if (position < 0 || static_cast<std::size_t>(position) >= size_)
			throw std::out_of_range("initializer_list index out of range");
		return *items_[static_cast<std::size_t>(position)];
	}

	// Iteration copies values; it never exposes an element reference to source code.
	class iterator final {
		const std::optional<T> *position_;
	public:
		explicit iterator(const std::optional<T> *position) : position_(position) {}
		T operator*() const { return **position_; }
		iterator &operator++() { ++position_; return *this; }
		bool operator!=(const iterator &other) const { return position_ != other.position_; }
	};
	iterator begin() const { return iterator(items_); }
	iterator end() const { return iterator(size_ == 0 ? items_ : items_ + size_); }
};

template <typename TValue, typename TKey = string_t>
struct initializer_entry final {
	const TKey key;
	const TValue value;
};

// One fixed template per element type, not per literal length. Partial construction is RAII-safe.
template <typename T>
class initializer_buffer final {
	using slot = std::optional<T>;
	static constexpr std::size_t local_capacity = (4096 / sizeof(slot) < 32) ? 4096 / sizeof(slot) : 32;
	std::array<slot, local_capacity> local_;
	std::unique_ptr<slot[]> heap_;
	slot *items_;
	std::size_t capacity_;
	std::size_t filled_ = 0;

public:
	explicit initializer_buffer(std::size_t count)
		: heap_(count > local_capacity ? std::make_unique<slot[]>(count) : nullptr),
		  items_(heap_ ? heap_.get() : local_.data()), capacity_(count) {}
	initializer_buffer(const initializer_buffer &) = delete;
	initializer_buffer &operator=(const initializer_buffer &) = delete;
	void append(T value) {
		if (filled_ == capacity_) throw std::out_of_range("initializer_buffer is full");
		items_[filled_].emplace(std::move(value));
		++filled_;
	}
	[[nodiscard]] initializer_list<T> view() const {
		if (filled_ != capacity_) throw std::logic_error("initializer_buffer is incomplete");
		return initializer_list<T>(items_, filled_);
	}
};

namespace initialization_detail {

template <typename Destination, typename T>
void populate_list(Destination &destination, initializer_list<T> input) {
	if constexpr (requires { destination.reserve(input.size()); }) destination.reserve(input.size());
	for (auto value : input) (void)destination.append(value);
}

template <typename Destination, typename V, typename K>
void populate_keyed(Destination &destination, initializer_list<initializer_entry<V, K>> input) {
	if constexpr (requires { destination.reserve(input.size()); }) destination.reserve(input.size());
	for (auto entry : input) {
		// Presence, not truthiness: null/false values still occupy their normalized key.
		if (destination.has(entry.key).native_value())
			throw std::runtime_error("data initialization: duplicate key");
		destination.set(entry.key, entry.value);
	}
}

} // namespace initialization_detail

// No fallback: only explicitly supplied runtime adapters are executable.
template <typename Target> struct data_initializer;

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

template <>
struct data_initializer<detail::dynamic_value> final {
	using value_type = detail::dynamic_value;
	static value_type from_list(initializer_list<value_type> input) {
		auto result = value_type::make_container();
		initialization_detail::populate_list(result.get_table(), input);
		return result;
	}
	static value_type from_key_value(initializer_list<initializer_entry<value_type, mixed_t>> input) {
		auto result = value_type::make_container();
		initialization_detail::populate_keyed(result.get_table(), input);
		return result;
	}
};

template <>
struct data_initializer<mixed_t> final {
	static mixed_t from_list(initializer_list<mixed_t> input) {
		auto storage = std::make_unique<hash_t<mixed_t>>();
		initialization_detail::populate_list(*storage, input);
		return mixed_t{std::move(storage)};
	}
	static mixed_t from_key_value(initializer_list<initializer_entry<mixed_t, mixed_t>> input) {
		auto storage = std::make_unique<hash_t<mixed_t>>();
		initialization_detail::populate_keyed(*storage, input);
		return mixed_t{std::move(storage)};
	}
};

} // namespace scpp
