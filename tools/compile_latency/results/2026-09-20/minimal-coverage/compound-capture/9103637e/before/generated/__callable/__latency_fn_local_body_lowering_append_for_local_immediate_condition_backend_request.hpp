#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_for_local_immediate_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> bodyTerminatorSourceRowId, int_t<std::uint16_t> bodyTerminatorKindId, int_t<std::uint32_t> updateSourceRowId, int_t<std::uint32_t> conditionLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol);
}
