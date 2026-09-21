#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendLoweringWorkerInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> owner_run_id = static_cast<int_t<> >(0);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> entry_context_id = static_cast<int_t<> >(0);
	int_t<> entry_declaration_node_id = static_cast<int_t<> >(0);
	int_t<> entry_graph_symbol_node_id = static_cast<int_t<> >(0);
	int_t<> entry_first_reference_id = static_cast<int_t<> >(0);
	int_t<> entry_reference_count = static_cast<int_t<> >(0);
	int_t<> entry_first_callable_contract_id = static_cast<int_t<> >(0);
	int_t<> entry_callable_contract_count = static_cast<int_t<> >(0);
	int_t<> entry_first_dependency_edge_id = static_cast<int_t<> >(0);
	int_t<> entry_dependency_edge_count = static_cast<int_t<> >(0);
	int_t<> entry_status_id = static_cast<int_t<> >(0);
	int_t<> entry_downstream_table_status_id = static_cast<int_t<> >(0);
	int_t<> entry_flags = static_cast<int_t<> >(0);
	int_t<> contract_id = static_cast<int_t<> >(0);
	int_t<> contract_reference_id = static_cast<int_t<> >(0);
	int_t<> contract_from_symbol_id = static_cast<int_t<> >(0);
	int_t<> contract_target_symbol_id = static_cast<int_t<> >(0);
	int_t<> contract_target_source_unit_id = static_cast<int_t<> >(0);
	int_t<> contract_argument_count_status_id = static_cast<int_t<> >(0);
	int_t<> contract_return_type_status_id = static_cast<int_t<> >(0);
	int_t<> contract_backend_lowering_status_id = static_cast<int_t<> >(0);
	int_t<> contract_status_id = static_cast<int_t<> >(0);
	int_t<> contract_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> contract_actual_arg_count = static_cast<int_t<> >(0);
	int_t<> contract_expected_arg_count = static_cast<int_t<> >(0);
	int_t<> contract_return_type_ref_id = static_cast<int_t<> >(0);
	int_t<> contract_backend_adapter_id = static_cast<int_t<> >(0);
	int_t<> operation_contract_id = static_cast<int_t<> >(0);
	int_t<> operation_lowering_adapter_id = static_cast<int_t<> >(0);
	int_t<> operation_source_row_id = static_cast<int_t<> >(0);
	int_t<> operation_kind_id = static_cast<int_t<> >(0);
	int_t<> operation_capability_id = static_cast<int_t<> >(0);
	int_t<> operation_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> operation_provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> operation_status_id = static_cast<int_t<> >(0);
	int_t<> operation_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> operation_result_type_ref_id = static_cast<int_t<> >(0);
	int_t<> storage_request_id = static_cast<int_t<> >(0);
	int_t<> storage_consumer_kind_id = static_cast<int_t<> >(0);
	int_t<> storage_capability_id = static_cast<int_t<> >(0);
	int_t<> storage_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> storage_context_id = static_cast<int_t<> >(0);
	int_t<> storage_type_ref_id = static_cast<int_t<> >(0);
	int_t<> storage_source_row_id = static_cast<int_t<> >(0);
	int_t<> storage_policy_id = static_cast<int_t<> >(0);
	int_t<> storage_copy_policy_id = static_cast<int_t<> >(0);
	int_t<> storage_cleanup_policy_id = static_cast<int_t<> >(0);
	int_t<> storage_lifetime_policy_id = static_cast<int_t<> >(0);
	int_t<> storage_readiness_status_id = static_cast<int_t<> >(0);
};
}
