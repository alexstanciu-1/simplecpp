#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__types/ScalarLoopBodyStatementSequence.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_variable_name_text.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_source_text_for_node.hpp"
#include "__callable/__latency_fn_local_body_lowering_type_ref_uses_inline_scalar_storage.hpp"
#include "__callable/__latency_fn_local_body_lowering_variable_name_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_literal_status_ready.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_binary_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_literal_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_assignment.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_assignment_effect_source_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_from_transfer_kind.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_from_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_break_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_continue_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_assignment_effect_source_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, FrontendNodeRow conditionNode, const vector_t<string_t>& localNames, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const vector_t<int_t<std::uint32_t>>& localTypeRefIds, int_t<std::uint32_t>& conditionTypeRefId, int_t<std::uint32_t>& conditionLocalSourceRowId, int_t<std::uint32_t>& conditionProviderTypeRefId, int_t<std::uint16_t>& conditionFeatureId, int_t<std::uint16_t>& conditionLocalOperationId, int_t<std::int32_t>& conditionValue) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::collect_local_immediate_bool_condition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[11]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(conditionNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(conditionNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendExpressionPayloadRow conditionBinary = __latency_fn_frontend_model_tables_expression_by_id(model, conditionNode->payload_row_id, counters);
	FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, conditionBinary->left_node_id, counters);
	FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, conditionBinary->right_node_id, counters);
	string_t leftName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, leftNode));
	int_t<std::uint32_t> leftLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_source_row_id_for_name(localNames, localSourceRowIds, leftName));
	int_t<std::uint32_t> providerTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_type_ref_id_for_name(localNames, localTypeRefIds, leftName));
	int_t<std::uint32_t> rightTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::int32_t> rightValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
	if (static_cast<bool>((php::identical(cast<int_t<>>(rightNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(rightNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
		FrontendLiteralPayloadRow rightLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, rightNode->payload_row_id, counters);
		rightTypeRefId = rightLiteral->type_ref_id;
		rightValue = rightLiteral->numeric_payload;
	}
	shared_p<SemanticOperatorLookupRow> parsedOperator = __latency_fn_semantic_operator_lookup_row_by_operator_id(conditionBinary->operator_id);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(parsedOperator->feature_id, providerTypeRefId, rightTypeRefId);
	int_t<std::uint16_t> localOperationId = required_cast<int_t<std::uint16_t>>(operatorRow->local_immediate_operation_id);
	if (static_cast<bool>((((php::identical(cast<int_t<>>(leftLocalSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(__latency_fn_type_refs_bool_id()))) || php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	conditionTypeRefId = operatorRow->result_type_ref_id;
	conditionLocalSourceRowId = cast<int_t<std::uint32_t>>(leftLocalSourceRowId);
	conditionProviderTypeRefId = cast<int_t<std::uint32_t>>(providerTypeRefId);
	conditionFeatureId = operatorRow->feature_id;
	conditionLocalOperationId = cast<int_t<std::uint16_t>>(localOperationId);
	conditionValue = cast<int_t<std::int32_t>>(rightValue);
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_append_assignment(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, shared_p<ScalarBodyBackendCollection>& body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::append_assignment", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[12]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())) && php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow targetNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	string_t targetName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, targetNode));
	int_t<std::uint32_t> targetLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(body, targetName));
	int_t<std::uint32_t> targetTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name(body, targetName));
	if (static_cast<bool>(((php::identical(cast<int_t<>>(targetLocalSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(targetTypeRefId), static_cast<int_t<> >(0))) || (!__latency_fn_local_body_lowering_type_ref_uses_inline_scalar_storage(targetTypeRefId))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
		FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, valueNode->payload_row_id, counters);
		if (static_cast<bool>(((!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(targetTypeRefId, literal->type_ref_id)) || (!__latency_fn_project_symbol_index_literal_status_ready(literal->literal_status_id))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		string_t valueText = required_cast<string_t>(string_t(""));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal(targetTypeRefId)))) {
			valueText = __latency_fn_local_body_lowering_source_text_for_node(model, sourceText, valueNode);
		}
		__latency_fn_scalar_body_backend_collection_append_literal_assignment(body, statementNode->node_id, targetTypeRefId, targetLocalSourceRowId, valueNode->node_id, literal->numeric_payload, valueText, __latency_fn_backend_preflight_requests_local_operation_store_id());
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		FrontendExpressionPayloadRow binary = __latency_fn_frontend_model_tables_expression_by_id(model, valueNode->payload_row_id, counters);
		FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->left_node_id, counters);
		FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->right_node_id, counters);
		string_t leftName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, leftNode));
		int_t<std::uint32_t> leftLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(body, leftName));
		int_t<std::uint32_t> providerTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name(body, leftName));
		int_t<std::uint32_t> rightSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::uint32_t> rightTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		int_t<std::int32_t> rightValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
		if (static_cast<bool>((php::identical(cast<int_t<>>(rightNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(rightNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
			FrontendLiteralPayloadRow rightLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, rightNode->payload_row_id, counters);
			rightSourceRowId = rightNode->node_id;
			rightTypeRefId = rightLiteral->type_ref_id;
			rightValue = rightLiteral->numeric_payload;
		}
		else {
			string_t rightName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, rightNode));
			rightSourceRowId = __latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(body, rightName);
			rightTypeRefId = __latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name(body, rightName);
		}
		shared_p<SemanticOperatorLookupRow> parsedOperator = __latency_fn_semantic_operator_lookup_row_by_operator_id(binary->operator_id);
		shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(parsedOperator->feature_id, providerTypeRefId, rightTypeRefId);
		int_t<std::uint16_t> localOperationId = required_cast<int_t<std::uint16_t>>(operatorRow->local_store_operation_id);
		if (static_cast<bool>(((((php::identical(cast<int_t<>>(leftLocalSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(rightSourceRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(targetTypeRefId))) || php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		__latency_fn_scalar_body_backend_collection_append_binary_assignment(body, statementNode->node_id, targetTypeRefId, targetLocalSourceRowId, rightSourceRowId, rightValue, localOperationId, valueNode->node_id, operatorRow->feature_id, providerTypeRefId, operatorRow->result_type_ref_id, leftLocalSourceRowId, rightSourceRowId, rightValue);
		return bool_t(static_cast<bool_t>(true));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scalar_body_statement_collection_assignment_effect_source_row_id(shared_p<FrontendModel> model, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::assignment_effect_source_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[13]);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		return valueNode->node_id;
	}
	return statementNode->node_id;
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
shared_p<ScalarLoopBodyStatementSequence> __latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t> bodyFirstNodeId, int_t<std::uint32_t> bodyLastNodeId, shared_p<ScalarBodyBackendCollection>& body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::append_loop_body_statement_sequence", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[14]);
	shared_p<ScalarLoopBodyStatementSequence> sequence = create<ScalarLoopBodyStatementSequence>();
	sequence->first_statement_row_id = bodyFirstNodeId;
	sequence->last_statement_row_id = bodyLastNodeId;
	if (static_cast<bool>((php::identical(cast<int_t<>>(bodyFirstNodeId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(bodyLastNodeId), static_cast<int_t<> >(0))))) {
		return sequence;
	}
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(bodyFirstNodeId));
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
			return sequence;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())))) {
			if (static_cast<bool>((cast<int_t<>>(sequence->terminator_kind_id) > static_cast<int_t<> >(0)))) {
				return sequence;
			}
			FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
			if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, statementNode, statement, body)))) {
				return sequence;
			}
			sequence->effect_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(sequence->effect_count) + static_cast<int_t<> >(1)));
			sequence->last_effect_source_row_id = __latency_fn_scalar_body_statement_collection_assignment_effect_source_row_id(model, statementNode, statement, counters);
		}
		else {
			if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_break_id())) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_continue_id()))))) {
				if (static_cast<bool>(((cast<int_t<>>(statementNode->next_sibling_node_id) > static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(statementNode->node_id), cast<int_t<>>(bodyLastNodeId))))) {
					return sequence;
				}
				int_t<std::uint16_t> transferKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_transfers_transfer_kind_from_statement(statementNode));
				if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), static_cast<int_t<> >(0)))) {
					return sequence;
				}
				sequence->terminator_statement_row_id = statementNode->node_id;
				sequence->terminator_kind_id = __latency_fn_backend_preflight_requests_control_flow_terminator_from_transfer_kind(transferKindId);
				if (static_cast<bool>(php::identical(cast<int_t<>>(sequence->terminator_kind_id), static_cast<int_t<> >(0)))) {
					return sequence;
				}
				if (static_cast<bool>(php::identical(cast<int_t<>>(sequence->effect_count), static_cast<int_t<> >(0)))) {
					sequence->last_effect_source_row_id = statementNode->node_id;
				}
			}
			else {
				return sequence;
			}
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->node_id), cast<int_t<>>(bodyLastNodeId)))) {
			sequence->ready = ((cast<int_t<>>(sequence->effect_count) > static_cast<int_t<> >(0)) || (cast<int_t<>>(sequence->terminator_kind_id) > static_cast<int_t<> >(0)));
			return sequence;
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	return sequence;
}

}
