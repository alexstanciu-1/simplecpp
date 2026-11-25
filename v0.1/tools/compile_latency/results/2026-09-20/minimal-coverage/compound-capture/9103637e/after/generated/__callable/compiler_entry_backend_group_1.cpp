#include <scpp/lang/php.hpp>
#include "__types/AnalysisContext.hpp"
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityConsumerPlan.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/ControlTransferTargetDecision.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__types/ScalarConditionOperand.hpp"
#include "__types/ScalarIfBranchOutcome.hpp"
#include "__types/ScalarLoopBodyStatementSequence.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_analysis_context_append_entry.hpp"
#include "__callable/__latency_fn_analysis_context_new_context.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_scalar_local_body_emission.hpp"
#include "__callable/__latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_from_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_if_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_if_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_literal_scalar_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_load_return_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_variable_name_text.hpp"
#include "__callable/__latency_fn_lowering_plan_from_entry_and_backend_requests.hpp"
#include "__callable/__latency_fn_lowering_plan_new_plan.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_return_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_dependency_graph_from_symbols_references_and_contracts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_symbol_index_literal_status_ready.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_coverage_assignment_sources.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_type_refs_to_table.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_new_collection.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_local_declaration.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_is_local_immediate.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_ready.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests_append_while_statement_sequence_requests.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_for_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_if_literal_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_literal_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_local_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_while_return_plan.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_storage_id.hpp"
#include "__callable/__latency_fn_type_refs_add_type.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_type_refs_table_from_project_symbols.hpp"
namespace scpp { extern const int __latency_lines_compiler_entry_backend[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_compiler_entry_backend_scalar_local_body_emission(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<LoweringPlan>& plan, shared_p<CapabilityCoverageArtifact>& capabilityCoverage) {
	SCPP_CALL_DEPTH_GUARD("compiler_entry_backend::scalar_local_body_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/compiler_entry_backend.phs", __latency_lines_compiler_entry_backend[4]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(symbol->parameter_count), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
		plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
		return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	}
	if (static_cast<bool>((!__latency_fn_primitive_abi_adapter_matrix_main_return_supported_for_type_ref(symbol->return_type_ref_id)))) {
		plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
		return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	shared_p<ScalarBodyBackendCollection> body = __latency_fn_scalar_body_backend_collection_new_collection();
	vector_t<int_t<std::uint32_t>> initialLiteralNodeIds = {};
	vector_t<int_t<std::int32_t>> initialLiteralValues = {};
	int_t<std::uint32_t> returnSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnValueSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnBinarySourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> returnBinaryFeatureId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	int_t<std::uint32_t> returnBinaryProviderTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnBinaryResultTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnBinaryLeftLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> returnBinaryRightSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::int32_t> returnBinaryRightValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint16_t> returnBinaryLocalOperationId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	int_t<std::uint32_t> ifSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> ifConditionSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> ifBodyFirstSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> ifBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> ifElseBodyFirstSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> ifElseBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	shared_p<ScalarConditionOperand> ifCondition = create<ScalarConditionOperand>();
	int_t<std::uint32_t> whileSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> whileConditionSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> whileConditionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::int32_t> whileConditionValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint32_t> whileConditionLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> whileConditionProviderTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> whileConditionFeatureId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	int_t<std::uint16_t> whileConditionLocalOperationId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	shared_p<ScalarLoopBodyStatementSequence> whileBodySequence = create<ScalarLoopBodyStatementSequence>();
	int_t<std::uint32_t> forSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forConditionSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forInitStatementRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forUpdateStatementRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forUpdateLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forConditionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::int32_t> forConditionValue = required_cast<int_t<std::int32_t>>(__latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint32_t> forConditionLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> forConditionProviderTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> forConditionFeatureId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	int_t<std::uint16_t> forConditionLocalOperationId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	shared_p<ScalarLoopBodyStatementSequence> forBodySequence = create<ScalarLoopBodyStatementSequence>();
	FrontendNodeRow thenLiteralNode = FrontendNodeRow{};
	FrontendLiteralPayloadRow thenLiteral = FrontendLiteralPayloadRow{};
	FrontendNodeRow fallbackLiteralNode = FrontendNodeRow{};
	FrontendLiteralPayloadRow fallbackLiteral = FrontendLiteralPayloadRow{};
	FrontendNodeRow returnLiteralNode = FrontendNodeRow{};
	FrontendLiteralPayloadRow returnLiteral = FrontendLiteralPayloadRow{};
	shared_p<ScalarIfBranchOutcome> ifBranchOutcome = create<ScalarIfBranchOutcome>();
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(symbol->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
			plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
			return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
		}
		FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())))) {
			if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_local_declaration(model, sourceText, counters, statementNode, statement, body)))) {
				plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
				return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
			}
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())))) {
				if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, statementNode, statement, body)))) {
					plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
					return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
				}
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id())))) {
					if (static_cast<bool>((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)))) {
						plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
						return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
					}
					FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
					FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->body_first_node_id, counters);
					if (static_cast<bool>(((php::identical(cast<int_t<>>(statement->body_first_node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(statement->body_first_node_id), cast<int_t<>>(statement->body_last_node_id))) || php::not_identical(cast<int_t<>>(bodyNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
						plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
						return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
					}
					FrontendStatementPayloadRow bodyStatement = __latency_fn_frontend_model_tables_statement_by_id(model, bodyNode->payload_row_id, counters);
					if (static_cast<bool>((php::identical(cast<int_t<>>(conditionNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(conditionNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
						string_t conditionName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, conditionNode));
						int_t<std::uint32_t> conditionLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_source_row_id_for_name(body->local_names, body->local_source_row_ids, conditionName));
						int_t<std::uint32_t> conditionTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_type_ref_id_for_name(body->local_names, body->local_type_ref_ids, conditionName));
						if (static_cast<bool>((php::identical(cast<int_t<>>(conditionLocalSourceRowId), static_cast<int_t<> >(0)) || (!__latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref(conditionTypeRefId))))) {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
						ifCondition->ready = static_cast<bool_t>(true);
						ifCondition->operand_kind_id = __latency_fn_scalar_body_statement_collection_condition_operand_local_id();
						ifCondition->type_ref_id = conditionTypeRefId;
						ifCondition->local_source_row_id = conditionLocalSourceRowId;
					}
					else {
						if (static_cast<bool>((php::identical(cast<int_t<>>(conditionNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(conditionNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
							FrontendExpressionPayloadRow conditionBinary = __latency_fn_frontend_model_tables_expression_by_id(model, conditionNode->payload_row_id, counters);
							FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, conditionBinary->left_node_id, counters);
							FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, conditionBinary->right_node_id, counters);
							string_t leftName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, leftNode));
							int_t<std::uint32_t> leftLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_source_row_id_for_name(body->local_names, body->local_source_row_ids, leftName));
							int_t<std::uint32_t> providerTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_type_ref_id_for_name(body->local_names, body->local_type_ref_ids, leftName));
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
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							ifCondition->ready = static_cast<bool_t>(true);
							ifCondition->operand_kind_id = __latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id();
							ifCondition->type_ref_id = operatorRow->result_type_ref_id;
							ifCondition->local_source_row_id = leftLocalSourceRowId;
							ifCondition->provider_type_ref_id = providerTypeRefId;
							ifCondition->feature_id = operatorRow->feature_id;
							ifCondition->local_operation_id = localOperationId;
							ifCondition->value = rightValue;
							{
							auto __latency_local_0 = conditionNode->node_id;
							(void) body->binary_source_row_ids.append(__latency_local_0);
							}
							{
							auto __latency_local_1 = operatorRow->feature_id;
							(void) body->binary_feature_ids.append(__latency_local_1);
							}
							(void) body->binary_provider_type_ref_ids.append(providerTypeRefId);
							{
							auto __latency_local_2 = operatorRow->result_type_ref_id;
							(void) body->binary_result_type_ref_ids.append(__latency_local_2);
							}
						}
						else {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
					}
					ifSourceRowId = statementNode->node_id;
					ifConditionSourceRowId = conditionNode->node_id;
					ifBodyFirstSourceRowId = statement->body_first_node_id;
					ifBodyLastSourceRowId = statement->body_last_node_id;
					if (static_cast<bool>((cast<int_t<>>(statement->else_body_first_node_id) > static_cast<int_t<> >(0)))) {
						FrontendNodeRow elseBodyNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->else_body_first_node_id, counters);
						if (static_cast<bool>((php::not_identical(cast<int_t<>>(statement->else_body_first_node_id), cast<int_t<>>(statement->else_body_last_node_id)) || php::not_identical(cast<int_t<>>(elseBodyNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
						FrontendStatementPayloadRow elseBodyStatement = __latency_fn_frontend_model_tables_statement_by_id(model, elseBodyNode->payload_row_id, counters);
						ifElseBodyFirstSourceRowId = statement->else_body_first_node_id;
						ifElseBodyLastSourceRowId = statement->else_body_last_node_id;
						if (static_cast<bool>(((php::identical(cast<int_t<>>(bodyNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())) && php::identical(cast<int_t<>>(elseBodyNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id()))) && php::identical(cast<int_t<>>(statementNode->next_sibling_node_id), static_cast<int_t<> >(0))))) {
							FrontendNodeRow bodyValueNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyStatement->value_node_id, counters);
							FrontendNodeRow elseBodyValueNode = __latency_fn_frontend_model_tables_node_by_id(model, elseBodyStatement->value_node_id, counters);
							if (static_cast<bool>((((php::not_identical(cast<int_t<>>(bodyValueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(bodyValueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))) || php::not_identical(cast<int_t<>>(elseBodyValueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))) || php::not_identical(cast<int_t<>>(elseBodyValueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							FrontendLiteralPayloadRow bodyLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, bodyValueNode->payload_row_id, counters);
							FrontendLiteralPayloadRow elseBodyLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, elseBodyValueNode->payload_row_id, counters);
							if (static_cast<bool>(((((!__latency_fn_project_symbol_index_literal_status_ready(bodyLiteral->literal_status_id)) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(symbol->return_type_ref_id, bodyLiteral->type_ref_id))) || (!__latency_fn_project_symbol_index_literal_status_ready(elseBodyLiteral->literal_status_id))) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(symbol->return_type_ref_id, elseBodyLiteral->type_ref_id))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							thenLiteralNode = bodyValueNode;
							thenLiteral = bodyLiteral;
							fallbackLiteralNode = elseBodyValueNode;
							fallbackLiteral = elseBodyLiteral;
							returnSourceRowId = statementNode->node_id;
							returnValueSourceRowId = elseBodyValueNode->node_id;
							returnTypeRefId = elseBodyLiteral->type_ref_id;
							ifBranchOutcome->outcome_kind_id = __latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id();
						}
						else {
							if (static_cast<bool>(((php::identical(cast<int_t<>>(bodyNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())) && php::identical(cast<int_t<>>(elseBodyNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id()))) && (cast<int_t<>>(statementNode->next_sibling_node_id) > static_cast<int_t<> >(0))))) {
								int_t<> thenAssignmentIndex = required_cast<int_t<>>(php::count(body->assignment_source_row_ids));
								if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, bodyNode, bodyStatement, body)))) {
									plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
									return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
								}
								int_t<> elseAssignmentIndex = required_cast<int_t<>>(php::count(body->assignment_source_row_ids));
								if (static_cast<bool>((((((!__latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, elseBodyNode, elseBodyStatement, body)) || (thenAssignmentIndex >= php::count(body->assignment_target_local_source_row_ids))) || (elseAssignmentIndex >= php::count(body->assignment_target_local_source_row_ids))) || php::not_identical(cast<int_t<>>(body->assignment_target_local_source_row_ids[thenAssignmentIndex]), cast<int_t<>>(body->assignment_target_local_source_row_ids[elseAssignmentIndex]))) || php::not_identical(cast<int_t<>>(body->assignment_type_ref_ids[thenAssignmentIndex]), cast<int_t<>>(body->assignment_type_ref_ids[elseAssignmentIndex]))))) {
									plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
									return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
								}
								ifBranchOutcome->outcome_kind_id = __latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id();
							}
							else {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
						}
					}
					else {
						if (static_cast<bool>((php::identical(cast<int_t<>>(bodyNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())) && (cast<int_t<>>(statementNode->next_sibling_node_id) > static_cast<int_t<> >(0))))) {
							FrontendNodeRow bodyValueNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyStatement->value_node_id, counters);
							if (static_cast<bool>((php::not_identical(cast<int_t<>>(bodyValueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(bodyValueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							FrontendLiteralPayloadRow bodyLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, bodyValueNode->payload_row_id, counters);
							if (static_cast<bool>(((!__latency_fn_project_symbol_index_literal_status_ready(bodyLiteral->literal_status_id)) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(symbol->return_type_ref_id, bodyLiteral->type_ref_id))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							thenLiteralNode = bodyValueNode;
							thenLiteral = bodyLiteral;
							ifBranchOutcome->outcome_kind_id = __latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id();
						}
						else {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
					}
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())))) {
						if (static_cast<bool>((((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)) || (cast<int_t<>>(whileSourceRowId) > static_cast<int_t<> >(0))) || (cast<int_t<>>(forSourceRowId) > static_cast<int_t<> >(0))))) {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
						FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
						if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition(model, sourceText, counters, conditionNode, body->local_names, body->local_source_row_ids, body->local_type_ref_ids, whileConditionTypeRefId, whileConditionLocalSourceRowId, whileConditionProviderTypeRefId, whileConditionFeatureId, whileConditionLocalOperationId, whileConditionValue)))) {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
						whileBodySequence = __latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence(model, sourceText, counters, statement->body_first_node_id, statement->body_last_node_id, body);
						if (static_cast<bool>((!whileBodySequence->ready))) {
							plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
							return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
						}
						if (static_cast<bool>((cast<int_t<>>(whileBodySequence->terminator_kind_id) > static_cast<int_t<> >(0)))) {
							FrontendNodeRow transferNode = __latency_fn_frontend_model_tables_node_by_id(model, whileBodySequence->terminator_statement_row_id, counters);
							shared_p<ControlTransferTargetDecision> transferDecision = __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(model, transferNode, __latency_fn_control_flow_transfers_transfer_kind_from_statement(transferNode), statementNode->node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), counters);
							if (static_cast<bool>((!transferDecision->ready))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
						}
						whileSourceRowId = statementNode->node_id;
						whileConditionSourceRowId = conditionNode->node_id;
					}
					else {
						if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())))) {
							if (static_cast<bool>((((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)) || (cast<int_t<>>(whileSourceRowId) > static_cast<int_t<> >(0))) || (cast<int_t<>>(forSourceRowId) > static_cast<int_t<> >(0))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							FrontendNodeRow initNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
							FrontendNodeRow updateNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
							if (static_cast<bool>((((php::not_identical(cast<int_t<>>(initNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())) || php::identical(cast<int_t<>>(statement->body_first_node_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(updateNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))) || php::not_identical(cast<int_t<>>(updateNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id()))))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							FrontendStatementPayloadRow initStatement = __latency_fn_frontend_model_tables_statement_by_id(model, initNode->payload_row_id, counters);
							if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_local_declaration(model, sourceText, counters, initNode, initStatement, body)))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
							if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition(model, sourceText, counters, conditionNode, body->local_names, body->local_source_row_ids, body->local_type_ref_ids, forConditionTypeRefId, forConditionLocalSourceRowId, forConditionProviderTypeRefId, forConditionFeatureId, forConditionLocalOperationId, forConditionValue)))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							forBodySequence = __latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence(model, sourceText, counters, statement->body_first_node_id, statement->body_last_node_id, body);
							if (static_cast<bool>((!forBodySequence->ready))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							if (static_cast<bool>((cast<int_t<>>(forBodySequence->terminator_kind_id) > static_cast<int_t<> >(0)))) {
								FrontendNodeRow transferNode = __latency_fn_frontend_model_tables_node_by_id(model, forBodySequence->terminator_statement_row_id, counters);
								shared_p<ControlTransferTargetDecision> transferDecision = __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(model, transferNode, __latency_fn_control_flow_transfers_transfer_kind_from_statement(transferNode), statementNode->node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), counters);
								if (static_cast<bool>((!transferDecision->ready))) {
									plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
									return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
								}
							}
							FrontendStatementPayloadRow updateStatement = __latency_fn_frontend_model_tables_statement_by_id(model, updateNode->payload_row_id, counters);
							if (static_cast<bool>((!__latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, updateNode, updateStatement, body)))) {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
							forSourceRowId = statementNode->node_id;
							forConditionSourceRowId = conditionNode->node_id;
							forInitStatementRowId = statement->target_node_id;
							forUpdateStatementRowId = statement->value_node_id;
							forUpdateLastSourceRowId = updateStatement->value_node_id;
						}
						else {
							if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())))) {
								FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
								if (static_cast<bool>((((((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(returnSourceRowId), static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))) && php::identical(cast<int_t<>>(statementNode->next_sibling_node_id), static_cast<int_t<> >(0))))) {
									FrontendLiteralPayloadRow valueLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, valueNode->payload_row_id, counters);
									if (static_cast<bool>(((!__latency_fn_project_symbol_index_literal_status_ready(valueLiteral->literal_status_id)) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(symbol->return_type_ref_id, valueLiteral->type_ref_id))))) {
										plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
										return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
									}
									fallbackLiteralNode = valueNode;
									fallbackLiteral = valueLiteral;
									returnSourceRowId = statementNode->node_id;
									returnValueSourceRowId = valueNode->node_id;
									returnTypeRefId = valueLiteral->type_ref_id;
								}
								else {
									if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
										FrontendLiteralPayloadRow valueLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, valueNode->payload_row_id, counters);
										if (static_cast<bool>((((!__latency_fn_project_symbol_index_literal_status_ready(valueLiteral->literal_status_id)) || (!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(symbol->return_type_ref_id, valueLiteral->type_ref_id))) || php::not_identical(cast<int_t<>>(statementNode->next_sibling_node_id), static_cast<int_t<> >(0))))) {
											plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
											return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
										}
										returnLiteralNode = valueNode;
										returnLiteral = valueLiteral;
										returnSourceRowId = statementNode->node_id;
										returnValueSourceRowId = valueNode->node_id;
										returnTypeRefId = valueLiteral->type_ref_id;
									}
									else {
										if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
											string_t returnName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, valueNode));
											returnLocalSourceRowId = __latency_fn_local_body_lowering_local_source_row_id_for_name(body->local_names, body->local_source_row_ids, returnName);
											returnTypeRefId = __latency_fn_local_body_lowering_local_type_ref_id_for_name(body->local_names, body->local_type_ref_ids, returnName);
											if (static_cast<bool>((((php::identical(cast<int_t<>>(returnLocalSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(returnTypeRefId), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(returnTypeRefId), cast<int_t<>>(symbol->return_type_ref_id))) || php::not_identical(cast<int_t<>>(statementNode->next_sibling_node_id), static_cast<int_t<> >(0))))) {
												plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
												return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
											}
											returnSourceRowId = statementNode->node_id;
											returnValueSourceRowId = valueNode->node_id;
										}
										else {
											if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
												FrontendExpressionPayloadRow binary = __latency_fn_frontend_model_tables_expression_by_id(model, valueNode->payload_row_id, counters);
												FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->left_node_id, counters);
												FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->right_node_id, counters);
												string_t leftName = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, leftNode));
												int_t<std::uint32_t> leftLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_source_row_id_for_name(body->local_names, body->local_source_row_ids, leftName));
												int_t<std::uint32_t> providerTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_local_body_lowering_local_type_ref_id_for_name(body->local_names, body->local_type_ref_ids, leftName));
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
													rightSourceRowId = __latency_fn_local_body_lowering_local_source_row_id_for_name(body->local_names, body->local_source_row_ids, rightName);
													rightTypeRefId = __latency_fn_local_body_lowering_local_type_ref_id_for_name(body->local_names, body->local_type_ref_ids, rightName);
												}
												shared_p<SemanticOperatorLookupRow> parsedOperator = __latency_fn_semantic_operator_lookup_row_by_operator_id(binary->operator_id);
												shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(parsedOperator->feature_id, providerTypeRefId, rightTypeRefId);
												int_t<std::uint16_t> localOperationId = required_cast<int_t<std::uint16_t>>(operatorRow->local_immediate_operation_id);
												if (static_cast<bool>((((((php::identical(cast<int_t<>>(leftLocalSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(rightSourceRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(operatorRow->operator_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(symbol->return_type_ref_id))) || php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))) || php::not_identical(cast<int_t<>>(statementNode->next_sibling_node_id), static_cast<int_t<> >(0))))) {
													plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
													return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
												}
												returnSourceRowId = statementNode->node_id;
												returnValueSourceRowId = valueNode->node_id;
												returnTypeRefId = operatorRow->result_type_ref_id;
												returnBinarySourceRowId = valueNode->node_id;
												returnBinaryFeatureId = operatorRow->feature_id;
												returnBinaryProviderTypeRefId = cast<int_t<std::uint32_t>>(providerTypeRefId);
												returnBinaryResultTypeRefId = operatorRow->result_type_ref_id;
												returnBinaryLeftLocalSourceRowId = cast<int_t<std::uint32_t>>(leftLocalSourceRowId);
												returnBinaryRightSourceRowId = cast<int_t<std::uint32_t>>(rightSourceRowId);
												returnBinaryRightValue = cast<int_t<std::int32_t>>(rightValue);
												returnBinaryLocalOperationId = cast<int_t<std::uint16_t>>(localOperationId);
												{
												auto __latency_local_3 = valueNode->node_id;
												(void) body->binary_source_row_ids.append(__latency_local_3);
												}
												{
												auto __latency_local_4 = operatorRow->feature_id;
												(void) body->binary_feature_ids.append(__latency_local_4);
												}
												(void) body->binary_provider_type_ref_ids.append(providerTypeRefId);
												{
												auto __latency_local_5 = operatorRow->result_type_ref_id;
												(void) body->binary_result_type_ref_ids.append(__latency_local_5);
												}
											}
											else {
												plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
												return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
											}
										}
									}
								}
							}
							else {
								plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
								return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
							}
						}
					}
				}
			}
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(returnSourceRowId), static_cast<int_t<> >(0)) || php::identical(php::count(body->local_source_row_ids), static_cast<int_t<> >(0))))) {
		plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
		return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	}
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(0));
	shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_project_callable_contracts_new_artifact(static_cast<int_t<> >(0));
	ProjectDependencyGraph graph = __latency_fn_project_dependency_graph_from_symbols_references_and_contracts(symbols, references, contracts);
	shared_p<AnalysisContext> analysis = __latency_fn_analysis_context_new_context(static_cast<int_t<> >(1));
	AnalysisEntryContextRow entry = __latency_fn_analysis_context_append_entry(analysis, symbol, references, contracts, graph);
	TypeRefTable typeRefs = __latency_fn_type_refs_table_from_project_symbols(symbols);
	__latency_fn_scalar_body_backend_collection_append_type_refs_to_table(typeRefs, body);
	if (static_cast<bool>((cast<int_t<>>(whileConditionTypeRefId) > static_cast<int_t<> >(0)))) {
		__latency_fn_type_refs_add_type(typeRefs, whileConditionTypeRefId);
	}
	if (static_cast<bool>((cast<int_t<>>(forConditionTypeRefId) > static_cast<int_t<> >(0)))) {
		__latency_fn_type_refs_add_type(typeRefs, forConditionTypeRefId);
	}
	vector_t<int_t<std::uint32_t>> coverageAssignmentSourceRowIds = {};
	vector_t<int_t<std::uint32_t>> coverageAssignmentTypeRefIds = {};
	__latency_fn_scalar_body_backend_collection_append_coverage_assignment_sources(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds);
	shared_p<CapabilityConsumerPlan> consumerPlan = create<CapabilityConsumerPlan>();
	if (static_cast<bool>((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)))) {
		if (static_cast<bool>(((((!__latency_fn_scalar_body_statement_collection_if_branch_outcome_ready(ifBranchOutcome)) || (__latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return(ifBranchOutcome) && (php::identical(cast<int_t<>>(thenLiteralNode->node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(fallbackLiteralNode->node_id), static_cast<int_t<> >(0))))) || (!ifCondition->ready)) || php::identical(cast<int_t<>>(ifCondition->type_ref_id), static_cast<int_t<> >(0))))) {
			plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
			return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
		}
		consumerPlan = __latency_fn_semantic_body_capability_consumers_scalar_if_literal_return_plan(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds, ifSourceRowId, thenLiteralNode->node_id, fallbackLiteralNode->node_id, ifCondition->type_ref_id, returnTypeRefId);
		capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(typeRefs, consumerPlan);
	}
	else {
		if (static_cast<bool>((cast<int_t<>>(whileSourceRowId) > static_cast<int_t<> >(0)))) {
			if (static_cast<bool>(((php::identical(cast<int_t<>>(whileBodySequence->effect_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(whileBodySequence->terminator_kind_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(whileConditionTypeRefId), static_cast<int_t<> >(0))))) {
				plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
				return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
			}
			consumerPlan = __latency_fn_semantic_body_capability_consumers_scalar_while_return_plan(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds, whileSourceRowId, whileConditionSourceRowId, whileConditionFeatureId, whileConditionProviderTypeRefId, whileConditionTypeRefId, returnSourceRowId, returnTypeRefId);
			capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(typeRefs, consumerPlan);
		}
		else {
			if (static_cast<bool>((cast<int_t<>>(forSourceRowId) > static_cast<int_t<> >(0)))) {
				if (static_cast<bool>((((php::identical(cast<int_t<>>(forBodySequence->effect_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(forBodySequence->terminator_kind_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(forUpdateStatementRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(forConditionTypeRefId), static_cast<int_t<> >(0))))) {
					plan = __latency_fn_lowering_plan_new_plan(static_cast<int_t<> >(0), static_cast<int_t<> >(0), symbol->source_unit_id, symbol->symbol_id);
					return __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
				}
				consumerPlan = __latency_fn_semantic_body_capability_consumers_scalar_for_return_plan(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds, forSourceRowId, forConditionSourceRowId, forConditionFeatureId, forConditionProviderTypeRefId, forConditionTypeRefId, returnSourceRowId, returnTypeRefId);
				capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(typeRefs, consumerPlan);
			}
			else {
				if (static_cast<bool>((cast<int_t<>>(returnLiteralNode->node_id) > static_cast<int_t<> >(0)))) {
					consumerPlan = __latency_fn_semantic_body_capability_consumers_scalar_literal_return_plan(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds, returnLiteralNode->node_id, returnTypeRefId);
					capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(typeRefs, consumerPlan);
				}
				else {
					consumerPlan = __latency_fn_semantic_body_capability_consumers_scalar_local_return_plan(body, coverageAssignmentSourceRowIds, coverageAssignmentTypeRefIds, returnSourceRowId, returnTypeRefId);
					capabilityCoverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(typeRefs, consumerPlan);
				}
			}
		}
	}
	int_t<> requestCapacity = required_cast<int_t<>>(((php::count(body->local_source_row_ids) + php::count(body->assignment_source_row_ids)) + static_cast<int_t<> >(1)));
	int_t<> controlFlowCapacity = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)))) {
		requestCapacity = (requestCapacity + static_cast<int_t<> >(2));
		controlFlowCapacity = static_cast<int_t<> >(1);
	}
	else {
		if (static_cast<bool>((cast<int_t<>>(whileSourceRowId) > static_cast<int_t<> >(0)))) {
			requestCapacity = (requestCapacity + static_cast<int_t<> >(1));
			controlFlowCapacity = static_cast<int_t<> >(1);
		}
		else {
			if (static_cast<bool>((cast<int_t<>>(forSourceRowId) > static_cast<int_t<> >(0)))) {
				requestCapacity = (requestCapacity + static_cast<int_t<> >(1));
				controlFlowCapacity = static_cast<int_t<> >(1);
			}
			else {
				if (static_cast<bool>((cast<int_t<>>(returnBinarySourceRowId) > static_cast<int_t<> >(0)))) {
					requestCapacity = (requestCapacity + static_cast<int_t<> >(1));
				}
			}
		}
	}
	backendRequests = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(requestCapacity, php::count(body->binary_source_row_ids), requestCapacity, static_cast<int_t<> >(0), controlFlowCapacity);
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((localIndex < php::count(body->local_source_row_ids)) && (localIndex < php::count(body->local_type_ref_ids))))) {
		__latency_fn_local_body_lowering_append_local_backend_request(backendRequests, capabilityCoverage, __latency_fn_type_capability_readiness_feature_local_storage_id(), body->local_source_row_ids[localIndex], body->local_source_row_ids[localIndex], __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), __latency_fn_backend_preflight_requests_local_operation_alloca_id(), symbol);
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> binaryIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((cast<int_t<>>(ifSourceRowId) > static_cast<int_t<> >(0)))) {
		__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(ifSourceRowId) - static_cast<int_t<> >(1))), binaryIndex);
		int_t<std::uint32_t> controlThenLastSourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(ifBodyLastSourceRowId));
		int_t<std::uint32_t> controlElseLastSourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(ifElseBodyLastSourceRowId));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return(ifBranchOutcome)))) {
			controlThenLastSourceRowId = thenLiteralNode->node_id;
			controlElseLastSourceRowId = fallbackLiteralNode->node_id;
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_scalar_body_statement_collection_condition_operand_is_local_immediate(ifCondition)))) {
			__latency_fn_local_body_lowering_append_if_local_immediate_condition_backend_request(backendRequests, capabilityCoverage, ifSourceRowId, ifConditionSourceRowId, ifBodyFirstSourceRowId, controlThenLastSourceRowId, ifElseBodyFirstSourceRowId, controlElseLastSourceRowId, ifCondition->local_source_row_id, ifCondition->provider_type_ref_id, ifCondition->type_ref_id, ifCondition->value, ifCondition->local_operation_id, symbol);
		}
		else {
			__latency_fn_local_body_lowering_append_if_condition_backend_request(backendRequests, capabilityCoverage, ifSourceRowId, ifConditionSourceRowId, ifBodyFirstSourceRowId, controlThenLastSourceRowId, ifElseBodyFirstSourceRowId, controlElseLastSourceRowId, ifCondition->type_ref_id, ifCondition->value, ifCondition->local_source_row_id, ifCondition->operand_kind_id, symbol);
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return(ifBranchOutcome)))) {
			__latency_fn_local_body_lowering_append_literal_scalar_backend_request(backendRequests, capabilityCoverage, thenLiteralNode, thenLiteral, symbol);
			__latency_fn_local_body_lowering_append_literal_scalar_backend_request(backendRequests, capabilityCoverage, fallbackLiteralNode, fallbackLiteral, symbol);
		}
		else {
			__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, cast<int_t<std::uint32_t>>(ifBodyFirstSourceRowId), cast<int_t<std::uint32_t>>(ifBodyLastSourceRowId), binaryIndex);
			__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, cast<int_t<std::uint32_t>>(ifElseBodyFirstSourceRowId), cast<int_t<std::uint32_t>>(ifElseBodyLastSourceRowId), binaryIndex);
			if (static_cast<bool>((cast<int_t<>>(returnBinarySourceRowId) > static_cast<int_t<> >(0)))) {
				__latency_fn_local_body_lowering_append_local_backend_request(backendRequests, capabilityCoverage, returnBinaryFeatureId, returnBinarySourceRowId, returnBinaryLeftLocalSourceRowId, returnBinaryRightSourceRowId, returnBinaryRightValue, returnBinaryLocalOperationId, symbol);
			}
			else {
				if (static_cast<bool>((cast<int_t<>>(returnLiteralNode->node_id) > static_cast<int_t<> >(0)))) {
					__latency_fn_local_body_lowering_append_literal_scalar_backend_request(backendRequests, capabilityCoverage, returnLiteralNode, returnLiteral, symbol);
				}
				else {
					__latency_fn_local_body_lowering_append_local_load_return_backend_request(backendRequests, capabilityCoverage, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, symbol);
				}
			}
		}
	}
	else {
		if (static_cast<bool>((cast<int_t<>>(whileSourceRowId) > static_cast<int_t<> >(0)))) {
			__latency_fn_scalar_loop_backend_requests_append_while_statement_sequence_requests(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, whileSourceRowId, whileConditionSourceRowId, whileBodySequence, whileConditionLocalSourceRowId, whileConditionProviderTypeRefId, whileConditionTypeRefId, whileConditionValue, whileConditionLocalOperationId, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, binaryIndex);
		}
		else {
			if (static_cast<bool>((cast<int_t<>>(forSourceRowId) > static_cast<int_t<> >(0)))) {
				__latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, forSourceRowId, forConditionSourceRowId, forBodySequence, forInitStatementRowId, forUpdateStatementRowId, forUpdateLastSourceRowId, forConditionLocalSourceRowId, forConditionProviderTypeRefId, forConditionTypeRefId, forConditionValue, forConditionLocalOperationId, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, binaryIndex);
			}
			else {
				if (static_cast<bool>((cast<int_t<>>(returnBinarySourceRowId) > static_cast<int_t<> >(0)))) {
					__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), binaryIndex);
					__latency_fn_local_body_lowering_append_local_backend_request(backendRequests, capabilityCoverage, returnBinaryFeatureId, returnBinarySourceRowId, returnBinaryLeftLocalSourceRowId, returnBinaryRightSourceRowId, returnBinaryRightValue, returnBinaryLocalOperationId, symbol);
				}
				else {
					if (static_cast<bool>((cast<int_t<>>(returnLiteralNode->node_id) > static_cast<int_t<> >(0)))) {
						__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), binaryIndex);
						__latency_fn_local_body_lowering_append_literal_scalar_backend_request(backendRequests, capabilityCoverage, returnLiteralNode, returnLiteral, symbol);
					}
					else {
						__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, body->assignment_source_row_ids, body->assignment_type_ref_ids, body->assignment_target_local_source_row_ids, body->assignment_value_source_row_ids, body->assignment_values, body->assignment_value_texts, body->assignment_local_operation_ids, body->binary_source_row_ids, body->binary_feature_ids, body->binary_left_local_source_row_ids, body->binary_right_source_row_ids, body->binary_right_values, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), binaryIndex);
						__latency_fn_local_body_lowering_append_local_load_return_backend_request(backendRequests, capabilityCoverage, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, symbol);
					}
				}
			}
		}
	}
	plan = __latency_fn_lowering_plan_from_entry_and_backend_requests(entry, backendRequests);
	return __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(plan, backendRequests);
}

}
