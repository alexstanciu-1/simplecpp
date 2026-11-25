#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_local_emission.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_module_declarations.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_all_string_literal_globals_from_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_emission_uses_runtime_string_abi.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_runtime_abi_bridge_row_ready.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_runtime_abi_second_argument_llvm_type.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_i64_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_int_literal_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_local_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::function_body_text_from_local_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[93]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, __latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes(cast<int_t<>>(emission->value_count)));
	__latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests(lines, emission, requests, returnType);
	return str::text_builder_take_string(lines);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_local_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_local_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[94]);
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id(emission, emission->value_count);
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_emission_has_echo_scalar(emission)))) {
		text = (cast<string_t>(text) + string_t("@.scpp_echo_i64_fmt = private unnamed_addr constant [5 x i8] c\"%ld\\0A\\00\"\n"));
		text = (cast<string_t>(text) + string_t("declare i32 @printf(ptr, ...)\n\n"));
	}
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission(emission, returnType)));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(lastValue->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_local_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_local_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[95]);
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, emission->value_count);
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	bool_t usesRuntimeStringAbi = required_cast<bool_t>(__latency_fn_llvm_text_from_plan_local_emission_uses_runtime_string_abi(emission, requests));
	string_t runtimeStringPrefix = required_cast<string_t>(string_t(""));
	if (static_cast<bool>(php::condition_truthy(usesRuntimeStringAbi))) {
		str::text_builder globals = required_cast<str::text_builder>(str::text_builder_create());
		str::text_builder_reserve_bytes(globals, ((cast<int_t<>>(requests->string_literal_operand_count) * static_cast<int_t<> >(96)) + static_cast<int_t<> >(32)));
		__latency_fn_llvm_text_from_plan_append_all_string_literal_globals_from_backend_requests(globals, requests);
		runtimeStringPrefix = (cast<string_t>(str::text_builder_take_string(globals)) + cast<string_t>(__latency_fn_llvm_abi_declarations_module_declarations(__latency_fn_runtime_abi_bridge_build())) + string_t("\n"));
	}
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	if (static_cast<bool>(php::condition_truthy(usesRuntimeStringAbi))) {
		text = (cast<string_t>(text) + string_t("; runtime_string_abi_status=local_owner_borrow_release_ready\n"));
	}
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + cast<string_t>(runtimeStringPrefix));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests(emission, requests)))) {
		text = (cast<string_t>(text) + string_t("@.scpp_echo_i64_fmt = private unnamed_addr constant [5 x i8] c\"%ld\\0A\\00\"\n"));
		text = (cast<string_t>(text) + string_t("declare i32 @printf(ptr, ...)\n\n"));
	}
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission_and_backend_requests(emission, requests, returnType)));
	text = (cast<string_t>(text) + string_t("}\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define i32 @main() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %run_value = call ") + cast<string_t>(returnType) + string_t(" @scpp_run()\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(lastValue->type_ref_id)));
	text = (cast<string_t>(text) + string_t("}\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_runtime_abi_bridge_row_ready(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::runtime_abi_bridge_row_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[96]);
	return ((((cast<int_t<>>(row->bridge_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->declaration_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(row->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(row->source_consumption_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id())));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_runtime_abi_second_argument_llvm_type(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::runtime_abi_second_argument_llvm_type", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[97]);
	if (static_cast<bool>(php::condition_truthy(php::str_contains(row->llvm_argument_signature, string_t(", i32"))))) {
		return string_t("i32");
	}
	if (static_cast<bool>(php::condition_truthy(php::str_contains(row->llvm_argument_signature, string_t(", i64"))))) {
		return string_t("i64");
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_emission_has_echo_scalar(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_has_echo_scalar", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[98]);
	auto __latency_local_0 = emission->values;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto value = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id())))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission(BackendEmissionDecisionArtifact& emission, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::function_body_text_from_echo_scalar_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[99]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, __latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes(cast<int_t<>>(emission->value_count)));
	BackendEmissionValueRow returnValue = BackendEmissionValueRow{};
	auto __latency_local_0 = emission->values;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto value = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id())))) {
			if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id(), value->type_ref_id)))) {
				continue;
			}
			__latency_fn_llvm_text_from_plan_append_echo_i64_call_text(lines, cast<int_t<>>(value->value_id), value->type_ref_id, __latency_fn_llvm_text_from_plan_int_literal_operand_text(cast<int_t<>>(value->value)));
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
