#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/vector_t.hpp"

#include <algorithm>
#include <cstddef>
#include <optional>
#include <string>
#include <utility>

namespace scpp {

// Ring-buffer queue for compiler worklists.
//
// Purpose:
// - avoid vector front-removal during dirty/dependency traversal
// - retain capacity after clear for resident/warm compiler loops
// - keep the first shape simple before promoting to a broader collections module
template <typename T>
class work_queue_t final {
private:
	vector_t<std::optional<T>> slots_;
	std::size_t head_ = 0;
	std::size_t count_ = 0;

	[[nodiscard]] std::size_t physical_index(const std::size_t logical_index) const noexcept {
		return (head_ + logical_index) % slots_.size();
	}

	void rebuild_with_capacity(const std::size_t requested_capacity) {
		const auto next_capacity = std::max(requested_capacity, count_);
		std::vector<std::optional<T>> next;
		next.resize(next_capacity);
		for (std::size_t i = 0; i < count_; ++i) {
			next[i] = std::move(slots_.native_value()[physical_index(i)]);
		}
		slots_.native_value() = std::move(next);
		head_ = 0;
	}

	void ensure_capacity_for_one_more() {
		if (count_ < slots_.size()) {
			return;
		}
		const auto next_capacity = slots_.size() == 0 ? std::size_t{4} : slots_.size() * 2U;
		rebuild_with_capacity(next_capacity);
	}

public:
	work_queue_t() = default;

	explicit work_queue_t(const std::size_t initial_capacity) {
		reserve(initial_capacity);
	}

	[[nodiscard]] std::size_t count() const noexcept {
		return count_;
	}

	[[nodiscard]] std::size_t capacity() const noexcept {
		return slots_.size();
	}

	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(count_ == 0);
	}

	void reserve(const std::size_t capacity) {
		if (capacity <= slots_.size()) {
			return;
		}
		rebuild_with_capacity(capacity);
	}

	void push_back(const T &value) {
		ensure_capacity_for_one_more();
		slots_.native_value()[physical_index(count_)] = value;
		++count_;
	}

	void push_back(T &&value) {
		ensure_capacity_for_one_more();
		slots_.native_value()[physical_index(count_)] = std::move(value);
		++count_;
	}

	[[nodiscard]] T pop_front() {
		if (count_ == 0) {
			throw runtime_error("work_queue_pop_front: queue is empty", "empty_queue");
		}
		auto &slot = slots_.native_value()[head_];
		T value = std::move(*slot);
		slot.reset();
		head_ = (head_ + 1U) % slots_.size();
		--count_;
		if (count_ == 0) {
			head_ = 0;
		}
		return value;
	}

	void clear() noexcept {
		for (auto &slot : slots_.native_value()) {
			slot.reset();
		}
		head_ = 0;
		count_ = 0;
	}
};

} // namespace scpp
