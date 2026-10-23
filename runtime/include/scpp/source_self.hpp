#pragma once

#include "scpp/memory.hpp"

namespace scpp {

// v0.2 source-object state. Handle transport never changes this observer.
class source_self_state {
	std::weak_ptr<void> owner_;
public:
	source_self_state() noexcept = default;
	source_self_state(const source_self_state&) noexcept {}
	source_self_state(source_self_state&&) noexcept {}
	source_self_state& operator=(const source_self_state&) noexcept { return *this; }
	source_self_state& operator=(source_self_state&&) noexcept { return *this; }
	void* scpp_self_state_address() noexcept { return this; }
	void bind(const std::shared_ptr<void>& owner) noexcept { owner_ = owner; }
	std::shared_ptr<void> retain() const {
		auto owner = owner_.lock();
		if (!owner) {
			throw runtime_error("Shared self requires a live managed owner.",
				"invalid_shared_self", "scpp::source_self", "retain");
		}
		return owner;
	}
};

// Called only by v0.2 source-class construction lowering, before publication.
template<class T, class... Args>
shared_p<T> source_shared(Args&&... args) {
	auto value = shared<T>(std::forward<Args>(args)...);
	static_cast<source_self_state*>(value->scpp_self_state_address())->bind(value.native_value());
	return value;
}

template<class T>
shared_p<T> retain_source_self(T* receiver) {
	auto owner = static_cast<source_self_state*>(receiver->scpp_self_state_address())->retain();
	return shared_p<T>(std::shared_ptr<T>(std::move(owner), receiver));
}

} // namespace scpp
