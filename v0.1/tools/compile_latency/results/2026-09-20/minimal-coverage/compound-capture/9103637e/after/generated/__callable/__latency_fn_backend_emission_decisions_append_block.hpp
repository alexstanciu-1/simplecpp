#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionBlockRow;
struct BackendEmissionDecisionArtifact;
void __latency_fn_backend_emission_decisions_append_block(BackendEmissionDecisionArtifact& artifact, BackendEmissionBlockRow row);
}
