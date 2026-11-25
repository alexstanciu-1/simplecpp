#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendLocalSlot {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> local_slot_source_row_id = static_cast<int_t<> >(0);
	string_t symbol_key = string_t("");
	string_t llvm_name = string_t("");
	int_t<> type_ref_id = static_cast<int_t<> >(0);
	int_t<> slot_id = static_cast<int_t<> >(0);
};
}
