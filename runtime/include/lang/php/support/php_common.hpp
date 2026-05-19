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

struct static_context_entry {
	const void *owner_token;
	const void *current_token;
};

inline thread_local vector_t<static_context_entry> g_static_context_stack{};

template <typename OwnerClass>
inline const void *current_static_token_for() {
	for (auto it = g_static_context_stack.native_value().rbegin(); it != g_static_context_stack.native_value().rend(); ++it) {
		if (OwnerClass::__scpp_static_accepts((*it).current_token)) {
			return (*it).current_token;
		}
	}
	return nullptr;
}

template <typename OwnerClass, typename CurrentClass, typename Fn>
decltype(auto) _static(Fn &&fn) {
	struct static_scope_guard final {
		bool pop_entry;

		~static_scope_guard() {
			if (pop_entry) {
				g_static_context_stack.native_value().pop_back();
			}
		}
	};

	const bool should_push = current_static_token_for<OwnerClass>() == nullptr;
	if (should_push) {
		g_static_context_stack.push_back(static_context_entry{
			OwnerClass::__scpp_static_token(),
			CurrentClass::__scpp_static_token(),
		});
	}
	static_scope_guard guard{should_push};
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
