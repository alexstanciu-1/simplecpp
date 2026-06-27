#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <cstdint>
#include <initializer_list>

namespace scpp {

// Minimal semantic vector wrapper for v1.
//
// Enforces:
// - sequence storage lives in scpp::vector_t rather than raw std::vector at the language level
// - emptiness checks return bool_t
// - indexed access is bounds-checked because it delegates to std::vector::at
template <typename T>
// Vector wrapper used for the current list-like container subset.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class vector_t final {
private:
	std::vector<T> value_;

	[[noreturn]] void throw_bounds_error(std::int64_t index) const {
		throw runtime_error(
			std::string("vector index ") + std::to_string(index) + " is out of bounds for size " + std::to_string(value_.size()) + ".",
			"bounds_error",
			"runtime",
			"operator[]",
			{
				{"container", "vector"},
				{"index", std::to_string(index)},
				{"size", std::to_string(value_.size())},
				{"operation", "operator[]"},
			}
		);
	}

	void check_bounds(std::size_t index) const {
		if (index >= value_.size()) {
			throw_bounds_error(static_cast<std::int64_t>(index));
		}
	}

public:
	vector_t() = default;

	vector_t(std::initializer_list<T> init)
		: value_(init) {
	}

	// Native-sized length query.
	[[nodiscard]] std::size_t size() const noexcept {
		return value_.size();
	}

	// Native-sized capacity query for memory/performance-aware code.
	[[nodiscard]] std::size_t capacity() const noexcept {
		return value_.capacity();
	}

	[[nodiscard]] std::size_t estimated_storage_bytes() const noexcept {
		return sizeof(*this) + (value_.capacity() * sizeof(T));
	}

	void reserve(std::size_t capacity) {
		value_.reserve(capacity);
	}

	// Runtime boolean wrapper for empty/non-empty state.
	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(value_.empty());
	}

	// Removes all elements.
	void clear() noexcept {
		value_.clear();
	}

	void compact() {
		value_.shrink_to_fit();
	}

	void compact(std::size_t requested_capacity) {
		const auto target_capacity = requested_capacity < value_.size() ? value_.size() : requested_capacity;
		std::vector<T> next;
		next.reserve(target_capacity);
		for (auto &item : value_) {
			next.push_back(std::move(item));
		}
		value_ = std::move(next);
	}

	// Removes one indexed element and compacts later indexes like a sequence.
	[[nodiscard]] bool remove(const int_t<> &index) {
		const auto native = index.native_value();
		if (native < 0) {
			return false;
		}

		const auto offset = static_cast<std::size_t>(native);
		if (offset >= value_.size()) {
			return false;
		}

		value_.erase(value_.begin() + static_cast<std::ptrdiff_t>(offset));
		return true;
	}

	// Implements the runtime unset hook for wrapped vectors.
	// How: the wrapper owns its sequence storage, so unsetting it clears the held elements immediately.
	void _unset_() noexcept {
		clear();
	}

	// Checked element access.
	T &at(std::size_t index) {
		check_bounds(index);
		return value_[index];
	}

	const T &at(std::size_t index) const {
		check_bounds(index);
		return value_[index];
	}

	T &at(const int_t<> &index) {
		const auto native = index.native_value();
		if (native < 0) {
			throw_bounds_error(native);
		}
		return at(static_cast<std::size_t>(native));
	}

	const T &at(const int_t<> &index) const {
		const auto native = index.native_value();
		if (native < 0) {
			throw_bounds_error(native);
		}
		return at(static_cast<std::size_t>(native));
	}

	T &operator[](std::size_t index) {
		return at(index);
	}

	const T &operator[](std::size_t index) const {
		return at(index);
	}

	T &operator[](const int_t<> &index) {
		return at(index);
	}

	const T &operator[](const int_t<> &index) const {
		return at(index);
	}

	// Alias used by generated code where source semantics map to indexing.
	T &index(std::size_t index_value) {
		return at(index_value);
	}

	const T &index(std::size_t index_value) const {
		return at(index_value);
	}

	T &index(const int_t<> &index_value) {
		return at(index_value);
	}

	const T &index(const int_t<> &index_value) const {
		return at(index_value);
	}

	// Restricted escape hatch for copy-stable handle-like element extraction.
	// Current safe subset: only shared_p<T> elements are allowed and the handle is returned by copy.
	template <typename U = T>
	[[nodiscard]] U try_ref(std::size_t index) const
		requires(detail::is_shared_p_v<U>)
	{
		return at(index);
	}

	template <typename U = T>
	[[nodiscard]] U try_ref(const int_t<> &index) const
		requires(detail::is_shared_p_v<U>)
	{
		const auto native = index.native_value();
		if (native < 0) {
			throw_bounds_error(native);
		}
		return at(static_cast<std::size_t>(native));
	}

	template <typename U = T>
	[[nodiscard]] U try_ref(std::size_t) const
		requires(!detail::is_shared_p_v<U>)
	{
		throw std::runtime_error("vector_t::try_ref is supported only for shared_p<T> elements in the current safe subset");
	}

	template <typename U = T>
	[[nodiscard]] U try_ref(const int_t<> &) const
		requires(!detail::is_shared_p_v<U>)
	{
		throw std::runtime_error("vector_t::try_ref is supported only for shared_p<T> elements in the current safe subset");
	}

	// Append by copy or move.
	void append(const T &value) {
		value_.push_back(value);
	}

	void push_back(const T &value) {
		append(value);
	}

	// Appends data while staying in the wrapper domain defined by the spec.
	// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
	void append(T &&value) {
		value_.push_back(std::move(value));
	}

	void push_back(T &&value) {
		append(std::move(value));
	}

	// Controlled access to the native container.
	[[nodiscard]] const std::vector<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::vector<T> &native_value() noexcept {
		return value_;
	}
};

} // namespace scpp
