#pragma once

#include "scpp/detail.hpp"
#include "scpp/nullopt_t.hpp"

namespace scpp::detail {

// One policy for shared-object value boundaries; no scalar coercions or downcasts.
template<class T> struct shared_boundary {
	static constexpr bool supported = false;
};
template<class T> struct shared_boundary<shared_p<T>> {
	static constexpr bool supported = true;
	static constexpr bool optional = false;
	using object_type = T;
	using payload_type = shared_p<T>;
};
template<class T> struct shared_boundary<nullable<shared_p<T>>> {
	static constexpr bool supported = true;
	static constexpr bool optional = true;
	using object_type = T;
	using payload_type = shared_p<T>;
};

template<class To, class From>
inline constexpr bool shared_boundary_convertible = [] {
	if constexpr (shared_boundary<To>::supported && shared_boundary<From>::supported) {
		return std::is_convertible_v<typename shared_boundary<From>::object_type *,
			typename shared_boundary<To>::object_type *>;
	} else {
		return false;
	}
}();

// Complete conversion before assignment. Optional absence is preserved only for
// optional destinations; required destinations use the existing checked unwrap.
template<class To, class From> requires shared_boundary_convertible<To, From>
To convert_shared_boundary(const From &source) {
	if constexpr (shared_boundary<From>::optional) {
		if constexpr (shared_boundary<To>::optional) {
			if (!source.has_value().native_value()) { return To(nullopt_t{}); }
		}
		return convert_shared_boundary<To>(source.require_value(
			"cast<To>(nullable) cannot convert an empty nullable to a required value"));
	} else if constexpr (shared_boundary<To>::optional) {
		using payload = typename shared_boundary<To>::payload_type;
		return To(convert_shared_boundary<payload>(source));
	} else {
		return To(source.native_value());
	}
}

} // namespace scpp::detail
