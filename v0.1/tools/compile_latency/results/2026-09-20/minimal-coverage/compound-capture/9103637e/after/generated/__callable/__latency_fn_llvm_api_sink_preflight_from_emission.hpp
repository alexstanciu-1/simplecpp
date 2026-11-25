#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct LlvmApiSinkPreflightArtifact;
LlvmApiSinkPreflightArtifact __latency_fn_llvm_api_sink_preflight_from_emission(BackendEmissionDecisionArtifact& emission);
}
