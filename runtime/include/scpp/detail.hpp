#pragma once

#include <cstdint>
#include <memory>
#include <optional>
#include <stdexcept>
#include <string>
#include <string_view>
#include <utility>
#include <vector>

namespace scpp {

// Forward declarations for cross-type references.
//
// Purpose:
// - keeps header dependencies smaller
// - allows wrappers to reference each other without forcing full definitions everywhere
class null_t;
// Sentinel type representing the empty optional state in the runtime contract.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class nullopt_t;
// Sentinel type representing null pointer state in the runtime contract.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class nullptr_t;
// Semantic boolean wrapper used so generated code stays inside the runtime type domain instead of raw bool.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class bool_t;
// Semantic integer wrapper implementing the integer arithmetic/comparison contract from the runtime spec.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class int_t;
// Semantic floating-point wrapper implementing the configured numeric widening rules.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class float_t;
// String wrapper used by the generator for string literals, concat, and explicit casts.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class string_t;

template <typename T> class vector_t;
template <typename T> class shared_p;
template <typename T> class unique_p;
template <typename T> class weak_p;
template <typename T> class value_p;
template <typename T> class nullable;
template <typename T> class result_or_false;
template <typename T> class result_or_bool;
class error_t;
struct error_sentinel_t;
template <typename T> class result;

// hash_t and value types full declarations in scpp/support/hash_t.hpp
class mixed_t;
template <typename T_VALUE>
struct default_hash_key {
	using type = string_t;
};

template <>
struct default_hash_key<mixed_t> {
	using type = mixed_t;
};

template <typename T_VALUE = mixed_t, typename T_KEY = typename default_hash_key<T_VALUE>::type>
class hash_t;

// Cast helper forward declaration.
template <typename To, typename From>
// Implements one explicit cast pair allowed by the current runtime and generator contract.
// How: conversions are centralized here so unsupported pairs fail at compile time instead of silently converting.
To cast(const From &value);

namespace detail {

// Common utility alias used by generated code for template normalization.
template <typename T>
using remove_cvref_t = std::remove_cv_t<std::remove_reference_t<T>>;


template <typename T, template <typename...> class Template>
struct is_specialization_of : std::false_type {};

template <template <typename...> class Template, typename... Args>
struct is_specialization_of<Template<Args...>, Template> : std::true_type {};

template <typename T, template <typename...> class Template>
inline constexpr bool is_specialization_of_v = is_specialization_of<remove_cvref_t<T>, Template>::value;

// Runtime wrapper classification traits used by helper utilities.
template <typename T>
struct is_handle_like : std::false_type {};

template <typename T>
struct is_shared_p : std::false_type {};

template <typename T>
struct is_shared_p<shared_p<T>> : std::true_type {};

template <typename T>
struct is_handle_like<shared_p<T>> : std::true_type {};

template <typename T>
struct is_handle_like<unique_p<T>> : std::true_type {};

template <typename T>
struct is_handle_like<weak_p<T>> : std::true_type {};

template <typename T>
inline constexpr bool is_shared_p_v = is_shared_p<remove_cvref_t<T>>::value;

template <typename T>
inline constexpr bool is_handle_like_v = is_handle_like<remove_cvref_t<T>>::value;

// Extracts the wrapped payload type from nullable<T> so centralized cast helpers can lift dynamic values into explicit nullable destinations.
template <typename T>
struct nullable_value_type;

template <typename T>
struct nullable_value_type<nullable<T>> {
	using type = T;
};

template <typename T>
using nullable_value_type_t = typename nullable_value_type<remove_cvref_t<T>>::type;

// Detects whether a runtime type exposes operator-> so wrapper types can forward dereference safely.
template <typename T, typename = void>
struct has_arrow_operator : std::false_type {};

template <typename T>
struct has_arrow_operator<T, std::void_t<decltype(std::declval<T &>().operator->())>> : std::true_type {};

template <typename T>
inline constexpr bool has_arrow_operator_v = has_arrow_operator<remove_cvref_t<T>>::value;

// Extracts the underlying class from runtime pointer wrappers so generated
// static calls can recover the represented class type from value carriers.
template <typename T>
// class_of participates in the runtime support layer.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
struct class_of {
	using type = remove_cvref_t<T>;
};

template <typename T>
// class_of participates in the runtime support layer.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
struct class_of<shared_p<T>> {
	using type = T;
};

template <typename T>
// class_of participates in the runtime support layer.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
struct class_of<unique_p<T>> {
	using type = T;
};

template <typename T>
// class_of participates in the runtime support layer.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
struct class_of<weak_p<T>> {
	using type = T;
};

template <typename T>
struct class_of<value_p<T>> {
	using type = T;
};
template <typename T>
using class_of_t = typename class_of<remove_cvref_t<T>>::type;

// Helper used in dependent static_asserts so unsupported templates fail cleanly.
template <typename... T>
constexpr bool always_false_v = false;

} // namespace detail

// Public shorthand used by generated code.
template <typename T>
using class_t = detail::class_of_t<T>;

// Public wrapper-family traits for runtime helpers and generated code.
template <typename T>
inline constexpr bool is_handle_like_v = detail::is_handle_like_v<T>;
} // namespace scpp
