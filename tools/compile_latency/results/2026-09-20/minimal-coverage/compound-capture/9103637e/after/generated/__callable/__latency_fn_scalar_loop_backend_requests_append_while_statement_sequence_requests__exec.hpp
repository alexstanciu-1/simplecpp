#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
class ScalarLoopBodyStatementSequence;
void __latency_fn_scalar_loop_backend_requests_append_while_statement_sequence_requests__exec(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> whileSourceRowId, int_t<std::uint32_t> whileConditionSourceRowId, shared_p<ScalarLoopBodyStatementSequence> bodySequence, int_t<std::uint32_t> whileConditionLocalSourceRowId, int_t<std::uint32_t> whileConditionProviderTypeRefId, int_t<std::uint32_t> whileConditionTypeRefId, int_t<std::int32_t> whileConditionValue, int_t<std::uint16_t> whileConditionLocalOperationId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnLocalSourceRowId, int_t<std::uint32_t> returnValueSourceRowId, int_t<std::uint32_t> returnTypeRefId, int_t<>& binaryIndex);
}
