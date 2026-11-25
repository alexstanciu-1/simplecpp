#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_result_name_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_operand_llvm_type_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_operand_llvm_type_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_result_name_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_binary_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_ternary_select_text.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_by_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_binary_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_ternary_select_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_lowering_step_kind.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_literal_function_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_function_text_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_binary_result_name_for_operator(shared_p<SemanticOperatorLookupRow> operatorRow, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::binary_result_name_for_operator", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[132]);
	string_t key = required_cast<string_t>(operatorRow->binary_result_key);
	if (static_cast<bool>(php::identical(key, string_t("")))) {
		key = string_t("add");
	}
	return (string_t("%binary_") + cast<string_t>(key) + string_t("_") + cast<string_t>(valueId));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_binary_operand_llvm_type_for_operator(shared_p<SemanticOperatorLookupRow> operatorRow) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::binary_operand_llvm_type_for_operator", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[133]);
	if (static_cast<bool>((cast<int_t<>>(operatorRow->lhs_type_ref_id) > static_cast<int_t<> >(0)))) {
		return __latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(operatorRow->lhs_type_ref_id);
	}
	return string_t("i64");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_binary_instruction_for_operator(shared_p<SemanticOperatorLookupRow> operatorRow) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::binary_instruction_for_operator", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[134]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(operatorRow->llvm_opcode, string_t(""))))) {
		return operatorRow->llvm_opcode;
	}
	return string_t("add");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_return_binary_value_text(shared_p<SemanticOperatorLookupRow> operatorRow, const string_t& returnType, int_t<> valueId, int_t<> leftValue, int_t<> rightValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::return_binary_value_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[135]);
	string_t tempName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_binary_result_name_for_operator(operatorRow, valueId));
	string_t instruction = required_cast<string_t>(__latency_fn_llvm_text_from_plan_binary_instruction_for_operator(operatorRow));
	string_t operandType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_binary_operand_llvm_type_for_operator(operatorRow));
	string_t text = required_cast<string_t>((string_t("  ") + cast<string_t>(tempName) + string_t(" = ") + cast<string_t>(instruction) + string_t(" ") + cast<string_t>(operandType) + string_t(" ") + cast<string_t>(leftValue) + string_t(", ") + cast<string_t>(rightValue) + string_t("\n")));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(tempName) + string_t("\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text(const string_t& returnType, int_t<> valueId, const string_t& conditionText, int_t<> thenValue, int_t<> elseValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::return_ternary_select_from_condition_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[136]);
	string_t tempName = required_cast<string_t>((string_t("%ternary_select_") + cast<string_t>(valueId)));
	string_t text = required_cast<string_t>((string_t("  ") + cast<string_t>(tempName) + string_t(" = select i1 ") + cast<string_t>(conditionText) + string_t(", ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(thenValue) + string_t(", ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(elseValue) + string_t("\n")));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(tempName) + string_t("\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_return_ternary_select_text(const string_t& returnType, int_t<> valueId, int_t<> conditionValue, int_t<> thenValue, int_t<> elseValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::return_ternary_select_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[137]);
	string_t conditionText = required_cast<string_t>(string_t("false"));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(conditionValue, static_cast<int_t<> >(0))))) {
		conditionText = string_t("true");
	}
	return __latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text(returnType, valueId, conditionText, thenValue, elseValue);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, BackendEmissionValueRow value, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::return_value_text_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[138]);
	int_t<std::uint32_t> workId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_emission_decisions_value_work_id(value));
	BackendBinaryOperandRow binaryOperand = __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(requests, workId);
	if (static_cast<bool>((cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
		BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_by_id(requests, workId);
		if (static_cast<bool>((php::identical(cast<int_t<>>(request->feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_ternary_select_id())) && php::identical(cast<int_t<>>(request->lowering_step_kind_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id()))))) {
			return __latency_fn_llvm_text_from_plan_return_ternary_select_text(returnType, cast<int_t<>>(value->value_id), cast<int_t<>>(request->value), cast<int_t<>>(binaryOperand->left_value), cast<int_t<>>(binaryOperand->right_value));
		}
		shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_lowering_step_kind(request->feature_id, request->lowering_step_kind_id);
		return __latency_fn_llvm_text_from_plan_return_binary_value_text(operatorRow, returnType, cast<int_t<>>(value->value_id), cast<int_t<>>(binaryOperand->left_value), cast<int_t<>>(binaryOperand->right_value));
	}
	return (string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(cast<int_t<>>(value->value)) + string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_literal_function_text_from_emission(const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::literal_function_text_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[139]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id()))))) {
		return string_t("");
	}
	BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id(emission, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(value->type_ref_id));
	string_t text = required_cast<string_t>((string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_return_value_text_from_emission(emission, value, returnType)));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests(const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::literal_function_text_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[140]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id()))))) {
		return string_t("");
	}
	BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(value->type_ref_id));
	string_t text = required_cast<string_t>((string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests(emission, requests, value, returnType)));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_local_function_text_from_emission(const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_function_text_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[141]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())) || php::identical(cast<int_t<>>(emission->local_operand_count), static_cast<int_t<> >(0))))) {
		return string_t("");
	}
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id(emission, emission->value_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lastValue->value_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	string_t text = required_cast<string_t>((string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission(emission, returnType)));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}
