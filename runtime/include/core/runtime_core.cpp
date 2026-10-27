#include "scpp/runtime.hpp"

// Core-only implementation bundle. Module libraries depend on this owner.

#include "../scpp/util/global_string_pool.cpp"
#include "../scpp/support/hash_t.cpp"
#include "../scpp/support/mixed_t.cpp"
#include "../scpp/support/dynamic_value.cpp"
#include "../scpp/support/dbg.cpp"
#include "../scpp/support/var_dump.cpp"
#include "../scpp/async_core.cpp"

#include "integers.cpp"

#include "failure.cpp"

#include "string.cpp"

#include "error_value.cpp"
#include "exception_info.cpp"

#include "generated/vector_string.cpp"
#include "generated/result_vector_string.cpp"

#include "generated/float64.cpp"
#include "generated/mixed.cpp"

#include "generated/dynamic.cpp"

// Metadata-generated normalized global operators.
#include "scpp/generated/public_add_int8.cpp"
#include "scpp/generated/public_add_int16.cpp"
#include "scpp/generated/public_add_int32.cpp"
#include "scpp/generated/public_add_int64.cpp"
#include "scpp/generated/public_add_uint8.cpp"
#include "scpp/generated/public_add_uint16.cpp"
#include "scpp/generated/public_add_uint32.cpp"
#include "scpp/generated/public_add_uint64.cpp"
#include "scpp/generated/public_add_float64.cpp"
#include "scpp/generated/public_equal_mixed.cpp"
#include "scpp/generated/public_equal_dynamic.cpp"
#include "scpp/generated/public_not_equal_mixed.cpp"
#include "scpp/generated/public_not_equal_dynamic.cpp"
