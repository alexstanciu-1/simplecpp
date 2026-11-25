#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
struct BackendEmissionDecisionArtifact;
void __latency_fn_backend_emission_decisions_append_control_flow_operand(BackendEmissionDecisionArtifact& artifact, BackendControlFlowOperandRow row);
}
