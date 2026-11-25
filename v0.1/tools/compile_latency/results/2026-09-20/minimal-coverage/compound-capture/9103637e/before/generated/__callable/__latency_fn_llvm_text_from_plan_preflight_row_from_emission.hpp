#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct FunctionBodyTextEmissionPreflightRow;
FunctionBodyTextEmissionPreflightRow __latency_fn_llvm_text_from_plan_preflight_row_from_emission(int_t<std::uint32_t> preflightId, BackendEmissionDecisionArtifact& emission);
}
