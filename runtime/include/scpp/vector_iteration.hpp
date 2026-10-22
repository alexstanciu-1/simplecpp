#pragma once

#include "scpp/iteration_preservation.hpp"
#include "scpp/int_t.hpp"
#include <memory>
#include <vector>

namespace scpp::iteration_detail {

// Allocated only when a value traversal starts. Storage remains live in its
// vector until owner destruction; destruction transfers it without copying or
// allocation so a retained traversal never depends on a dead wrapper.
template<class T>
struct vector_source final : observers {
	std::vector<T> *live;
	std::vector<T> retired;
	explicit vector_source(std::vector<T> &value) noexcept : live(&value) {}
	const std::vector<T> &values() const noexcept { return live ? *live : retired; }
	void retire() noexcept {
		retired.swap(*live);
		live = nullptr;
	}
};

template<class T>
class vector_range final : public observer {
	std::shared_ptr<vector_source<T>> source_;
	std::size_t index_ = 0;
	std::size_t saved_start_ = 0;
	bool consumed_ = false;
	bool preserved_ = false;
	bool prepared_ = false;
	std::vector<T> saved_;
	std::vector<T> pending_;
	std::size_t pending_start_ = 0;

	const T &value() const {
		return preserved_ ? saved_.at(index_ - saved_start_) : source_->values().at(index_);
	}
	bool finished() const noexcept {
		return preserved_ ? index_ >= saved_start_ + saved_.size() : index_ >= source_->values().size();
	}
public:
	explicit vector_range(std::shared_ptr<vector_source<T>> source) : source_(std::move(source)) {
		source_->attach(*this);
	}
	~vector_range() override { detach(); }
	void prepare() override {
		if (preserved_ || prepared_) return;
		pending_start_ = index_ + (consumed_ ? 1 : 0);
		const auto &values = source_->values();
		if (pending_start_ < values.size()) {
			pending_.reserve(values.size() - pending_start_);
			for (auto at = pending_start_; at < values.size(); ++at) pending_.push_back(values[at]);
		}
		prepared_ = true;
	}
	void commit() noexcept override {
		if (!prepared_) return;
		saved_.swap(pending_);
		saved_start_ = pending_start_;
		preserved_ = true;
		prepared_ = false;
	}
	void cancel() noexcept override {
		pending_.clear();
		prepared_ = false;
	}
	struct entry {
		vector_range *range;
		int_t<> key() const { return int_t<>{static_cast<std::int64_t>(range->index_)}; }
		T value_copy() const {
			T result = range->value();
			range->consumed_ = true;
			return result;
		}
	};
	struct sentinel {};
	struct iterator {
		vector_range *range;
		entry operator*() const noexcept { return entry{range}; }
		iterator &operator++() noexcept {
			++range->index_;
			range->consumed_ = false;
			return *this;
		}
		bool operator!=(sentinel) const noexcept { return !range->finished(); }
	};
	iterator begin() noexcept { return iterator{this}; }
	sentinel end() const noexcept { return {}; }
};

} // namespace scpp::iteration_detail
