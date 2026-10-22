#pragma once

#include "scpp/iteration_preservation.hpp"
#include <memory>

namespace scpp::iteration_detail {

// A boxed source has two identities: its variable binding and its shared table.
// The underlying range observes table mutations; this subscription additionally
// observes replacement of this binding without freezing independent aliases.
template<class Table>
class bound_range final : public observer {
	using range_type = decltype(std::declval<const Table &>().value_range());
	range_type range_;
	std::shared_ptr<observers> binding_;
public:
	bound_range(const Table &table, std::shared_ptr<observers> binding)
		: range_(table.value_range()), binding_(std::move(binding)) {
		binding_->attach(*this);
	}
	~bound_range() override { detach(); }
	void prepare() override { range_.prepare(); }
	void commit() noexcept override { range_.commit(); }
	void cancel() noexcept override { range_.cancel(); }
	auto begin() noexcept { return range_.begin(); }
	auto end() const noexcept { return range_.end(); }
};

} // namespace scpp::iteration_detail
