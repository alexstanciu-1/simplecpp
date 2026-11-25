#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionValueRow;
class BackendRequestAuthorizationArtifact;
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(BackendEmissionDecisionArtifact& artifact, shared_p<BackendRequestAuthorizationArtifact>& requests, int_t<std::uint32_t> valueId);
}
