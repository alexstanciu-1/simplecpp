#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionValueRow;
string_t __latency_fn_llvm_text_from_plan_return_value_text_from_emission(BackendEmissionDecisionArtifact& emission, BackendEmissionValueRow value, const string_t& returnType);
}
