#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ScalarLoopBodyStatementSequence.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests__norm_append_for_statement_sequence_requests__binaryIndex.hpp"
#include "__callable/__latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests__exec.hpp"
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
class ScalarLoopBodyStatementSequence;
	template <typename T_binaryIndex>
	void __latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> forConditionSourceRowId, shared_p<ScalarLoopBodyStatementSequence> bodySequence, int_t<std::uint32_t> forInitStatementRowId, int_t<std::uint32_t> forUpdateStatementRowId, int_t<std::uint32_t> forUpdateLastSourceRowId, int_t<std::uint32_t> forConditionLocalSourceRowId, int_t<std::uint32_t> forConditionProviderTypeRefId, int_t<std::uint32_t> forConditionTypeRefId, int_t<std::int32_t> forConditionValue, int_t<std::uint16_t> forConditionLocalOperationId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnLocalSourceRowId, int_t<std::uint32_t> returnValueSourceRowId, int_t<std::uint32_t> returnTypeRefId, T_binaryIndex&& _binaryIndex) {
	int_t<>& binaryIndex = __latency_fn_scalar_loop_backend_requests__norm_append_for_statement_sequence_requests__binaryIndex(std::forward<T_binaryIndex>(_binaryIndex));
		__latency_fn_scalar_loop_backend_requests_append_for_statement_sequence_requests__exec(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, forSourceRowId, forConditionSourceRowId, bodySequence, forInitStatementRowId, forUpdateStatementRowId, forUpdateLastSourceRowId, forConditionLocalSourceRowId, forConditionProviderTypeRefId, forConditionTypeRefId, forConditionValue, forConditionLocalOperationId, returnSourceRowId, returnLocalSourceRowId, returnValueSourceRowId, returnTypeRefId, binaryIndex);
	}

}
