#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_if_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> bodyFirstSourceRowId, int_t<std::uint32_t> bodyLastSourceRowId, int_t<std::uint32_t> elseBodyFirstSourceRowId, int_t<std::uint32_t> elseBodyLastSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::int32_t> conditionValue, int_t<std::uint32_t> conditionLocalSourceRowId, bool_t conditionIsLocal, ProjectSymbolIndexRow entrySymbol);
}
