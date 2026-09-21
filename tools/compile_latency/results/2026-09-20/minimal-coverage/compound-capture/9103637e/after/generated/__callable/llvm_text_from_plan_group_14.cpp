#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/LlvmPrimitiveAbiDescriptorRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_string_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_module_declarations.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_string_literal_globals_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_string_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_string_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_echo_string_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::function_body_text_from_echo_string_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[123]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, ((cast<int_t<>>(emission->value_count) * static_cast<int_t<> >(160)) + static_cast<int_t<> >(512)));
	BackendEmissionValueRow returnValue = BackendEmissionValueRow{};
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id())))) {
			if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id(), value->type_ref_id)))) {
				continue;
			}
			shared_p<BackendStringLiteralOperandRow> literal = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, __latency_fn_backend_emission_decisions_value_work_id(value));
			if (static_cast<bool>((cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0)))) {
				__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls(lines, cast<int_t<>>(value->value_id), literal);
			}
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_return_value_id())))) {
				returnValue = value;
			}
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(returnValue->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	str::text_builder_append_string(lines, (string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(cast<int_t<>>(returnValue->value)) + string_t("\n")));
	return str::text_builder_take_string(lines);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_echo_string_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_echo_string_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[124]);
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, emission->value_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lastValue->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	string_t bodyText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_echo_string_emission_and_backend_requests(emission, requests, returnType));
	if (static_cast<bool>(php::identical(bodyText, string_t("")))) {
		return string_t("");
	}
	str::text_builder globals = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(globals, ((cast<int_t<>>(requests->string_literal_operand_count) * static_cast<int_t<> >(96)) + static_cast<int_t<> >(32)));
	__latency_fn_llvm_text_from_plan_append_string_literal_globals_from_emission_and_backend_requests(globals, emission, requests);
	string_t globalText = required_cast<string_t>(str::text_builder_take_string(globals));
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	return (string_t("; v2 PHS backend-emission LLVM text sink\n; backend_value_stack_status=ok\n; backend_local_slot_status=ok\n; runtime_string_abi_status=literal_echo_cleanup_ready\nsource_filename = \"compiler/reset-runner-entry\"\ntarget triple = \"x86_64-pc-linux-gnu\"\n\n") + cast<string_t>(globalText) + cast<string_t>(__latency_fn_llvm_abi_declarations_module_declarations(bridge)) + string_t("\n") + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n") + string_t("entry:\n") + cast<string_t>(bodyText) + string_t("}\n") + string_t("\n") + string_t("define i32 @main() {\n") + string_t("entry:\n") + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n") + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(lastValue->type_ref_id)) + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_type_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[125]);
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	if (static_cast<bool>(php::identical(cast<int_t<>>(trait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_runtime_opaque_id())))) {
		return string_t("ptr");
	}
	shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor = __latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	return descriptor->llvm_type;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_function_name_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[126]);
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_row_by_id(symbols, symbolId);
	string_t symbolName = required_cast<string_t>(__latency_fn_project_symbol_index_name(symbols, symbol));
	if (static_cast<bool>(php::identical(symbolName, string_t("run")))) {
		return string_t("@scpp_run");
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(symbolName, string_t(""))))) {
		return (string_t("@scpp_v2_function_") + cast<string_t>(symbolName));
	}
	return string_t("@scpp_v2_function_unknown");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_return_value_text_from_emission(BackendEmissionDecisionArtifact& emission, BackendEmissionValueRow value, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::return_value_text_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[131]);
	BackendBinaryOperandRow binaryOperand = __latency_fn_backend_emission_decisions_binary_operand_by_owner_row_id(emission, value->value_id);
	if (static_cast<bool>((cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
		string_t tempName = required_cast<string_t>((string_t("%binary_add_") + cast<string_t>(cast<int_t<>>(value->value_id))));
		string_t text = required_cast<string_t>((string_t("  ") + cast<string_t>(tempName) + string_t(" = add ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(cast<int_t<>>(binaryOperand->left_value)) + string_t(", ") + cast<string_t>(cast<int_t<>>(binaryOperand->right_value)) + string_t("\n")));
		text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(tempName) + string_t("\n"));
		return text;
	}
	return (string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(cast<int_t<>>(value->value)) + string_t("\n"));
}

}
