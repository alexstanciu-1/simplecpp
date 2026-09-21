#pragma once

#include <functional>
#include <concepts>
#include <type_traits>
#include <utility>
#include "scpp/foreach.hpp"

namespace scpp::collections {
namespace detail {

// Source value parameters may lower to const references (strings/containers).
// Accept a read-only borrow of the exact copied value, never mutable references
// or implicit mixed/scalar conversions.
template <typename F, typename = void> struct callable_signature {};
template <typename R, typename A> struct callable_signature<R(A), void> {
	using argument = A;
	using result = R;
};
template <typename R, typename A> struct callable_signature<R (*)(A), void> : callable_signature<R(A)> {};
template <typename R, typename A> struct callable_signature<std::function<R(A)>, void> : callable_signature<R(A)> {};
template <typename C, typename R, typename A> struct callable_signature<R (C::*)(A), void> : callable_signature<R(A)> {};
template <typename C, typename R, typename A> struct callable_signature<R (C::*)(A) const, void> : callable_signature<R(A)> {};
template <typename C, typename R, typename A> struct callable_signature<R (C::*)(A) noexcept, void> : callable_signature<R(A)> {};
template <typename C, typename R, typename A> struct callable_signature<R (C::*)(A) const noexcept, void> : callable_signature<R(A)> {};
template <typename F> struct callable_signature<F, std::void_t<decltype(&F::operator())>> : callable_signature<decltype(&F::operator())> {};

enum class carrier_kind { sequence, keyed, dynamic };
template <typename T> struct sequence_policy {
	static constexpr auto kind = carrier_kind::sequence;
	using value = T;
	template <typename U> using output = vector_t<U>;
	template <typename U, typename C> static output<U> create(const C &source) {
		output<U> out;
		out.reserve(source.size());
		return out;
	}
	template <typename U, typename K> static void insert(output<U> &out, const K &, U value) { out.append(std::move(value)); }
};
template <typename C> struct policy;
template <typename T> struct policy<vector_t<T>> : sequence_policy<T> {};
template <typename T, std::size_t N> struct policy<fixed_array_t<T, N>> : sequence_policy<T> {};

template <typename T, typename K> struct policy<hash_t<T, K>> {
	static constexpr auto kind = carrier_kind::keyed;
	using value = T;
	template <typename U> using stored = std::conditional_t<std::is_same_v<K, mixed_t>, mixed_t, U>;
	template <typename U> using output = hash_t<stored<U>, K>;
	template <typename U> static output<U> create(const hash_t<T, K> &) { return {}; }
	template <typename U> static void insert(output<U> &out, const K &key, U value) { out.set(key, stored<U>(std::move(value))); }
};

template <typename T, typename K> struct policy<dynamic_t<T, K>> {
	static constexpr auto kind = carrier_kind::dynamic;
	using value = T;
	using table_policy = policy<hash_t<T, K>>;
	template <typename U> using output = shared_p<typename table_policy::template output<U>>;
	template <typename U> static output<U> create(const dynamic_t<T, K> &source) {
		if (!static_cast<bool>(source)) { throw runtime_error("Collection operation requires a non-null dynamic table.", "type_error", "runtime", "collection"); }
		return output<U>(std::make_shared<typename table_policy::template output<U>>());
	}
	template <typename U> static void insert(output<U> &out, const K &key, U value) { table_policy::template insert<U>(*out, key, std::move(value)); }
};

template <> struct policy<mixed_t> {
	static constexpr auto kind = carrier_kind::keyed;
	using value = mixed_t;
	template <typename U> using output = mixed_t;
	template <typename U> static mixed_t create(const mixed_t &source) {
		if (!source.try_get_hash()) { throw runtime_error("Collection operation requires a table-valued mixed input.", "type_error", "runtime", "collection"); }
		return mixed_t(std::make_unique<hash_t<mixed_t>>());
	}
	template <typename U> static void insert(mixed_t &out, const mixed_t &key, U value) { out.try_get_hash()->set(key, mixed_t(std::move(value))); }
};

template <typename A, typename T>
concept value_parameter_for = std::same_as<A, T> || std::same_as<A, const T &>;

template <typename C, typename F>
concept compatible_callback = requires {
	typename policy<C>::value;
	typename callable_signature<std::remove_cvref_t<F>>::result;
	requires value_parameter_for<typename callable_signature<std::remove_cvref_t<F>>::argument, typename policy<C>::value>;
	requires (!std::is_void_v<typename callable_signature<std::remove_cvref_t<F>>::result>);
	requires (!std::is_reference_v<typename callable_signature<std::remove_cvref_t<F>>::result>);
};
template <typename C, carrier_kind Kind>
concept carrier_is = requires { requires policy<C>::kind == Kind; };
} // namespace detail

template <typename C, typename F> requires detail::compatible_callback<C, F>
[[nodiscard]] auto map(const C &source, F &&callback) {
	using policy = detail::policy<C>;
	using result = typename detail::callable_signature<std::remove_cvref_t<F>>::result;
	auto out = policy::template create<result>(source);
	for (const auto entry : foreach_range(source)) {
		policy::template insert<result>(out, entry.key(), std::invoke(callback, entry.value_copy()));
	}
	return out;
}

template <typename C, typename F> requires detail::compatible_callback<C, F>
	&& std::same_as<typename detail::callable_signature<std::remove_cvref_t<F>>::result, bool_t>
[[nodiscard]] auto filter(const C &source, F &&predicate) {
	using policy = detail::policy<C>;
	using value = typename policy::value;
	auto out = policy::template create<value>(source);
	for (const auto entry : foreach_range(source)) {
		auto item = entry.value_copy();
		if (std::invoke(predicate, item).native_value()) {
			policy::template insert<value>(out, entry.key(), std::move(item));
		}
	}
	return out;
}

// Constrained public entry points share the algorithms and callback checks above.
template <typename C, typename F> requires detail::carrier_is<C, detail::carrier_kind::sequence>
	&& requires(const C &source, F &&callback) { collections::map(source, std::forward<F>(callback)); }
[[nodiscard]] auto sequence_map(const C &source, F &&callback) {
	return collections::map(source, std::forward<F>(callback));
}
template <typename C, typename F> requires detail::carrier_is<C, detail::carrier_kind::sequence>
	&& requires(const C &source, F &&predicate) { collections::filter(source, std::forward<F>(predicate)); }
[[nodiscard]] auto sequence_filter(const C &source, F &&predicate) {
	return collections::filter(source, std::forward<F>(predicate));
}
template <typename C, typename F> requires detail::carrier_is<C, detail::carrier_kind::keyed>
	&& requires(const C &source, F &&callback) { collections::map(source, std::forward<F>(callback)); }
[[nodiscard]] auto keyed_map(const C &source, F &&callback) {
	return collections::map(source, std::forward<F>(callback));
}
template <typename C, typename F> requires detail::carrier_is<C, detail::carrier_kind::keyed>
	&& requires(const C &source, F &&predicate) { collections::filter(source, std::forward<F>(predicate)); }
[[nodiscard]] auto keyed_filter(const C &source, F &&predicate) {
	return collections::filter(source, std::forward<F>(predicate));
}
} // namespace scpp::collections
