#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_i64_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_int_literal_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_has_echo_string_and_backend_requests.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_hex_digit.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_echo_scalar_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[100]);
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id(emission, emission->value_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lastValue->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	string_t bodyText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission(emission, returnType));
	if (static_cast<bool>(php::identical(bodyText, string_t("")))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("@.scpp_echo_i64_fmt = private unnamed_addr constant [5 x i8] c\"%ld\\0A\\00\"\n"));
	text = (cast<string_t>(text) + string_t("declare i32 @printf(ptr, ...)\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(bodyText));
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
bool_t __latency_fn_llvm_text_from_plan_emission_has_echo_scalar_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_has_echo_scalar_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[101]);
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id())))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::function_body_text_from_echo_scalar_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[102]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, __latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes(cast<int_t<>>(emission->value_count)));
	BackendEmissionValueRow returnValue = BackendEmissionValueRow{};
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
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

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_module_text_from_echo_scalar_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::module_text_from_echo_scalar_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[103]);
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, emission->value_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lastValue->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	string_t bodyText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission_and_backend_requests(emission, requests, returnType));
	if (static_cast<bool>(php::identical(bodyText, string_t("")))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("; v2 PHS backend-emission LLVM text sink\n"));
	text = (cast<string_t>(text) + string_t("; backend_value_stack_status=ok\n"));
	text = (cast<string_t>(text) + string_t("; backend_local_slot_status=ok\n"));
	text = (cast<string_t>(text) + string_t("source_filename = \"compiler/reset-runner-entry\"\n"));
	text = (cast<string_t>(text) + string_t("target triple = \"x86_64-pc-linux-gnu\"\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("@.scpp_echo_i64_fmt = private unnamed_addr constant [5 x i8] c\"%ld\\0A\\00\"\n"));
	text = (cast<string_t>(text) + string_t("declare i32 @printf(ptr, ...)\n"));
	text = (cast<string_t>(text) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" @scpp_run() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(bodyText));
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
bool_t __latency_fn_llvm_text_from_plan_emission_has_echo_string_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_has_echo_string_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[104]);
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id())))) {
			shared_p<BackendStringLiteralOperandRow> literal = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, __latency_fn_backend_emission_decisions_value_work_id(value));
			return bool_t((cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0)));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_llvm_hex_digit(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_hex_digit", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[105]);
	string_t digits = required_cast<string_t>(string_t("0123456789ABCDEF"));
	if (static_cast<bool>(((value < static_cast<int_t<> >(0)) || (value > static_cast<int_t<> >(15))))) {
		return string_t("0");
	}
	return str::byte_slice(digits, value, static_cast<int_t<> >(1));
}

}
