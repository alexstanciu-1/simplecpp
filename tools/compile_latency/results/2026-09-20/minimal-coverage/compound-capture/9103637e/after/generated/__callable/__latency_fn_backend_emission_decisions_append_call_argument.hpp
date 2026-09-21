#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendCallArgumentRow;
struct BackendEmissionDecisionArtifact;
void __latency_fn_backend_emission_decisions_append_call_argument(BackendEmissionDecisionArtifact& artifact, BackendCallArgumentRow row);
}
