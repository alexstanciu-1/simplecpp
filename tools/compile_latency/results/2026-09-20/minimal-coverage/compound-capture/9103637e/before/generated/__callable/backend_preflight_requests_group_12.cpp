#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendLocalImmediateTextOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_immediate_text_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_append.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_operator.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_plus.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_operator.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_binary_operator.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_ternary_select.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_echo_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_echo_string_literal.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_from_operation_source_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendStringLiteralOperandRow> __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::string_literal_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[160]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->string_literal_operand_count))))) {
		shared_p<BackendStringLiteralOperandRow> row = artifact->string_literal_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->string_literal_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	shared_p<BackendStringLiteralOperandRow> empty = create<BackendStringLiteralOperandRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendLocalImmediateTextOperandRow> __latency_fn_backend_preflight_requests_local_immediate_text_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_immediate_text_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[161]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->local_immediate_text_operand_count))))) {
		shared_p<BackendLocalImmediateTextOperandRow> row = artifact->local_immediate_text_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->local_immediate_text_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	shared_p<BackendLocalImmediateTextOperandRow> empty = create<BackendLocalImmediateTextOperandRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
void __latency_fn_backend_preflight_requests_append_backend_request(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestAuthorizationRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::append_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[162]);
	__latency_fn_backend_request_row_lists_append(artifact->request_rows, row);
	artifact->request_count = __latency_fn_backend_request_row_lists_row_count(artifact->request_rows);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_operation(OperationReadiness operation, StorageLifetimeRequestRow storage, ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_operation", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[163]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), operation, storage, contract);
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_literal(OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_literal", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[164]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_literal(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), operation, storage, literal, ownerSymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_plus(OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow binaryNode, FrontendNodeRow leftNode, FrontendNodeRow rightNode, FrontendLiteralPayloadRow leftLiteral, FrontendLiteralPayloadRow rightLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_binary_plus", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[165]);
	return __latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_operator(operation, storage, binaryNode, leftNode, rightNode, leftLiteral, rightLiteral, ownerSymbol);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_binary_operator(OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow binaryNode, FrontendNodeRow leftNode, FrontendNodeRow rightNode, FrontendLiteralPayloadRow leftLiteral, FrontendLiteralPayloadRow rightLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_binary_operator", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[166]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_binary_operator(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), operation, storage, binaryNode, leftLiteral, ownerSymbol);
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	__latency_fn_backend_preflight_requests_append_binary_operand(artifact, __latency_fn_backend_preflight_requests_binary_operand_row(row->request_id, leftNode, rightNode, leftLiteral, rightLiteral));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_backend_preflight_requests_backend_request_artifact_from_ternary_select(OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow ternaryNode, FrontendLiteralPayloadRow conditionLiteral, FrontendNodeRow thenNode, FrontendNodeRow elseNode, FrontendLiteralPayloadRow thenLiteral, FrontendLiteralPayloadRow elseLiteral, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_artifact_from_ternary_select", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[167]);
	shared_p<BackendRequestAuthorizationArtifact> artifact = __latency_fn_backend_preflight_requests_new_backend_request_artifact(static_cast<int_t<> >(1));
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), operation, storage, ternaryNode->node_id, conditionLiteral->numeric_payload, ownerSymbol);
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	__latency_fn_backend_preflight_requests_append_backend_request(artifact, row);
	__latency_fn_backend_preflight_requests_append_binary_operand(artifact, __latency_fn_backend_preflight_requests_binary_operand_row(row->request_id, thenNode, elseNode, thenLiteral, elseLiteral));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_echo_literal(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow echoNode, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_echo_literal", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[168]);
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(cast<int_t<std::uint32_t>>(requestId), operation, storage, echoNode->node_id, literal->numeric_payload, ownerSymbol);
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_echo_string_literal(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow echoNode, ProjectSymbolIndexRow ownerSymbol) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_from_echo_string_literal", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[169]);
	BackendRequestAuthorizationRow row = __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(cast<int_t<std::uint32_t>>(requestId), operation, storage, echoNode->node_id, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), ownerSymbol);
	row->source_reference_id = __latency_fn_structure_row_ids_none_id();
	row->target_symbol_id = __latency_fn_structure_row_ids_none_id();
	return row;
}

}
