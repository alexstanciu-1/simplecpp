#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct FunctionBodyTextEmissionPreflightArtifact;
FunctionBodyTextEmissionPreflightArtifact __latency_fn_llvm_text_from_plan_preflight_from_emission(BackendEmissionDecisionArtifact& emission);
}
