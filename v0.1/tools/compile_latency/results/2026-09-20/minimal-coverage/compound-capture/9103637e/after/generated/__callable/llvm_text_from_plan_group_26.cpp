#include <scpp/lang/php.hpp>
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_call_argument_list_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, const string_t& targetFunctionText, const string_t& targetName) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_entry_call_with_target_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[198]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(entryEmission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())) || php::identical(targetFunctionText, string_t(""))))) {
		return string_t("");
	}
	BackendEmissionValueRow callValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(entryEmission, entryRequests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	vector_t<BackendCallArgumentRow> arguments = required_cast<vector_t<BackendCallArgumentRow>>(__latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id(entryRequests, __latency_fn_backend_emission_decisions_value_work_id(callValue)));
	string_t argumentListText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_call_argument_list_text(arguments));
	if (static_cast<bool>((php::identical(cast<int_t<>>(callValue->value_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(callValue->target_symbol_id), static_cast<int_t<> >(0))))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(callValue->type_ref_id));
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-direct-call\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + cast<string_t>(targetFunctionText));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %direct_call_result_1 = call ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(targetName) + string_t("(") + cast<string_t>(argumentListText) + string_t(")\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" %direct_call_result_1\n"));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(callValue->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, BackendEmissionDecisionArtifact& targetEmission, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_entry_and_target_emissions", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[199]);
	BackendEmissionValueRow callValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(entryEmission, entryRequests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	string_t targetName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, callValue->target_symbol_id));
	return __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name(entryEmission, entryRequests, targetEmission, targetRequests, targetName);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, BackendEmissionDecisionArtifact& targetEmission, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, const string_t& targetName) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_entry_and_target_emissions_with_target_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[200]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(entryEmission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())) || php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(targetEmission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id()))))) {
		return string_t("");
	}
	BackendEmissionValueRow callValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(entryEmission, entryRequests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>((php::identical(cast<int_t<>>(callValue->value_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(callValue->target_symbol_id), static_cast<int_t<> >(0))))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(callValue->type_ref_id));
	if (static_cast<bool>((cast<int_t<>>(targetRequests->local_operand_count) > static_cast<int_t<> >(0)))) {
		str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
		str::text_builder_reserve_bytes(lines, (__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes(cast<int_t<>>(targetEmission->value_count)) + static_cast<int_t<> >(2048)));
		str::text_builder_append_string(lines, string_t("; v2 PHS backend-emission LLVM text sink\n; backend_value_stack_status=ok\n; backend_local_slot_status=ok\nsource_filename = \"compiler/reset-runner-direct-call\"\ntarget triple = \"x86_64-pc-linux-gnu\"\n\n"));
		if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests(lines, targetName, targetEmission, targetRequests)))) {
			return string_t("");
		}
		str::text_builder_append_string(lines, (string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n")));
		str::text_builder_append_string(lines, string_t("entry:\n"));
		str::text_builder_append_string(lines, (string_t("  %direct_call_result_1 = call ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(targetName) + string_t("()\n")));
		str::text_builder_append_string(lines, (string_t("  ret ") + cast<string_t>(returnType) + string_t(" %direct_call_result_1\n")));
		str::text_builder_append_string(lines, string_t("}\n"));
		str::text_builder_append_string(lines, string_t("\n"));
		str::text_builder_append_string(lines, string_t("define i32 @main() {\n"));
		str::text_builder_append_string(lines, string_t("entry:\n"));
		str::text_builder_append_string(lines, (string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n")));
		str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(callValue->type_ref_id));
		str::text_builder_append_string(lines, string_t("}\n"));
		return str::text_builder_take_string(lines);
	}
	string_t targetFunctionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests(targetName, targetEmission, targetRequests));
	if (static_cast<bool>(php::identical(targetFunctionText, string_t("")))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-direct-call\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + cast<string_t>(targetFunctionText));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %direct_call_result_1 = call ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(targetName) + string_t("()\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" %direct_call_result_1\n"));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(callValue->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}
