#include <scpp/lang/php.hpp>
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_symbol.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_only_function_body_statement_row.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_result_name_for_operator.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_by_reference_parameter_mutation_return_function_text_from_symbol_rows.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_function_text_from_symbol_rows.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_return_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_signature_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_has_parameter_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_call_argument_list_text.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_function_text_from_symbol_rows(const string_t& llvmFunctionName, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_function_text_from_symbol_rows", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[149]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(symbol->parameter_count), static_cast<int_t<> >(0)) || (!__latency_fn_project_symbol_index_symbol_has_parameter_rows(symbols, symbol))))) {
		return string_t("");
	}
	string_t byReferenceText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_by_reference_parameter_mutation_return_function_text_from_symbol_rows(llvmFunctionName, symbols, symbol, model, sourceText));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(byReferenceText, string_t(""))))) {
		return byReferenceText;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	shared_p<FrontendBodySummaryArtifact> summary = __latency_fn_frontend_body_summaries_from_symbol(model, sourceText, symbol, counters);
	FrontendBodySummaryRow statementRow = __latency_fn_frontend_body_summaries_only_function_body_statement_row(summary);
	if (static_cast<bool>((php::identical(cast<int_t<>>(statementRow->statement_node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(statementRow->statement_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id()))))) {
		return string_t("");
	}
	FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementRow->statement_node_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->node_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		return string_t("");
	}
	FrontendExpressionPayloadRow binary = __latency_fn_frontend_model_tables_expression_by_id(model, valueNode->payload_row_id, counters);
	FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->left_node_id, counters);
	FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->right_node_id, counters);
	int_t<std::uint32_t> leftTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> rightTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	string_t leftOperand = required_cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_return_operand_text(symbols, symbol, model, sourceText, leftNode, leftTypeRefId));
	string_t rightOperand = required_cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_return_operand_text(symbols, symbol, model, sourceText, rightNode, rightTypeRefId));
	shared_p<SemanticOperatorLookupRow> parsedOperator = __latency_fn_semantic_operator_lookup_row_by_operator_id(binary->operator_id);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(parsedOperator->feature_id, leftTypeRefId, rightTypeRefId);
	if (static_cast<bool>((((php::identical(leftOperand, string_t("")) || php::identical(rightOperand, string_t(""))) || php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(symbol->return_type_ref_id))))) {
		return string_t("");
	}
	string_t returnType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(symbol->return_type_ref_id));
	string_t operandType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(cast<int_t<std::uint32_t>>(leftTypeRefId)));
	string_t resultName = required_cast<string_t>(__latency_fn_llvm_text_from_plan_binary_result_name_for_operator(operatorRow, static_cast<int_t<> >(1)));
	string_t text = required_cast<string_t>((string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("(") + cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_signature_text(symbols, symbol)) + string_t(") {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  ") + cast<string_t>(resultName) + string_t(" = ") + cast<string_t>(__latency_fn_llvm_text_from_plan_binary_instruction_for_operator(operatorRow)) + string_t(" ") + cast<string_t>(operandType) + string_t(" ") + cast<string_t>(leftOperand) + string_t(", ") + cast<string_t>(rightOperand) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(resultName) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_call_argument_list_text(const vector_t<BackendCallArgumentRow>& arguments) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::call_argument_list_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[150]);
	string_t text = required_cast<string_t>(string_t(""));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(arguments)))) {
		BackendCallArgumentRow argument = arguments.at(index);
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			text = (cast<string_t>(text) + string_t(", "));
		}
		text = (cast<string_t>(text) + string_t("i64 ") + cast<string_t>(cast<int_t<>>(argument->value)));
		index = (index + static_cast<int_t<> >(1));
	}
	return text;
}

}
