#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionDecisionRow;
void __latency_fn_backend_emission_decisions_append_decision(BackendEmissionDecisionArtifact& artifact, BackendEmissionDecisionRow row);
}
