#pragma once

#include "scpp/iteration_preservation.hpp"
#include <cstdint>
#include <memory>
#include <vector>

namespace scpp::iteration_detail {

// The empty retirement shell is allocated with the first traversal state.
// Destruction transfers storage into it without allocation or element copying.
template<class Table>
struct table_source final : observers {
	Table *live;
	Table retired;
	explicit table_source(Table &value) noexcept : live(&value) {}
	void retire() noexcept {
		live->swap_iteration_storage(retired);
		live = &retired;
	}
};

template<class Table, class Key, class Value>
class table_range final : public observer {
	struct saved_entry { Key key; Value value; };
	std::shared_ptr<table_source<Table>> source_;
	std::uint32_t position_ = 0;
	std::size_t saved_position_ = 0;
	bool consumed_ = false;
	bool preserved_ = false;
	bool prepared_ = false;
	bool skip_saved_increment_ = false;
	std::vector<saved_entry> saved_;
	std::vector<saved_entry> pending_;

	void skip_dead() noexcept {
		const auto &table = *source_->live;
		while (position_ < table.values_.size() && !table.is_live_physical_index(position_)) ++position_;
	}
	bool finished() const noexcept {
		return preserved_ ? saved_position_ >= saved_.size() : position_ >= source_->live->values_.size();
	}
	Key key() const {
		return preserved_ ? saved_.at(saved_position_).key : source_->live->key_from_physical_index(position_);
	}
	Value value_copy() {
		Value result = preserved_ ? saved_.at(saved_position_).value : source_->live->value_from_physical_index(position_);
		consumed_ = true;
		return result;
	}
	void advance() noexcept {
		if (preserved_) {
			if (!skip_saved_increment_) ++saved_position_;
			skip_saved_increment_ = false;
		} else {
			++position_;
			skip_dead();
		}
		consumed_ = false;
	}
public:
	explicit table_range(std::shared_ptr<table_source<Table>> source) : source_(std::move(source)) {
		skip_dead();
		source_->attach(*this);
	}
	~table_range() override { detach(); }
	void prepare() override {
		if (prepared_ || preserved_) return;
		const auto &table = *source_->live;
		for (auto at = position_ + (consumed_ ? 1 : 0); at < table.values_.size(); ++at) {
			if (table.is_live_physical_index(at))
				pending_.push_back({table.key_from_physical_index(at), table.value_from_physical_index(at)});
		}
		prepared_ = true;
	}
	void commit() noexcept override {
		if (!prepared_) return;
		saved_.swap(pending_);
		preserved_ = true;
		skip_saved_increment_ = consumed_;
		prepared_ = false;
	}
	void cancel() noexcept override {
		pending_.clear();
		prepared_ = false;
	}
	struct entry {
		table_range *range;
		Key key() const { return range->key(); }
		Value value_copy() const { return range->value_copy(); }
	};
	struct sentinel {};
	struct iterator {
		table_range *range;
		entry operator*() const noexcept { return entry{range}; }
		iterator &operator++() noexcept { range->advance(); return *this; }
		bool operator!=(sentinel) const noexcept { return !range->finished(); }
	};
	iterator begin() noexcept { return iterator{this}; }
	sentinel end() const noexcept { return {}; }
};

} // namespace scpp::iteration_detail
