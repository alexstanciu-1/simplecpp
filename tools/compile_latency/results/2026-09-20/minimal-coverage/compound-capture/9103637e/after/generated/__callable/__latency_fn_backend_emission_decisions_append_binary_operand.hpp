#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendBinaryOperandRow;
struct BackendEmissionDecisionArtifact;
void __latency_fn_backend_emission_decisions_append_binary_operand(BackendEmissionDecisionArtifact& artifact, BackendBinaryOperandRow row);
}
