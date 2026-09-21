#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendLocalOperandRow;
void __latency_fn_backend_emission_decisions_append_local_operand(BackendEmissionDecisionArtifact& artifact, BackendLocalOperandRow row);
}
