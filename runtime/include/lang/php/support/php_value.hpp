#pragma once

#include "lang/php/support/php_common.hpp"
#include "operators/probe/probe.hpp"
#include "operators/empty/empty.hpp"
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
} // namespace scpp::php
