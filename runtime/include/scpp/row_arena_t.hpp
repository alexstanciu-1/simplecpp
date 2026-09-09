#pragma once

#include "scpp/runtime_error.hpp"
#include "scpp/vector_t.hpp"

#include <cstdint>
#include <limits>
#include <string>
#include <type_traits>
#include <utility>

namespace scpp {

// Stable-id row storage for compiler/model tables.
//
// Default shape:
// - vector_t<T> backing, deliberately simple and readable
// - uint32 ids, 1-based, with 0 reserved as an invalid/null id
//
// Future improvement note:
// If benchmarks show vector relocation or spare capacity dominates resident
// compiler memory, this wrapper can move to chunked/fixed-array pages while
// preserving the public id contract.
template <typename T, typename Id = std::uint32_t>
class row_arena_t final {
	static_assert(std::is_unsigned_v<Id>, "row_arena_t ids must be unsigned integer types");

private:
	vector_t<T> rows_;

	[[nodiscard]] static std::size_t id_to_index(const Id id, const char *context) {
		if (id == 0) {
			throw runtime_error(std::string(context), "row arena id 0 is invalid");
		}
		return static_cast<std::size_t>(id - 1);
	}

	[[nodiscard]] Id next_id() const {
		const auto next = rows_.size() + 1U;
		if (next > static_cast<std::size_t>(std::numeric_limits<Id>::max())) {
			throw runtime_error("row_arena_append", "row arena id range exhausted");
		}
		return static_cast<Id>(next);
	}

public:
	using value_type = T;
	using id_type = Id;

	row_arena_t() = default;

	[[nodiscard]] std::size_t size() const noexcept {
		return rows_.size();
	}

	[[nodiscard]] std::size_t capacity() const noexcept {
		return rows_.capacity();
	}

	[[nodiscard]] Id count_id() const {
		if (rows_.size() > static_cast<std::size_t>(std::numeric_limits<Id>::max())) {
			throw runtime_error("row_arena_count", "row arena count is outside id range");
		}
		return static_cast<Id>(rows_.size());
	}

	void reserve(const std::size_t capacity) {
		rows_.reserve(capacity);
	}

	[[nodiscard]] Id append(const T &row) {
		const auto id = next_id();
		rows_.append(row);
		return id;
	}

	[[nodiscard]] Id append(T &&row) {
		const auto id = next_id();
		rows_.append(std::move(row));
		return id;
	}

	[[nodiscard]] bool can_read(const Id id) const noexcept {
		return id > 0 && static_cast<std::size_t>(id) <= rows_.size();
	}

	T &get(const Id id) {
		return rows_.at(id_to_index(id, "row_arena_get"));
	}

	const T &get(const Id id) const {
		return rows_.at(id_to_index(id, "row_arena_get"));
	}

	void set(const Id id, const T &row) {
		rows_.at(id_to_index(id, "row_arena_set")) = row;
	}

	void set(const Id id, T &&row) {
		rows_.at(id_to_index(id, "row_arena_set")) = std::move(row);
	}

	void clear() noexcept {
		rows_.clear();
	}

	void compact() {
		rows_.compact();
	}

	void compact(const std::size_t requested_capacity) {
		rows_.compact(requested_capacity);
	}
};

} // namespace scpp
