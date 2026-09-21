#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionValueRow;
void __latency_fn_backend_emission_decisions_append_value(BackendEmissionDecisionArtifact& artifact, BackendEmissionValueRow row);
}
