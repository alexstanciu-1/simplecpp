#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <algorithm>
#include <array>
#include <cstdint>
#include <initializer_list>
#include <stdexcept>
#include <string>

namespace scpp {

template <typename T, std::size_t N>
class fixed_array_t final {
private:
	std::array<T, N> value_{};

	[[noreturn]] void throw_bounds_error(std::int64_t index) const {
		throw runtime_error(
			std::string("fixed array index ") + std::to_string(index) + " is out of bounds for size " + std::to_string(N) + ".",
			"bounds_error",
			"runtime",
			"operator[]",
			{
				{"container", "fixed_array"},
				{"index", std::to_string(index)},
				{"size", std::to_string(N)},
				{"operation", "operator[]"},
			}
		);
	}

	void check_bounds(std::size_t index) const {
		if (index >= N) {
			throw_bounds_error(static_cast<std::int64_t>(index));
		}
	}

public:
	fixed_array_t() = default;

	fixed_array_t(std::initializer_list<T> init) {
		if (init.size() != N) {
			throw runtime_error(
				std::string("fixed_array initializer size ") + std::to_string(init.size()) + " does not match declared size " + std::to_string(N) + ".",
				"fixed_array_size_mismatch",
				"runtime",
				"fixed_array",
				{
					{"container", "fixed_array"},
					{"initializer_size", std::to_string(init.size())},
					{"declared_size", std::to_string(N)},
				}
			);
		}

		std::copy(init.begin(), init.end(), value_.begin());
	}

	[[nodiscard]] static constexpr std::size_t static_size() noexcept {
		return N;
	}

	[[nodiscard]] constexpr std::size_t size() const noexcept {
		return N;
	}

	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(N == 0);
	}

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
		return at(index);
	}

	template <typename U = T>
	[[nodiscard]] U try_ref(std::size_t) const
		requires(!detail::is_shared_p_v<U>)
	{
		throw std::runtime_error("fixed_array_t::try_ref is supported only for shared_p<T> elements in the current safe subset");
	}

	template <typename U = T>
	[[nodiscard]] U try_ref(const int_t<> &) const
		requires(!detail::is_shared_p_v<U>)
	{
		throw std::runtime_error("fixed_array_t::try_ref is supported only for shared_p<T> elements in the current safe subset");
	}

	[[nodiscard]] const std::array<T, N> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::array<T, N> &native_value() noexcept {
		return value_;
	}
};

} // namespace scpp
