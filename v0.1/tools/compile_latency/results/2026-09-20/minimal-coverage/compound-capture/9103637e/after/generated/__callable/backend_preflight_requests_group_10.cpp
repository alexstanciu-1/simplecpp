#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalImmediateTextOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_row_from_actual.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_literal_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_set_control_flow_else_body.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_call_argument.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_string_literal_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_immediate_text_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendCallArgumentRow __latency_fn_backend_preflight_requests_call_argument_row_from_actual(int_t<std::uint32_t> ownerRowId, ProjectReferenceActualArgumentRow argument) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_argument_row_from_actual", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[142]);
	BackendCallArgumentRow row = __latency_fn_backend_preflight_requests_call_argument_row(cast<int_t<std::uint32_t>>(ownerRowId), argument->argument_source_row_id, argument->type_ref_id, argument->numeric_payload, argument->position);
	row->flags = argument->flags;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> controlOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[143]);
	BackendControlFlowOperandRow row = BackendControlFlowOperandRow{};
	row->owner_row_id = ownerRowId;
	row->condition_source_row_id = conditionSourceRowId;
	row->body_first_source_row_id = bodyFirstSourceRowId;
	row->condition_type_ref_id = conditionTypeRefId;
	row->condition_value = conditionValue;
	row->condition_operand_kind_id = __latency_fn_backend_preflight_requests_control_condition_operand_literal_id();
	row->control_operation_id = controlOperationId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_local_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint16_t> controlOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_local_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[144]);
	BackendControlFlowOperandRow row = __latency_fn_backend_preflight_requests_control_flow_operand_row(cast<int_t<std::uint32_t>>(ownerRowId), cast<int_t<std::uint32_t>>(conditionSourceRowId), cast<int_t<std::uint32_t>>(bodyFirstSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId), __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), cast<int_t<std::uint16_t>>(controlOperationId));
	row->condition_operand_kind_id = __latency_fn_backend_preflight_requests_control_condition_operand_local_id();
	row->condition_local_source_row_id = conditionLocalSourceRowId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_local_binary_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> conditionLeftLocalSourceRowId, int_t<std::uint32_t> conditionRightLocalSourceRowId, int_t<std::uint32_t> conditionLhsTypeRefId, int_t<std::uint16_t> conditionLocalOperationId, int_t<std::uint16_t> controlOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_local_binary_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[145]);
	BackendControlFlowOperandRow row = __latency_fn_backend_preflight_requests_control_flow_operand_row(cast<int_t<std::uint32_t>>(ownerRowId), cast<int_t<std::uint32_t>>(conditionSourceRowId), cast<int_t<std::uint32_t>>(bodyFirstSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId), __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), cast<int_t<std::uint16_t>>(controlOperationId));
	row->condition_operand_kind_id = __latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id();
	row->condition_left_local_source_row_id = conditionLeftLocalSourceRowId;
	row->condition_right_local_source_row_id = conditionRightLocalSourceRowId;
	row->condition_lhs_type_ref_id = conditionLhsTypeRefId;
	row->condition_local_operation_id = conditionLocalOperationId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_local_immediate_operand_row(int_t<std::uint32_t> ownerRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint32_t> conditionLhsTypeRefId, int_t<std::uint16_t> conditionLocalOperationId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> controlOperationId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_local_immediate_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[146]);
	BackendControlFlowOperandRow row = __latency_fn_backend_preflight_requests_control_flow_operand_row(cast<int_t<std::uint32_t>>(ownerRowId), cast<int_t<std::uint32_t>>(conditionSourceRowId), cast<int_t<std::uint32_t>>(bodyFirstSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId), cast<int_t<std::int32_t>>(conditionValue), cast<int_t<std::uint16_t>>(controlOperationId));
	row->condition_operand_kind_id = __latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id();
	row->condition_local_source_row_id = conditionLocalSourceRowId;
	row->condition_lhs_type_ref_id = conditionLhsTypeRefId;
	row->condition_local_operation_id = conditionLocalOperationId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_set_control_flow_else_body(BackendControlFlowOperandRow& row, int_t<std::uint32_t> elseBodyFirstSourceRowId, int_t<std::uint32_t> elseBodyLastSourceRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::set_control_flow_else_body", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[147]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(elseBodyFirstSourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(elseBodyLastSourceRowId), static_cast<int_t<> >(0))))) {
		return;
	}
	row->else_body_first_source_row_id = elseBodyFirstSourceRowId;
	row->else_body_last_source_row_id = elseBodyLastSourceRowId;
	row->control_operation_id = __latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendStringLiteralOperandRow> __latency_fn_backend_preflight_requests_string_literal_operand_row(int_t<std::uint32_t> ownerRowId, FrontendNodeRow literalNode, FrontendLiteralPayloadRow literal, const string_t& literalText) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::string_literal_operand_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[148]);
	shared_p<BackendStringLiteralOperandRow> row = create<BackendStringLiteralOperandRow>();
	row->owner_row_id = ownerRowId;
	row->literal_source_row_id = literalNode->node_id;
	row->literal_source_range_id = literal->source_range_id;
	row->byte_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(literalText));
	row->literal_text = literalText;
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_binary_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_binary_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[149]);
	(void) artifact->binary_operands.append(row);
	artifact->binary_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->binary_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_local_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendLocalOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_local_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[150]);
	(void) artifact->local_operands.append(row);
	artifact->local_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->local_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_call_argument(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendCallArgumentRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_call_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[151]);
	(void) artifact->call_arguments.append(row);
	artifact->call_argument_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->call_arguments));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_control_flow_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendControlFlowOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_control_flow_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[152]);
	(void) artifact->control_flow_operands.append(row);
	artifact->control_flow_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->control_flow_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_string_literal_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, shared_p<BackendStringLiteralOperandRow> row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_string_literal_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[153]);
	(void) artifact->string_literal_operands.append(row);
	artifact->string_literal_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->string_literal_operands));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_local_immediate_text_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, shared_p<BackendLocalImmediateTextOperandRow> row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_local_immediate_text_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[154]);
	(void) artifact->local_immediate_text_operands.append(row);
	artifact->local_immediate_text_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->local_immediate_text_operands));
}

}
