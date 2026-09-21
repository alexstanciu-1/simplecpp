#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendCallArgumentRow;
class BackendRequestAuthorizationArtifact;
vector_t<BackendCallArgumentRow> __latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId);
}
