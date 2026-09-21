#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_compiler_entry_backend__norm_append_assignment_backend_request_by_source_row_id__binaryIndex.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id__exec.hpp"
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
	template <typename T_binaryIndex>
	void __latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, ProjectSymbolIndexRow symbol, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentTargetLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentValueSourceRowIds, const vector_t<int_t<std::int32_t>>& assignmentValues, const vector_t<string_t>& assignmentValueTexts, const vector_t<int_t<std::uint16_t>>& assignmentLocalOperationIds, const vector_t<int_t<std::uint32_t>>& binarySourceRowIds, const vector_t<int_t<std::uint16_t>>& binaryFeatureIds, const vector_t<int_t<std::uint32_t>>& binaryLeftLocalSourceRowIds, const vector_t<int_t<std::uint32_t>>& binaryRightSourceRowIds, const vector_t<int_t<std::int32_t>>& binaryRightValues, int_t<std::uint32_t> sourceRowId, T_binaryIndex&& _binaryIndex) {
	int_t<>& binaryIndex = __latency_fn_compiler_entry_backend__norm_append_assignment_backend_request_by_source_row_id__binaryIndex(std::forward<T_binaryIndex>(_binaryIndex));
		__latency_fn_compiler_entry_backend_append_assignment_backend_request_by_source_row_id__exec(backendRequests, capabilityCoverage, symbol, assignmentSourceRowIds, assignmentTypeRefIds, assignmentTargetLocalSourceRowIds, assignmentValueSourceRowIds, assignmentValues, assignmentValueTexts, assignmentLocalOperationIds, binarySourceRowIds, binaryFeatureIds, binaryLeftLocalSourceRowIds, binaryRightSourceRowIds, binaryRightValues, sourceRowId, binaryIndex);
	}

}
