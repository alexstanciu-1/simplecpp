#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/LlvmPrimitiveAbiDescriptorRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_preflight.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_source_model_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_local_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_string_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_string_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightArtifact __latency_fn_llvm_text_from_plan_preflight_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[26]);
	FunctionBodyTextEmissionPreflightArtifact artifact = __latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source(static_cast<int_t<> >(1), __latency_fn_llvm_text_from_plan_source_model_backend_emission_decision_id());
	FunctionBodyTextEmissionPreflightRow row = __latency_fn_llvm_text_from_plan_preflight_row_from_emission(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), emission);
	__latency_fn_llvm_text_from_plan_append_preflight(artifact, row);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightRow __latency_fn_llvm_text_from_plan_preflight_by_id(FunctionBodyTextEmissionPreflightArtifact artifact, int_t<std::uint32_t> preflightId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[27]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(preflightId, cast<int_t<>>(artifact->row_count))))) {
		FunctionBodyTextEmissionPreflightRow row = artifact->rows[__latency_fn_structure_row_ids_dense_index(preflightId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->preflight_id), cast<int_t<>>(preflightId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->preflight_id), cast<int_t<>>(preflightId)))) {
			return row;
		}
	}
	FunctionBodyTextEmissionPreflightRow empty = FunctionBodyTextEmissionPreflightRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[28]);
	BackendEmissionDecisionArtifact emission = __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	return __latency_fn_llvm_text_from_plan_module_text_from_emission(emission);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[29]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id()))))) {
		return string_t("");
	}
	BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id(emission, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	if (static_cast<bool>((cast<int_t<>>(emission->local_operand_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_local_emission(emission);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_emission_has_echo_scalar(emission)))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission(emission);
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(value->type_ref_id));
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_return_value_text_from_emission(emission, value, returnType)));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(value->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[30]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id()))))) {
		return string_t("");
	}
	BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	if (static_cast<bool>(((cast<int_t<>>(requests->local_operand_count) > static_cast<int_t<> >(0)) || (cast<int_t<>>(requests->control_flow_operand_count) > static_cast<int_t<> >(0))))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_local_emission_and_backend_requests(emission, requests);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_emission_has_echo_string_and_backend_requests(emission, requests)))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_echo_string_emission_and_backend_requests(emission, requests);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests(emission, requests)))) {
		return __latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission_and_backend_requests(emission, requests);
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(value->type_ref_id));
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests(emission, requests, value, returnType)));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(value->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_align_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[31]);
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	if (static_cast<bool>((php::identical(cast<int_t<>>(trait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && (cast<int_t<>>(trait->abi_align_bytes) > static_cast<int_t<> >(0))))) {
		return cast<int_t<>>(trait->abi_align_bytes);
	}
	shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor = __latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	return cast<int_t<>>(descriptor->align_bytes);
}

}
