#pragma once

#include "scpp/bool_t.hpp"
#include "core/string_support.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result.hpp"
#include "scpp/error_t.hpp"
#include "lang/php/support/php_take.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/support/dbg.hpp"
#include "scpp/support/var_dump.hpp"
#include "core/dynamic_helpers.hpp"

#include <algorithm>
#include <chrono>
#include <cstdlib>
#include <cctype>
#include <cstdint>
#include <fstream>
#include <iostream>
#include <limits>
#include <string>
#include <string_view>
#include <tuple>
#include <type_traits>
#include <utility>
#if defined(__unix__) || defined(__APPLE__)
#include <sys/resource.h>
#endif

namespace scpp::php {

using ::scpp::ValueError;
using ::scpp::TypeError;

// PHP compatibility constants consumed by generated code.
using ::scpp::PHP_INT_MAX;
using ::scpp::STR_PAD_LEFT;
using ::scpp::STR_PAD_RIGHT;
using ::scpp::STR_PAD_BOTH;
using ::scpp::DBG_CALLER;
using ::scpp::DBG_COMPACT;
using ::scpp::DBG_DEFAULT;
using ::scpp::DBG_DEPTH_0;
using ::scpp::DBG_DEPTH_1;
using ::scpp::DBG_DEPTH_2;
using ::scpp::DBG_DEPTH_3;
using ::scpp::DBG_DEPTH_4;
using ::scpp::DBG_DEPTH_5;
using ::scpp::DBG_FIELDS;
using ::scpp::DBG_JSON;
using ::scpp::DBG_KEYS;
using ::scpp::DBG_LEN;
using ::scpp::DBG_PTR;
using ::scpp::DBG_RAW;
using ::scpp::DBG_SHAPE;
using ::scpp::DBG_SOURCE;
using ::scpp::DBG_TYPE;
using ::scpp::DBG_VALUE;

inline thread_local const void *g_current_static_token = nullptr;

inline const void *current_static_token() {
	return g_current_static_token;
}

template <typename CurrentClass, typename Fn>
decltype(auto) _static(Fn &&fn) {
	struct static_scope_guard final {
		const void *previous;
		bool restore;

		~static_scope_guard() {
			if (restore) {
				g_current_static_token = previous;
			}
		}
	};

	const void *previous = g_current_static_token;
	const bool should_raise = previous == nullptr;
	if (should_raise) {
		g_current_static_token = CurrentClass::__scpp_static_token();
	}
	static_scope_guard guard{previous, should_raise};
	return std::forward<Fn>(fn)();
}

// Validates a PHP array / ?array argument that has been lowered to mixed_t.
// How: reject invalid kinds before executing any user code inside the callee.
inline void expect_array_argument(const mixed_t &value, bool nullable, const char *name) {
	const auto kind = value.kind();
	if (kind == mixed_t::kind_t::table_v || kind == mixed_t::kind_t::shared_table_v || kind == mixed_t::kind_t::weak_table_v) {
		return;
	}
	if (nullable && kind == mixed_t::kind_t::null_v) {
		return;
	}
	throw ValueError(std::string("Argument $") + name + (nullable ? " must be of type ?array" : " must be of type array"));
}


} // namespace scpp::php
