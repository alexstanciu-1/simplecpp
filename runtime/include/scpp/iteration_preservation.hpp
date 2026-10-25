#pragma once

#include <initializer_list>
#include <atomic>
#include <memory>
#include <mutex>

namespace scpp::iteration_detail {

class observers;

// A traversal owns its subscription. Owners never extend traversal lifetime.
class observer {
	friend class observers;
	observers *owner_ = nullptr;
	observer *next_ = nullptr;
	observer **link_ = nullptr;
public:
	observer() = default;
	observer(const observer &) = delete;
	observer &operator=(const observer &) = delete;
	virtual ~observer() { detach(); }
	void detach() noexcept;
	virtual void prepare() = 0;
	virtual void commit() noexcept = 0;
	virtual void cancel() noexcept = 0;
};

class observers {
	friend class observer;
	std::mutex subscriptions_;
	observer *first_ = nullptr;
public:
	observers() = default;
	observers(const observers &) = delete;
	observers &operator=(const observers &) = delete;
	~observers() { while (first_) first_->detach(); }
	void attach(observer &item) noexcept {
		item.detach();
		std::lock_guard lock(subscriptions_);
		item.owner_ = this;
		item.next_ = first_;
		item.link_ = &first_;
		if (first_) first_->link_ = &item.next_;
		first_ = &item;
	}
	void prepare() { for (auto *item = first_; item; item = item->next_) item->prepare(); }
	void commit() noexcept { for (auto *item = first_; item; item = item->next_) item->commit(); }
	void cancel() noexcept { for (auto *item = first_; item; item = item->next_) item->cancel(); }
};

// Each traversal belongs to one thread. Concurrent readers only share subscription
// links, not cursor state. Mutation/retirement still requires caller synchronization.
inline void observer::detach() noexcept {
	if (!owner_) return;
	std::lock_guard lock(owner_->subscriptions_);
	*link_ = next_;
	if (next_) next_->link_ = link_;
	owner_ = nullptr;
	link_ = nullptr;
	next_ = nullptr;
}

// Publish one lazily allocated source even when independent read traversals start
// together. Losing candidates have no subscriptions and own no retired storage.
template<class Source, class Storage>
std::shared_ptr<Source> read_source(std::shared_ptr<Source> &slot, Storage &storage) {
	auto source = std::atomic_load_explicit(&slot, std::memory_order_acquire);
	if (source) return source;
	auto candidate = std::make_shared<Source>(storage);
	if (std::atomic_compare_exchange_strong_explicit(&slot, &source, candidate,
		std::memory_order_acq_rel, std::memory_order_acquire)) return candidate;
	return source;
}

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
