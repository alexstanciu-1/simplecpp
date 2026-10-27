#pragma once

#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include <array>
#include <cstddef>
#include <cstdint>
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

	// Internal boundary adaptation: return by value even when the adapter borrows.
	// A returned C++ reference is copied before this synchronous call ends.
	template <typename Adapt>
	auto copy_adapted_at(std::size_t position, const Adapt &adapt) const {
		if (position >= this->size_) throw std::out_of_range("initializer_list index out of range");
		return adapt(*this->items_[position]);
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

template <typename Destination, typename T, typename Adapt>
void populate_list_adapted(Destination &destination, initializer_list<T> input, const Adapt &adapt) {
	if constexpr (requires { destination.reserve(input.size()); }) destination.reserve(input.size());
	for (std::size_t index = 0; index < input.size(); ++index) {
		auto value = input.copy_adapted_at(index, adapt);
		(void)destination.append(value);
	}
}

template <typename Destination, typename T>
void populate_list(Destination &destination, initializer_list<T> input) {
	populate_list_adapted(destination, input, [](const T &value) -> const T & { return value; });
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

} // namespace scpp
