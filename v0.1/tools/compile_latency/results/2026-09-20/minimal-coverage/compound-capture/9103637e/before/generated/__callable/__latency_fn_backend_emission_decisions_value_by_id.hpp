#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionValueRow;
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_by_id(BackendEmissionDecisionArtifact& artifact, int_t<std::uint32_t> valueId);
}
