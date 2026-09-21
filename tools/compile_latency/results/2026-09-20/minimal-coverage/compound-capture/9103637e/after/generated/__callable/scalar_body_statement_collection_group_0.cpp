#include <scpp/lang/php.hpp>
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__types/ScalarConditionOperand.hpp"
#include "__types/ScalarIfBranchOutcome.hpp"
#include "__types/scalar_body_statement_collection.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_ready.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_is_local.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_is_local_immediate.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal.hpp"
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
#include "__callable/__latency_fn_scalar_body_backend_collection_append_literal_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_typed_local_source_if_missing.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_statement_collection_append_local_declaration.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t scalar_body_statement_collection::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == scalar_body_statement_collection::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_if_branch_outcome_none_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::if_branch_outcome_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[0]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::if_branch_outcome_assignment_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::if_branch_outcome_return_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_if_branch_outcome_ready(shared_p<ScalarIfBranchOutcome> outcome) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::if_branch_outcome_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[3]);
	return (php::identical(cast<int_t<>>(outcome->outcome_kind_id), cast<int_t<>>(__latency_fn_scalar_body_statement_collection_if_branch_outcome_assignment_id())) || php::identical(cast<int_t<>>(outcome->outcome_kind_id), cast<int_t<>>(__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id())));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_if_branch_outcome_is_return(shared_p<ScalarIfBranchOutcome> outcome) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::if_branch_outcome_is_return", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[4]);
	return bool_t(php::identical(cast<int_t<>>(outcome->outcome_kind_id), cast<int_t<>>(__latency_fn_scalar_body_statement_collection_if_branch_outcome_return_id())));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_condition_operand_none_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::condition_operand_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[5]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_condition_operand_local_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::condition_operand_local_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::condition_operand_local_immediate_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_condition_operand_is_local(shared_p<ScalarConditionOperand> operand) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::condition_operand_is_local", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[8]);
	return bool_t(php::identical(cast<int_t<>>(operand->operand_kind_id), cast<int_t<>>(__latency_fn_scalar_body_statement_collection_condition_operand_local_id())));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_condition_operand_is_local_immediate(shared_p<ScalarConditionOperand> operand) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::condition_operand_is_local_immediate", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[9]);
	return bool_t(php::identical(cast<int_t<>>(operand->operand_kind_id), cast<int_t<>>(__latency_fn_scalar_body_statement_collection_condition_operand_local_immediate_id())));
}

}

namespace scpp { extern const int __latency_lines_scalar_body_statement_collection[]; }
namespace scpp {
bool_t __latency_fn_scalar_body_statement_collection_append_local_declaration(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, shared_p<ScalarBodyBackendCollection>& body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_statement_collection::append_local_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_statement_collection.phs", __latency_lines_scalar_body_statement_collection[10]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow targetNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || (php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id())) && php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id())))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	string_t name = required_cast<string_t>(__latency_fn_local_body_lowering_variable_name_text(model, sourceText, targetNode));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_builder_expression_type_ref_from_node(model, targetNode->node_id, counters));
	if (static_cast<bool>(((php::identical(name, string_t("")) || php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0))) || (!__latency_fn_local_body_lowering_type_ref_uses_inline_scalar_storage(typeRefId))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	__latency_fn_scalar_body_backend_collection_append_typed_local_source_if_missing(body, name, statementNode->node_id, typeRefId);
	int_t<std::uint32_t> targetLocalSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(body, name));
	if (static_cast<bool>(php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id())))) {
		FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, valueNode->payload_row_id, counters);
		if (static_cast<bool>(((!__latency_fn_type_refs_scalar_value_type_compatible_without_conversion(typeRefId, literal->type_ref_id)) || (!__latency_fn_project_symbol_index_literal_status_ready(literal->literal_status_id))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		string_t valueText = required_cast<string_t>(string_t(""));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_model_builder_numeric_type_ref_accepts_decimal_literal(typeRefId)))) {
			valueText = __latency_fn_local_body_lowering_source_text_for_node(model, sourceText, valueNode);
		}
		__latency_fn_scalar_body_backend_collection_append_literal_assignment(body, statementNode->node_id, typeRefId, targetLocalSourceRowId, valueNode->node_id, literal->numeric_payload, valueText, __latency_fn_backend_preflight_requests_local_operation_store_id());
		return bool_t(static_cast<bool_t>(true));
	}
	FrontendStatementPayloadRow syntheticAssignment = FrontendStatementPayloadRow{.payload_id = statement->payload_id, .statement_kind_id = __latency_fn_frontend_model_tables_row_kind_statement_assignment_id(), .target_node_id = statement->target_node_id, .value_node_id = statement->value_node_id, .condition_node_id = __latency_fn_structure_row_ids_none_id(), .body_first_node_id = __latency_fn_structure_row_ids_none_id(), .body_last_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_first_node_id = __latency_fn_structure_row_ids_none_id(), .else_body_last_node_id = __latency_fn_structure_row_ids_none_id(), .source_range_id = statement->source_range_id, .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_scalar_body_statement_collection_append_assignment(model, sourceText, counters, statementNode, syntheticAssignment, body);
}

}
