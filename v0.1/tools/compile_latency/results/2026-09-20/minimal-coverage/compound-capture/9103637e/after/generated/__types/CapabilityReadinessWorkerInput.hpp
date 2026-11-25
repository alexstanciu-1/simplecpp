#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class CapabilityReadinessWorkerInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> owner_run_id = static_cast<int_t<> >(0);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	vector_t<int_t<>> type_ref_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_ref_kind_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_ref_family_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_ref_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_parent_type_ref_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_indices = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_kind_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_ref_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_value_ints = vector_t<int_t<>>{};
	vector_t<int_t<>> type_arg_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_reference_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_from_symbol_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_target_symbol_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_target_source_unit_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_argument_count_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_return_type_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_backend_lowering_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_status_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_blocked_reason_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_actual_arg_counts = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_expected_arg_counts = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_return_type_ref_ids = vector_t<int_t<>>{};
	vector_t<int_t<>> contract_backend_adapter_ids = vector_t<int_t<>>{};
};
}
