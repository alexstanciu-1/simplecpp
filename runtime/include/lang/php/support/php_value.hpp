#pragma once

#include "lang/php/support/php_common.hpp"
#include "../../../operators/coalesce/coalesce.hpp"
#include "../../../operators/concat_assign/concat_assign.hpp"
#include "../../../operators/conditional/condition_truthiness.hpp"
#include "../../../operators/conditional/conditional_selection.hpp"
#include "../../../operators/count/count.hpp"
#include "../../../operators/debug_use_count/debug_use_count.hpp"
#include "../../../operators/probe/probe.hpp"
#include "../../../operators/empty/empty.hpp"
#include "../../../operators/identity/strict_identity.hpp"
#include "../../../operators/isset/isset.hpp"
#include "../../../operators/memory_usage/memory_usage.hpp"
#include "../../../operators/unset/unset.hpp"
#include "../../../operators/value_copy/value_copy.hpp"
#include "../../../operators/weakref/weakref.hpp"
#include "lang/php/operators/coalesce/coalesce.hpp"
#include "lang/php/operators/concat_assign/concat_assign.hpp"
#include "lang/php/operators/count/count.hpp"
#include "lang/php/operators/debug_use_count/debug_use_count.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "lang/php/operators/conditional/conditional_selection.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "lang/php/operators/identity/strict_identity.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "lang/php/operators/memory_usage/memory_usage.hpp"
#include "lang/php/operators/type_predicates/type_predicates.hpp"
#include "lang/php/operators/unset/unset.hpp"
#include "lang/php/operators/value_copy/value_copy.hpp"
#include "lang/php/operators/weakref/weakref.hpp"

namespace scpp::php {

namespace detail {

using ::scpp::detail::probe_state;
using ::scpp::detail::probe_value;
using ::scpp::detail::vector_has_index;

} // namespace detail

using ::scpp::to_dynamic;
using ::scpp::to_hash;

template <typename T>
inline void vector_reserve(vector_t<T> &values, const int_t<> &capacity) {
	const auto native_capacity = capacity.native_value();
	if (native_capacity < 0) {
		throw runtime_error(
			"vector_reserve capacity must be non-negative.",
			"value_error",
			"runtime",
			"vector_reserve"
		);
	}
	values.reserve(static_cast<std::size_t>(native_capacity));
}

template <typename T>
inline int_t<> vector_capacity(const vector_t<T> &values) {
	return int_t<>(static_cast<std::int64_t>(values.capacity()));
}

template <typename T>
inline void vector_clear(vector_t<T> &values) noexcept {
	values.clear();
}

template <typename T>
inline void vector_compact(vector_t<T> &values) {
	values.compact();
}

template <typename T>
inline void vector_compact(vector_t<T> &values, const int_t<> &capacity) {
	const auto native_capacity = capacity.native_value();
	if (native_capacity < 0) {
		throw runtime_error(
			"vector_compact capacity must be non-negative.",
			"value_error",
			"runtime",
			"vector_compact"
		);
	}
	values.compact(static_cast<std::size_t>(native_capacity));
}
} // namespace scpp::php
