#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct LlvmApiSinkPreflightArtifact;
void __latency_fn_llvm_api_sink_preflight_append_from_emission(LlvmApiSinkPreflightArtifact& artifact, BackendEmissionDecisionArtifact& emission);
}
