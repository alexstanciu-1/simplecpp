#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowSpan.hpp"
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_value.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_block.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_preflight.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_first_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_span_request_at.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_block.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_decision.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_preflight.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_request.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_entry_requests_into_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_value(int_t<> hash, BackendEmissionValueRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[175]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->value_id)));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->decision_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_step_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->backend_request_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_row_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_reference_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->target_symbol_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->type_ref_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value_role_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->storage_kind_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	return next;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_block(int_t<> hash, BackendEmissionBlockRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_block", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[176]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->block_id)));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->decision_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_lowering_step_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_step_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_value_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->block_kind_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	return next;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_preflight(int_t<> hash, FunctionBodyTextEmissionPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[177]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->preflight_id)));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->symbol_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_unit_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->body_readiness_status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->body_readiness_reason_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->text_sink_policy_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->emission_order_status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->next_step_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->body_readiness_lowering_blocked_reason_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->body_readiness_lowering_lookup_status_id));
	return next;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm(shared_p<BackendRequestAuthorizationArtifact>& requests, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_for_emission_llvm", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[178]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(29));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->request_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->ready_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->blocked_count));
	BackendRequestRowSpan span = __latency_fn_backend_preflight_requests_first_request_span(requests);
	while (static_cast<bool>((!__latency_fn_backend_preflight_requests_request_span_is_empty(span)))) {
		int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((offset < cast<int_t<>>(span->count)))) {
			BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_span_request_at(requests, span, offset);
			hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_request(hash, request);
			offset = (offset + static_cast<int_t<> >(1));
		}
		span = __latency_fn_backend_preflight_requests_next_request_span(requests, span);
	}
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(emission->decision_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(emission->ready_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(emission->blocked_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(emission->value_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(emission->block_count));
	auto __latency_local_0 = emission->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_decision(hash, decision);
	}
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		if (static_cast<bool>((cast<int_t<>>(value->value_id) > static_cast<int_t<> >(0)))) {
			hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_value(hash, value);
		}
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
	}
	auto __latency_local_2 = emission->blocks;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto block = __latency_local_3.value_copy();
		hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_block(hash, block);
	}
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(preflightArtifact->row_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(preflightArtifact->ready_count));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(preflightArtifact->blocked_count));
	auto __latency_local_4 = preflightArtifact->rows;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto preflight = __latency_local_5.value_copy();
		hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_preflight(hash, preflight);
	}
	BackendSinkBoundaryRow sink = __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(emission);
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(sink->sink_id));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(sink->sink_kind_id));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(sink->status_id));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, str::length(moduleText));
	hash = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText)));
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_snapshot_entry_requests_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::snapshot_entry_requests_into_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[179]);
	input->request_count = cast<int_t<>>(requests->request_count);
	input->ready_count = cast<int_t<>>(requests->ready_count);
	input->blocked_count = cast<int_t<>>(requests->blocked_count);
	input->binary_operand_count = cast<int_t<>>(requests->binary_operand_count);
	input->local_operand_count = cast<int_t<>>(requests->local_operand_count);
	input->call_argument_count = cast<int_t<>>(requests->call_argument_count);
	input->control_flow_operand_count = cast<int_t<>>(requests->control_flow_operand_count);
	int_t<> requestIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((requestIndex <= cast<int_t<>>(requests->request_count)))) {
		BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_by_id(requests, __latency_fn_structure_row_ids_uint32_from_int(requestIndex));
		if (static_cast<bool>((cast<int_t<>>(request->request_id) > static_cast<int_t<> >(0)))) {
			{
			auto __latency_local_0 = cast<int_t<>>(request->request_id);
			(void) input->request_ids.push_back(__latency_local_0);
			}
			{
			auto __latency_local_1 = cast<int_t<>>(request->contract_id);
			(void) input->request_contract_ids.push_back(__latency_local_1);
			}
			{
			auto __latency_local_2 = cast<int_t<>>(request->project_callable_contract_id);
			(void) input->request_project_callable_contract_ids.push_back(__latency_local_2);
			}
			{
			auto __latency_local_3 = cast<int_t<>>(request->source_row_id);
			(void) input->request_source_row_ids.push_back(__latency_local_3);
			}
			{
			auto __latency_local_4 = cast<int_t<>>(request->source_reference_id);
			(void) input->request_source_reference_ids.push_back(__latency_local_4);
			}
			{
			auto __latency_local_5 = cast<int_t<>>(request->target_symbol_id);
			(void) input->request_target_symbol_ids.push_back(__latency_local_5);
			}
			{
			auto __latency_local_6 = cast<int_t<>>(request->feature_id);
			(void) input->request_feature_ids.push_back(__latency_local_6);
			}
			{
			auto __latency_local_7 = cast<int_t<>>(request->lowering_adapter_id);
			(void) input->request_lowering_adapter_ids.push_back(__latency_local_7);
			}
			{
			auto __latency_local_8 = cast<int_t<>>(request->lowering_step_kind_id);
			(void) input->request_lowering_step_kind_ids.push_back(__latency_local_8);
			}
			{
			auto __latency_local_9 = cast<int_t<>>(request->provider_type_ref_id);
			(void) input->request_provider_type_ref_ids.push_back(__latency_local_9);
			}
			{
			auto __latency_local_10 = cast<int_t<>>(request->cache_owner_symbol_id);
			(void) input->request_cache_owner_symbol_ids.push_back(__latency_local_10);
			}
			{
			auto __latency_local_11 = cast<int_t<>>(request->status_id);
			(void) input->request_status_ids.push_back(__latency_local_11);
			}
			{
			auto __latency_local_12 = cast<int_t<>>(request->blocked_reason_id);
			(void) input->request_blocked_reason_ids.push_back(__latency_local_12);
			}
			{
			auto __latency_local_13 = cast<int_t<>>(request->project_callable_blocked_reason_id);
			(void) input->request_project_callable_blocked_reason_ids.push_back(__latency_local_13);
			}
			{
			auto __latency_local_14 = cast<int_t<>>(request->value);
			(void) input->request_values.push_back(__latency_local_14);
			}
		}
		requestIndex = (requestIndex + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>((cast<int_t<>>(requests->request_count) > static_cast<int_t<> >(0)))) {
		BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_by_id(requests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		input->request_id = cast<int_t<>>(request->request_id);
		input->request_contract_id = cast<int_t<>>(request->contract_id);
		input->request_project_callable_contract_id = cast<int_t<>>(request->project_callable_contract_id);
		input->request_source_row_id = cast<int_t<>>(request->source_row_id);
		input->request_source_reference_id = cast<int_t<>>(request->source_reference_id);
		input->request_target_symbol_id = cast<int_t<>>(request->target_symbol_id);
		input->request_feature_id = cast<int_t<>>(request->feature_id);
		input->request_lowering_adapter_id = cast<int_t<>>(request->lowering_adapter_id);
		input->request_lowering_step_kind_id = cast<int_t<>>(request->lowering_step_kind_id);
		input->request_provider_type_ref_id = cast<int_t<>>(request->provider_type_ref_id);
		input->request_cache_owner_symbol_id = cast<int_t<>>(request->cache_owner_symbol_id);
		input->request_status_id = cast<int_t<>>(request->status_id);
		input->request_blocked_reason_id = cast<int_t<>>(request->blocked_reason_id);
		input->request_project_callable_blocked_reason_id = cast<int_t<>>(request->project_callable_blocked_reason_id);
		input->request_value = cast<int_t<>>(request->value);
	}
	auto __latency_local_15 = requests->binary_operands;
	for (auto __latency_local_16 : foreach_range(__latency_local_15)) {
		auto row = __latency_local_16.value_copy();
		{
		auto __latency_local_17 = cast<int_t<>>(row->owner_row_id);
		(void) input->binary_owner_row_ids.push_back(__latency_local_17);
		}
		{
		auto __latency_local_18 = cast<int_t<>>(row->left_source_row_id);
		(void) input->binary_left_source_row_ids.push_back(__latency_local_18);
		}
		{
		auto __latency_local_19 = cast<int_t<>>(row->right_source_row_id);
		(void) input->binary_right_source_row_ids.push_back(__latency_local_19);
		}
		{
		auto __latency_local_20 = cast<int_t<>>(row->left_value);
		(void) input->binary_left_values.push_back(__latency_local_20);
		}
		{
		auto __latency_local_21 = cast<int_t<>>(row->right_value);
		(void) input->binary_right_values.push_back(__latency_local_21);
		}
	}
	auto __latency_local_22 = requests->local_operands;
	for (auto __latency_local_23 : foreach_range(__latency_local_22)) {
		auto row = __latency_local_23.value_copy();
		{
		auto __latency_local_24 = cast<int_t<>>(row->owner_row_id);
		(void) input->local_owner_row_ids.push_back(__latency_local_24);
		}
		{
		auto __latency_local_25 = cast<int_t<>>(row->local_source_row_id);
		(void) input->local_source_row_ids.push_back(__latency_local_25);
		}
		{
		auto __latency_local_26 = cast<int_t<>>(row->value_source_row_id);
		(void) input->local_value_source_row_ids.push_back(__latency_local_26);
		}
		{
		auto __latency_local_27 = cast<int_t<>>(row->type_ref_id);
		(void) input->local_type_ref_ids.push_back(__latency_local_27);
		}
		{
		auto __latency_local_28 = cast<int_t<>>(row->value);
		(void) input->local_values.push_back(__latency_local_28);
		}
		{
		auto __latency_local_29 = cast<int_t<>>(row->local_operation_id);
		(void) input->local_operation_ids.push_back(__latency_local_29);
		}
	}
	auto __latency_local_30 = requests->call_arguments;
	for (auto __latency_local_31 : foreach_range(__latency_local_30)) {
		auto row = __latency_local_31.value_copy();
		{
		auto __latency_local_32 = cast<int_t<>>(row->owner_row_id);
		(void) input->call_argument_owner_row_ids.push_back(__latency_local_32);
		}
		{
		auto __latency_local_33 = cast<int_t<>>(row->argument_source_row_id);
		(void) input->call_argument_source_row_ids.push_back(__latency_local_33);
		}
		{
		auto __latency_local_34 = cast<int_t<>>(row->type_ref_id);
		(void) input->call_argument_type_ref_ids.push_back(__latency_local_34);
		}
		{
		auto __latency_local_35 = cast<int_t<>>(row->value);
		(void) input->call_argument_values.push_back(__latency_local_35);
		}
		{
		auto __latency_local_36 = cast<int_t<>>(row->position);
		(void) input->call_argument_positions.push_back(__latency_local_36);
		}
	}
	auto __latency_local_37 = requests->control_flow_operands;
	for (auto __latency_local_38 : foreach_range(__latency_local_37)) {
		auto row = __latency_local_38.value_copy();
		{
		auto __latency_local_39 = cast<int_t<>>(row->owner_row_id);
		(void) input->control_owner_row_ids.push_back(__latency_local_39);
		}
		{
		auto __latency_local_40 = cast<int_t<>>(row->condition_source_row_id);
		(void) input->control_condition_source_row_ids.push_back(__latency_local_40);
		}
		{
		auto __latency_local_41 = cast<int_t<>>(row->body_first_source_row_id);
		(void) input->control_body_first_source_row_ids.push_back(__latency_local_41);
		}
		{
		auto __latency_local_42 = cast<int_t<>>(row->body_last_source_row_id);
		(void) input->control_body_last_source_row_ids.push_back(__latency_local_42);
		}
		{
		auto __latency_local_43 = cast<int_t<>>(row->body_terminator_source_row_id);
		(void) input->control_body_terminator_source_row_ids.push_back(__latency_local_43);
		}
		{
		auto __latency_local_44 = cast<int_t<>>(row->body_terminator_kind_id);
		(void) input->control_body_terminator_kind_ids.push_back(__latency_local_44);
		}
		{
		auto __latency_local_45 = cast<int_t<>>(row->else_body_first_source_row_id);
		(void) input->control_else_body_first_source_row_ids.push_back(__latency_local_45);
		}
		{
		auto __latency_local_46 = cast<int_t<>>(row->else_body_last_source_row_id);
		(void) input->control_else_body_last_source_row_ids.push_back(__latency_local_46);
		}
		{
		auto __latency_local_47 = cast<int_t<>>(row->condition_type_ref_id);
		(void) input->control_condition_type_ref_ids.push_back(__latency_local_47);
		}
		{
		auto __latency_local_48 = cast<int_t<>>(row->condition_value);
		(void) input->control_condition_values.push_back(__latency_local_48);
		}
		{
		auto __latency_local_49 = cast<int_t<>>(row->condition_operand_kind_id);
		(void) input->control_condition_operand_kind_ids.push_back(__latency_local_49);
		}
		{
		auto __latency_local_50 = cast<int_t<>>(row->condition_local_source_row_id);
		(void) input->control_condition_local_source_row_ids.push_back(__latency_local_50);
		}
		{
		auto __latency_local_51 = cast<int_t<>>(row->condition_left_local_source_row_id);
		(void) input->control_condition_left_local_source_row_ids.push_back(__latency_local_51);
		}
		{
		auto __latency_local_52 = cast<int_t<>>(row->condition_right_local_source_row_id);
		(void) input->control_condition_right_local_source_row_ids.push_back(__latency_local_52);
		}
		{
		auto __latency_local_53 = cast<int_t<>>(row->condition_lhs_type_ref_id);
		(void) input->control_condition_lhs_type_ref_ids.push_back(__latency_local_53);
		}
		{
		auto __latency_local_54 = cast<int_t<>>(row->condition_local_operation_id);
		(void) input->control_condition_local_operation_ids.push_back(__latency_local_54);
		}
		{
		auto __latency_local_55 = cast<int_t<>>(row->control_operation_id);
		(void) input->control_operation_ids.push_back(__latency_local_55);
		}
	}
}

}
