#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ObjectOutputWorkerInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> owner_run_id = static_cast<int_t<> >(0);
	int_t<> cache_object_id = static_cast<int_t<> >(0);
	int_t<> execution_id = static_cast<int_t<> >(0);
	int_t<> owner_symbol_id = static_cast<int_t<> >(0);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> object_action_kind_id = static_cast<int_t<> >(0);
	int_t<> execution_status_id = static_cast<int_t<> >(0);
	int_t<> blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> owner_key_id = static_cast<int_t<> >(0);
	int_t<> source_unit_key_id = static_cast<int_t<> >(0);
	int_t<> partition_key_id = static_cast<int_t<> >(0);
	int_t<> backend_row_key_id = static_cast<int_t<> >(0);
	int_t<> logical_llvm_function_name_id = static_cast<int_t<> >(0);
	int_t<> emitted_llvm_function_name_id = static_cast<int_t<> >(0);
	int_t<> emission_source_key_id = static_cast<int_t<> >(0);
	int_t<> input_surface_key_id = static_cast<int_t<> >(0);
	int_t<> output_object_key_id = static_cast<int_t<> >(0);
};
}
