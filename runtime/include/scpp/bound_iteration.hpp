#pragma once

#include "scpp/iteration_preservation.hpp"
#include <memory>

namespace scpp::iteration_detail {

template<class Table> class bound_range;

// Tracks only cursors belonging to one boxed variable. Retargeting after a COW
// clone does not preserve entries and never subscribes independent copies.
template<class Table>
class binding final : public observers {
	friend class bound_range<Table>;
	bound_range<Table> *first_ = nullptr;
public:
	void retarget(const Table &table) {
		auto source = table.iteration_source(); // Finish allocation before changing any cursor.
		for (auto *item = first_; item; item = item->next_) item->range_.rebind(source);
	}
};

// A boxed source has two identities: its variable binding and its shared table.
// The underlying range observes table mutations; this subscription additionally
// observes replacement of this binding without freezing independent aliases.
template<class Table>
class bound_range final : public observer {
	friend class binding<Table>;
	using range_type = decltype(std::declval<const Table &>().value_range());
	range_type range_;
	std::shared_ptr<binding<Table>> binding_;
	bound_range *next_ = nullptr;
	bound_range **link_ = nullptr;
public:
	bound_range(const Table &table, std::shared_ptr<binding<Table>> owner)
		: range_(table.value_range()), binding_(std::move(owner)) {
		binding_->attach(*this);
		next_ = binding_->first_;
		link_ = &binding_->first_;
		if (next_) next_->link_ = &next_;
		binding_->first_ = this;
	}
	~bound_range() override {
		detach();
		*link_ = next_;
		if (next_) next_->link_ = link_;
	}
	void prepare() override { range_.prepare(); }
	void commit() noexcept override { range_.commit(); }
	void cancel() noexcept override { range_.cancel(); }
	auto begin() noexcept { return range_.begin(); }
	auto end() const noexcept { return range_.end(); }
};

} // namespace scpp::iteration_detail
