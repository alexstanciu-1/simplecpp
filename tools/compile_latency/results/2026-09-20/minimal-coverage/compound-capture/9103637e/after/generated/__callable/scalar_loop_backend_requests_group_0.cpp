#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ScalarLoopBodyStatementSequence.hpp"
#include "__types/scalar_loop_backend_requests.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_load_return_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_while_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests_append_while_statement_sequence_requests__exec.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_break.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_for_local_immediate_condition_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_load_return_backend_request.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests__exec.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_scalar_loop_backend_requests[]; }
namespace scpp {
bool_t scalar_loop_backend_requests::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == scalar_loop_backend_requests::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_scalar_loop_backend_requests[]; }
namespace scpp {
void __latency_fn_scalar_loop_backend_requests_append_while_statement_sequence_requests__exec(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> whileSourceRowId, int_t<std::uint32_t> whileConditionSourceRowId, shared_p<ScalarLoopBodyStatementSequence> bodySequence, int_t<std::uint32_t> whileConditionLocalSourceRowId, int_t<std::uint32_t> whileConditionProviderTypeRefId, int_t<std::uint32_t> whileConditionTypeRefId, int_t<std::int32_t> whileConditionValue, int_t<std::uint16_t> whileConditionLocalOperationId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnLocalSourceRowId, int_t<std::uint32_t> returnValueSourceRowId, int_t<std::uint32_t> returnTypeRefId, int_t<>& binaryIndex) {
	__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(whileSourceRowId) - static_cast<int_t<> >(1))), binaryIndex);
	__latency_fn_local_body_lowering_append_while_local_immediate_condition_backend_request(backendRequests, capabilityCoverage, whileSourceRowId, whileConditionSourceRowId, bodySequence->first_statement_row_id, bodySequence->last_effect_source_row_id, bodySequence->terminator_statement_row_id, bodySequence->terminator_kind_id, whileConditionLocalSourceRowId, whileConditionProviderTypeRefId, whileConditionTypeRefId, whileConditionValue, whileConditionLocalOperationId, symbol);
	if (static_cast<bool>((cast<int_t<>>(bodySequence->effect_count) > static_cast<int_t<> >(0)))) {
		__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, bodySequence->first_statement_row_id, bodySequence->last_statement_row_id, binaryIndex);
	}
	__latency_fn_local_body_lowering_append_local_load_return_backend_request(backendRequests, capabilityCoverage, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, symbol);
}

}

namespace scpp { extern const int __latency_lines_scalar_loop_backend_requests[]; }
namespace scpp {
void __latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests__exec(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> forConditionSourceRowId, shared_p<ScalarLoopBodyStatementSequence> bodySequence, int_t<std::uint32_t> forInitStatementRowId, int_t<std::uint32_t> forUpdateStatementRowId, int_t<std::uint32_t> forUpdateLastSourceRowId, int_t<std::uint32_t> forConditionLocalSourceRowId, int_t<std::uint32_t> forConditionProviderTypeRefId, int_t<std::uint32_t> forConditionTypeRefId, int_t<std::int32_t> forConditionValue, int_t<std::uint16_t> forConditionLocalOperationId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnLocalSourceRowId, int_t<std::uint32_t> returnValueSourceRowId, int_t<std::uint32_t> returnTypeRefId, int_t<>& binaryIndex) {
	__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(forInitStatementRowId) - static_cast<int_t<> >(1))), binaryIndex);
	__latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, forInitStatementRowId, binaryIndex);
	__latency_fn_local_body_lowering_append_for_local_immediate_condition_backend_request(backendRequests, capabilityCoverage, forSourceRowId, forConditionSourceRowId, bodySequence->first_statement_row_id, bodySequence->last_effect_source_row_id, bodySequence->terminator_statement_row_id, bodySequence->terminator_kind_id, forUpdateLastSourceRowId, forConditionLocalSourceRowId, forConditionProviderTypeRefId, forConditionTypeRefId, forConditionValue, forConditionLocalOperationId, symbol);
	if (static_cast<bool>((cast<int_t<>>(bodySequence->effect_count) > static_cast<int_t<> >(0)))) {
		__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, bodySequence->first_statement_row_id, bodySequence->last_statement_row_id, binaryIndex);
	}
	if (static_cast<bool>((!__latency_fn_backend_preflight_requests_control_flow_terminator_is_break(bodySequence->terminator_kind_id)))) {
		__latency_fn_compiler_entry_backend_append_assignment_backend_requests_in_source_range(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, forUpdateStatementRowId, forUpdateStatementRowId, binaryIndex);
	}
	__latency_fn_local_body_lowering_append_local_load_return_backend_request(backendRequests, capabilityCoverage, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, symbol);
}

}
