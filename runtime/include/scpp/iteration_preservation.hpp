#pragma once

#include <initializer_list>

namespace scpp::iteration_detail {

class observers;

// A traversal owns its subscription. Owners never extend traversal lifetime.
class observer {
	friend class observers;
	observer *next_ = nullptr;
	observer **link_ = nullptr;
public:
	observer() = default;
	observer(const observer &) = delete;
	observer &operator=(const observer &) = delete;
	virtual ~observer() { detach(); }
	void detach() noexcept {
		if (!link_) return;
		*link_ = next_;
		if (next_) next_->link_ = link_;
		link_ = nullptr;
		next_ = nullptr;
	}
	virtual void prepare() = 0;
	virtual void commit() noexcept = 0;
	virtual void cancel() noexcept = 0;
};

class observers {
	observer *first_ = nullptr;
public:
	observers() = default;
	observers(const observers &) = delete;
	observers &operator=(const observers &) = delete;
	~observers() { while (first_) first_->detach(); }
	void attach(observer &item) noexcept {
		item.detach();
		item.next_ = first_;
		item.link_ = &first_;
		if (first_) first_->link_ = &item.next_;
		first_ = &item;
	}
	void prepare() { for (auto *item = first_; item; item = item->next_) item->prepare(); }
	void commit() noexcept { for (auto *item = first_; item; item = item->next_) item->commit(); }
	void cancel() noexcept { for (auto *item = first_; item; item = item->next_) item->cancel(); }
};

// Prepare every owner before publishing any cursor transition. In particular,
// failure in a later nested cursor or in the source of a move leaves all live.
inline void preserve(std::initializer_list<observers *> owners) {
	try {
		for (auto *owner : owners) if (owner) owner->prepare();
	} catch (...) {
		for (auto *owner : owners) if (owner) owner->cancel();
		throw;
	}
	for (auto *owner : owners) if (owner) owner->commit();
}

} // namespace scpp::iteration_detail
