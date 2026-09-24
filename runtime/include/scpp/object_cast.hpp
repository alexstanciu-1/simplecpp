#pragma once

#include "scpp/shared_p.hpp"
#include "scpp/nullable.hpp"

namespace scpp {

// Identity-preserving object tests. No record copies or ownership transfer.
template <typename To, typename From>
[[nodiscard]] bool_t object_is(const shared_p<From> &value) noexcept {
	if constexpr (std::is_convertible_v<From *, To *>) {
		return bool_t(value.get() != nullptr);
	} else if constexpr (std::is_polymorphic_v<From>) {
		return bool_t(dynamic_cast<To *>(value.get()) != nullptr);
	} else {
		static_assert(std::is_polymorphic_v<From>, "Object narrowing requires a polymorphic interface or base");
		return bool_t(false);
	}
}

template <typename To, typename From>
[[nodiscard]] bool_t object_is(const nullable<shared_p<From>> &value) noexcept {
	if (!static_cast<bool>(value.has_value())) { return bool_t(false); }
	return object_is<To>(value.value());
}

template <typename To>
[[nodiscard]] bool_t object_is(null_t) noexcept { return bool_t(false); }

template <typename To>
[[nodiscard]] shared_p<To> checked_object_cast(null_t) {
	throw runtime_error("Object cast requires a present compatible object", "invalid_object_cast", "scpp::object_cast", "checked_object_cast");
}

// A required checked cast retains the original shared control block.
template <typename To, typename From>
[[nodiscard]] shared_p<To> checked_object_cast(const shared_p<From> &value) {
	std::shared_ptr<To> result;
	if constexpr (std::is_convertible_v<From *, To *>) {
		result = std::static_pointer_cast<To>(value.native_value());
	} else if constexpr (std::is_polymorphic_v<From>) {
		result = std::dynamic_pointer_cast<To>(value.native_value());
	} else {
		static_assert(std::is_polymorphic_v<From>, "Object narrowing requires a polymorphic interface or base");
	}
	if (!result) {
		throw runtime_error("Object cast requires a present compatible object", "invalid_object_cast", "scpp::object_cast", "checked_object_cast");
	}
	return shared_p<To>(std::move(result));
}

template <typename To, typename From>
[[nodiscard]] shared_p<To> checked_object_cast(const nullable<shared_p<From>> &value) {
	if (!static_cast<bool>(value.has_value())) { return checked_object_cast<To>(shared_p<From>{}); }
	return checked_object_cast<To>(value.value());
}

} // namespace scpp
