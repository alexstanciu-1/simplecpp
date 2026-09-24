#pragma once

#include <algorithm>
#include <cstddef>
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>
#include <vector>

namespace scpp::compiler {

using storage_position = std::int64_t;

namespace detail {

inline storage_position checked_append_position(std::uint64_t extent) {
	if (extent >= static_cast<std::uint64_t>(std::numeric_limits<storage_position>::max()))
		throw std::overflow_error("Compiler storage positions exhausted");
	return static_cast<storage_position>(extent);
}

// Dense values; only the position directory contains holes. Physical row moves
// never change public positions. This helper does not expose native references.
template<class T>
class position_store final {
	static_assert(std::is_nothrow_move_constructible_v<T>);
	static_assert(std::is_nothrow_move_assignable_v<T>);
	static_assert(std::is_nothrow_destructible_v<T>);
	static constexpr auto absent = std::numeric_limits<std::size_t>::max();
	std::vector<T> values_;
	std::vector<storage_position> positions_;
	std::vector<std::size_t> rows_;

	template<class V> static void prepare_one(V &buffer) {
		if (buffer.size() < buffer.capacity()) return;
		if (buffer.size() == buffer.max_size()) throw std::length_error("Compiler storage allocation limit");
		const auto limit = buffer.max_size();
		const auto grown = buffer.capacity() > limit / 2 ? limit
			: std::min(limit, std::max<std::size_t>(4, buffer.capacity() * 2));
		buffer.reserve(std::max(buffer.size() + 1, grown));
	}

	std::size_t row(storage_position position) const {
		if (!has(position)) throw std::out_of_range("Unknown compiler storage position");
		return rows_[static_cast<std::size_t>(position)];
	}

public:
	static std::size_t checked_size(storage_position size) {
		if (size < 0) throw std::invalid_argument("Negative compiler storage capacity");
		if (static_cast<std::uint64_t>(size) > std::numeric_limits<std::size_t>::max())
			throw std::length_error("Compiler storage capacity does not fit native size");
		return static_cast<std::size_t>(size);
	}

	void reserve(storage_position capacity) {
		const auto size = checked_size(capacity);
		if (size > values_.max_size() || size > positions_.max_size() || size > rows_.max_size())
			throw std::length_error("Compiler storage capacity exceeds allocation limit");
		values_.reserve(size);
		positions_.reserve(size);
		rows_.reserve(size);
	}

	void prepare_append() {
		checked_append_position(rows_.size());
		// Grow each buffer geometrically by its own extent. Historical holes must
		// not cause allocation of unused record capacity during low-live-count churn.
		prepare_one(values_);
		prepare_one(positions_);
		prepare_one(rows_);
	}

	storage_position next_position() const { return static_cast<storage_position>(rows_.size()); }
	std::size_t size() const noexcept { return values_.size(); }
	bool has(storage_position position) const noexcept {
		return position >= 0 && static_cast<std::uint64_t>(position) < rows_.size()
			&& rows_[static_cast<std::size_t>(position)] != absent;
	}
	T snapshot(storage_position position) const { return values_[row(position)]; }

	// Requires successful preparation; supported T makes the commit non-throwing.
	storage_position append(T value) {
		const auto position = next_position();
		rows_.push_back(values_.size());
		positions_.push_back(position);
		values_.push_back(std::move(value));
		return position;
	}
	void replace(storage_position position, T value) { values_[row(position)] = std::move(value); }
	void remove(storage_position position) {
		const auto index = row(position);
		const auto last = values_.size() - 1;
		if (index != last) {
			values_[index] = std::move(values_[last]);
			positions_[index] = positions_[last];
			rows_[static_cast<std::size_t>(positions_[index])] = index;
		}
		values_.pop_back();
		positions_.pop_back();
		rows_[static_cast<std::size_t>(position)] = absent;
	}
	template<class R, class M>
	M field(storage_position position, M R::*member) const { return values_[row(position)].*member; }
	template<class R, class M>
	void set_field(storage_position position, M R::*member, M value) {
		values_[row(position)].*member = std::move(value);
	}
};

// Shared mutation protocol for owners and views. Validation/preparation happens
// before write(); escaping write exceptions poison only this state.
class mutation_state {
	bool failed_ = false;
	bool mutating_ = false;
	mutable std::size_t readers_ = 0;
public:
	void assert_usable() const {
		if (failed_) throw std::logic_error("Compiler collection failed; rebuild its model segment");
	}
	class mutation final {
		mutation_state &state_;
	public:
		explicit mutation(mutation_state &state) : state_(state) {
			state_.assert_usable();
			if (state_.mutating_ || state_.readers_) throw std::logic_error("Recursive compiler collection mutation");
			state_.mutating_ = true;
		}
		mutation(const mutation &) = delete;
		~mutation() { state_.mutating_ = false; }
		template<class Fn> decltype(auto) write(Fn &&fn) {
			try { return fn(); }
			catch (...) { state_.failed_ = true; throw; }
		}
	};
	class traversal final {
		const mutation_state &state_;
	public:
		explicit traversal(const mutation_state &state) : state_(state) {
			state_.assert_usable();
			++state_.readers_;
		}
		traversal(const traversal &) = delete;
		~traversal() { --state_.readers_; }
	};
};

} // namespace detail
} // namespace scpp::compiler
