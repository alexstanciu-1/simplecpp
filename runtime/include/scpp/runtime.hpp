#pragma once

#include <functional>

// Umbrella runtime header generated from runtime config.
//
// Purpose:
// - provides one include entry point for generated code
// - gathers the complete public runtime surface in config order

#include "scpp/detail.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/sentinel_ops.hpp"
#include "scpp/int_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/value_p.hpp"
#include "scpp/false_sentinel_t.hpp"
#include "scpp/true_sentinel_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/foreach.hpp"
#include "scpp/error_t.hpp"
#include "scpp/result.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/cast.hpp"
#include "scpp/fs.hpp"
#include "scpp/io.hpp"
#include "scpp/json.hpp"
#include "scpp/str.hpp"
#include "hosts/fastcgi/fastcgi.hpp"
#include "scpp/generated/operators.hpp"
