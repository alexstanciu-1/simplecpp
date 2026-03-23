#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"

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

public:
	vector_t() = default;

	// Native-sized length query.
	[[nodiscard]] std::size_t size() const noexcept {
		return value_.size();
	}

	// Runtime boolean wrapper for empty/non-empty state.
	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(value_.empty());
	}

	// Removes all elements.
	void clear() noexcept {
		value_.clear();
	}

	// Checked element access.
	T &at(std::size_t index) {
		return value_.at(index);
	}

	const T &at(std::size_t index) const {
		return value_.at(index);
	}

	// Alias used by generated code where source semantics map to indexing.
	T &index(std::size_t index_value) {
		return value_.at(index_value);
	}

	const T &index(std::size_t index_value) const {
		return value_.at(index_value);
	}

	// Append by copy or move.
	void append(const T &value) {
		value_.push_back(value);
	}

	// Appends data while staying in the wrapper domain defined by the spec.
	// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
	void append(T &&value) {
		value_.push_back(std::move(value));
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
