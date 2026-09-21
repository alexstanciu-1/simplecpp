#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct BackendRequestListRef;
BackendRequestListRef __latency_fn_backend_preflight_requests_request_list_ref(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> ownerSymbolId);
}
