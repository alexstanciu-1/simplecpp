#pragma once

#include "scpp/shared_p.hpp"

namespace scpp {

// One virtual base per generated object, including interface diamonds. The weak
// control-block observer never owns the object or creates an ownership cycle.
class shared_self : public std::enable_shared_from_this<shared_self> {
public:
	template <typename T>
	shared_p<T> retain_self(T *address) {
		// Aliasing preserves both the original owner and the adjusted subobject
		// address. A constructor/destructor or unmanaged object has no live owner.
		auto owner = weak_from_this().lock();
		if (!owner) {
			throw runtime_error("Shared self requires a live managed owner.",
				"invalid_shared_self", "scpp::shared_self", "retain_self");
		}
		return shared_p<T>(std::shared_ptr<T>(std::move(owner), address));
	}
};

} // namespace scpp
