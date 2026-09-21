#include <scpp/lang/php.hpp>
#include "__types/BackendStepRouteDescriptorRow.hpp"
#include "__types/CallableAbiReadinessArtifact.hpp"
#include "__types/CallableAbiReadinessRow.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_adapter_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_descriptor.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_descriptor.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_descriptors.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_string_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_literal_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_load_return_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_by_feature_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_step_route_descriptors.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_abi_model_scalar_direct_call_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_artifact_kind_callable_abi_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_callable_abi_artifact.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_source_model_project_contracts_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_lowering_gate_blocked_until_lowering_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_row_from_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_parameter_abi_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_result_stack_policy_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_return_abi_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_adapter_none_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::adapter_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[117]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendStepRouteDescriptorRow __latency_fn_backend_preflight_requests_backend_step_route_descriptor(int_t<std::uint16_t> featureId, int_t<std::uint16_t> loweringStepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_step_route_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[118]);
	BackendStepRouteDescriptorRow row = BackendStepRouteDescriptorRow{};
	row->feature_id = featureId;
	row->lowering_step_kind_id = loweringStepKindId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
vector_t<BackendStepRouteDescriptorRow> __latency_fn_backend_preflight_requests_backend_step_route_descriptors() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_step_route_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[119]);
	vector_t<BackendStepRouteDescriptorRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(27));
	{
	auto __latency_local_0 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_literal_scalar_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id());
	(void) rows.push_back(__latency_local_0);
	}
	auto __latency_local_1 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto operatorRow = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))))) {
			{
			auto __latency_local_3 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(operatorRow->feature_id, operatorRow->lowering_step_kind_id);
			(void) rows.push_back(__latency_local_3);
			}
		}
	}
	{
	auto __latency_local_4 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_local_storage_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id());
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_local_assignment_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id());
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_echo_scalar_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id());
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_echo_string_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id());
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id());
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id());
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_while_bool_loop_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id());
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_for_bool_loop_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id());
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_local_load_return_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id());
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_backend_preflight_requests_backend_step_route_descriptor(__latency_fn_type_capability_readiness_feature_ternary_select_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id());
	(void) rows.push_back(__latency_local_13);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendStepRouteDescriptorRow __latency_fn_backend_preflight_requests_backend_step_route_by_feature_id(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_step_route_by_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[120]);
	auto __latency_local_0 = __latency_fn_backend_preflight_requests_backend_step_route_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)))) {
			return row;
		}
	}
	BackendStepRouteDescriptorRow empty = BackendStepRouteDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
CallableAbiReadinessArtifact __latency_fn_backend_preflight_requests_new_callable_abi_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::new_callable_abi_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[121]);
	CallableAbiReadinessArtifact artifact = CallableAbiReadinessArtifact{};
	artifact->artifact_kind_id = __latency_fn_backend_preflight_requests_artifact_kind_callable_abi_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_backend_preflight_requests_source_model_project_contracts_id();
	artifact->abi_model_id = __latency_fn_backend_preflight_requests_abi_model_scalar_direct_call_id();
	php::vector_reserve(artifact->rows, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
CallableAbiReadinessRow __latency_fn_backend_preflight_requests_callable_abi_row_from_contract(int_t<std::uint32_t> abiRowId, ProjectCallableContractRow contract, StorageLifetimeRequestRow storage) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::callable_abi_row_from_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[122]);
	CallableAbiReadinessRow row = CallableAbiReadinessRow{};
	row->abi_row_id = abiRowId;
	row->reference_id = contract->reference_id;
	row->from_symbol_id = contract->from_symbol_id;
	row->target_symbol_id = contract->target_symbol_id;
	row->target_source_unit_id = contract->target_source_unit_id;
	row->actual_arg_count = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(contract->actual_arg_count));
	row->expected_arg_count = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(contract->expected_arg_count));
	row->return_type_ref_id = contract->return_type_ref_id;
	row->parameter_abi_status_id = __latency_fn_backend_preflight_requests_parameter_abi_ready_id();
	row->return_abi_status_id = __latency_fn_backend_preflight_requests_return_abi_ready_id();
	row->result_stack_policy_id = __latency_fn_backend_preflight_requests_result_stack_policy_scalar_id();
	row->backend_lowering_gate_id = __latency_fn_backend_preflight_requests_backend_lowering_gate_blocked_until_lowering_id();
	if (static_cast<bool>((php::identical(cast<int_t<>>(storage->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id())) && php::identical(cast<int_t<>>(storage->type_ref_id), cast<int_t<>>(contract->return_type_ref_id))))) {
		row->abi_status_id = __latency_fn_backend_preflight_requests_status_ready_id();
		row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
		row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id();
		return row;
	}
	row->abi_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id();
	return row;
}

}
