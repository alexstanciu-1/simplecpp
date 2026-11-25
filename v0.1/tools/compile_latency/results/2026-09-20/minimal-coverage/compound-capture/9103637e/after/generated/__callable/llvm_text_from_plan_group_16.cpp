#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_function_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_source_range_text_equals.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_int_literal_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_return_operand_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_signature_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_signature_text_with_reference_slots.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_symbol.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_function_body_statement_rows.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_result_name_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_by_reference_parameter_mutation_return_function_text_from_symbol_rows.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_signature_text_with_reference_slots.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_has_parameter_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests(str::text_builder& lines, const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_function_text_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[142]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())) || php::identical(cast<int_t<>>(requests->local_operand_count), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, emission->value_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lastValue->value_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(lastValue->type_ref_id));
	str::text_builder_append_string(lines, (string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n")));
	str::text_builder_append_string(lines, string_t("entry:\n"));
	__latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests(lines, emission, requests, returnType);
	str::text_builder_append_string(lines, string_t("}\n\n"));
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_local_function_text_from_emission_and_backend_requests(const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_function_text_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[143]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, (__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes(cast<int_t<>>(emission->value_count)) + static_cast<int_t<> >(128)));
	if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests(lines, llvmFunctionName, emission, requests)))) {
		return string_t("");
	}
	return str::text_builder_take_string(lines);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, int_t<std::uint32_t>& typeRefIdOut) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_arg_index_for_variable_node", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[144]);
	typeRefIdOut = __latency_fn_structure_row_ids_none_id();
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceRangeRow variableNameRange = __latency_fn_frontend_body_summaries_variable_name_range(model, variableNode, counters);
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, symbol));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(parameters)))) {
		ProjectSymbolParameterRow parameter = parameters.at(index);
		FrontendNodeRow parameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameter->parameter_source_row_id, counters);
		SourceRangeRow parameterNameRange = __latency_fn_frontend_body_summaries_variable_name_range(model, parameterNode, counters);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_body_summaries_source_range_text_equals(sourceText, variableNameRange, parameterNameRange)))) {
			typeRefIdOut = parameter->type_ref_id;
			return (index + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_return_operand_text(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow node, int_t<std::uint32_t>& typeRefIdOut) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_return_operand_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[145]);
	typeRefIdOut = __latency_fn_structure_row_ids_none_id();
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id())))) {
		FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, node->payload_row_id, counters);
		if (static_cast<bool>((!__latency_fn_frontend_body_summaries_ready_literal_payload(literal)))) {
			return string_t("");
		}
		typeRefIdOut = literal->type_ref_id;
		return __latency_fn_llvm_text_from_plan_int_literal_operand_text(cast<int_t<>>(literal->numeric_payload));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id())))) {
		int_t<std::uint32_t> argTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<> argIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(symbols, symbol, model, sourceText, node, argTypeRefId));
		if (static_cast<bool>(((argIndex <= static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(argTypeRefId), static_cast<int_t<> >(0))))) {
			return string_t("");
		}
		typeRefIdOut = cast<int_t<std::uint32_t>>(argTypeRefId);
		return (string_t("%arg_") + cast<string_t>(argIndex));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_signature_text(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_signature_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[146]);
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, symbol));
	string_t text = required_cast<string_t>(string_t(""));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(parameters)))) {
		ProjectSymbolParameterRow parameter = parameters.at(index);
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			text = (cast<string_t>(text) + string_t(", "));
		}
		text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(parameter->type_ref_id)) + string_t(" %arg_") + cast<string_t>((index + static_cast<int_t<> >(1))));
		index = (index + static_cast<int_t<> >(1));
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_signature_text_with_reference_slots(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_signature_text_with_reference_slots", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[147]);
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, symbol));
	string_t text = required_cast<string_t>(string_t(""));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(parameters)))) {
		ProjectSymbolParameterRow parameter = parameters.at(index);
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			text = (cast<string_t>(text) + string_t(", "));
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_parameter_is_by_reference(parameter)))) {
			text = (cast<string_t>(text) + string_t("ptr %arg_") + cast<string_t>((index + static_cast<int_t<> >(1))));
		}
		else {
			text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(parameter->type_ref_id)) + string_t(" %arg_") + cast<string_t>((index + static_cast<int_t<> >(1))));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_by_reference_parameter_mutation_return_function_text_from_symbol_rows(const string_t& llvmFunctionName, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::by_reference_parameter_mutation_return_function_text_from_symbol_rows", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[148]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(symbol->parameter_count), static_cast<int_t<> >(1)) || (!__latency_fn_project_symbol_index_symbol_has_parameter_rows(symbols, symbol))))) {
		return string_t("");
	}
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, symbol));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(parameters), static_cast<int_t<> >(1))))) {
		return string_t("");
	}
	ProjectSymbolParameterRow parameter = parameters.at(static_cast<int_t<> >(0));
	if (static_cast<bool>(((!__latency_fn_project_symbol_index_parameter_is_by_reference(parameter)) || php::not_identical(cast<int_t<>>(parameter->type_ref_id), cast<int_t<>>(symbol->return_type_ref_id))))) {
		return string_t("");
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	shared_p<FrontendBodySummaryArtifact> summary = __latency_fn_frontend_body_summaries_from_symbol(model, sourceText, symbol, counters);
	vector_t<FrontendBodySummaryRow> bodyRows = required_cast<vector_t<FrontendBodySummaryRow>>(__latency_fn_frontend_body_summaries_function_body_statement_rows(summary));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(bodyRows), static_cast<int_t<> >(2))))) {
		return string_t("");
	}
	FrontendBodySummaryRow assignmentRow = bodyRows.at(static_cast<int_t<> >(0));
	FrontendBodySummaryRow returnRow = bodyRows.at(static_cast<int_t<> >(1));
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(assignmentRow->statement_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())) || php::not_identical(cast<int_t<>>(returnRow->statement_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id()))))) {
		return string_t("");
	}
	FrontendNodeRow assignmentNode = __latency_fn_frontend_model_tables_node_by_id(model, assignmentRow->statement_node_id, counters);
	FrontendNodeRow returnNode = __latency_fn_frontend_model_tables_node_by_id(model, returnRow->statement_node_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(assignmentNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(returnNode->node_id), static_cast<int_t<> >(0))))) {
		return string_t("");
	}
	FrontendStatementPayloadRow assignment = __latency_fn_frontend_model_tables_statement_by_id(model, assignmentNode->payload_row_id, counters);
	FrontendNodeRow assignmentTarget = __latency_fn_frontend_model_tables_node_by_id(model, assignment->target_node_id, counters);
	FrontendNodeRow assignmentValue = __latency_fn_frontend_model_tables_node_by_id(model, assignment->value_node_id, counters);
	FrontendStatementPayloadRow returnStatement = __latency_fn_frontend_model_tables_statement_by_id(model, returnNode->payload_row_id, counters);
	FrontendNodeRow returnValue = __latency_fn_frontend_model_tables_node_by_id(model, returnStatement->value_node_id, counters);
	int_t<std::uint32_t> assignmentTargetTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> assignmentTargetIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(symbols, symbol, model, sourceText, assignmentTarget, assignmentTargetTypeRefId));
	int_t<> returnIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(symbols, symbol, model, sourceText, returnValue, returnTypeRefId));
	if (static_cast<bool>((((((php::not_identical(assignmentTargetIndex, static_cast<int_t<> >(1)) || php::not_identical(returnIndex, static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(assignmentTargetTypeRefId), cast<int_t<>>(parameter->type_ref_id))) || php::not_identical(cast<int_t<>>(returnTypeRefId), cast<int_t<>>(parameter->type_ref_id))) || php::not_identical(cast<int_t<>>(assignmentValue->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))) || php::not_identical(cast<int_t<>>(assignmentValue->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		return string_t("");
	}
	FrontendExpressionPayloadRow binary = __latency_fn_frontend_model_tables_expression_by_id(model, assignmentValue->payload_row_id, counters);
	FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->left_node_id, counters);
	FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->right_node_id, counters);
	int_t<std::uint32_t> leftTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> leftIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(symbols, symbol, model, sourceText, leftNode, leftTypeRefId));
	if (static_cast<bool>((((php::not_identical(leftIndex, static_cast<int_t<> >(1)) || php::not_identical(cast<int_t<>>(leftTypeRefId), cast<int_t<>>(parameter->type_ref_id))) || php::not_identical(cast<int_t<>>(rightNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))) || php::not_identical(cast<int_t<>>(rightNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
		return string_t("");
	}
	FrontendLiteralPayloadRow rightLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, rightNode->payload_row_id, counters);
	shared_p<SemanticOperatorLookupRow> parsedOperator = __latency_fn_semantic_operator_lookup_row_by_operator_id(binary->operator_id);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(parsedOperator->feature_id, parameter->type_ref_id, rightLiteral->type_ref_id);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(parameter->type_ref_id))) || (!__latency_fn_frontend_body_summaries_ready_literal_payload(rightLiteral))))) {
		return string_t("");
	}
	string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(parameter->type_ref_id));
	int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(parameter->type_ref_id));
	string_t resultName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_binary_result_name_for_operator(operatorRow, static_cast<int_t<> >(1)));
	string_t text = required_cast<string_t>((string_t("define ") + cast<string_t>(llvmType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("(") + cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_signature_text_with_reference_slots(symbols, symbol)) + string_t(") {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %ref_load_1 = load ") + cast<string_t>(llvmType) + string_t(", ptr %arg_1, align ") + cast<string_t>(align) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  ") + cast<string_t>(resultName) + string_t(" = ") + cast<string_t>(__latency_fn_llvm_text_from_plan_binary_instruction_for_operator(operatorRow)) + string_t(" ") + cast<string_t>(llvmType) + string_t(" %ref_load_1, ") + cast<string_t>(cast<int_t<>>(rightLiteral->numeric_payload)) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  store ") + cast<string_t>(llvmType) + string_t(" ") + cast<string_t>(resultName) + string_t(", ptr %arg_1, align ") + cast<string_t>(align) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  %ref_load_2 = load ") + cast<string_t>(llvmType) + string_t(", ptr %arg_1, align ") + cast<string_t>(align) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(llvmType) + string_t(" %ref_load_2\n"));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}
