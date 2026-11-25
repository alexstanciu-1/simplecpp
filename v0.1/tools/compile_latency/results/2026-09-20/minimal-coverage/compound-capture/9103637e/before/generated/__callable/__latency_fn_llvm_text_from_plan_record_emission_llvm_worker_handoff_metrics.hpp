#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class CompilerProjectRunReport;
class EmissionLLVMWorkerInput;
class EmissionLLVMWorkerResult;
struct FunctionBodyTextEmissionPreflightArtifact;
void __latency_fn_llvm_text_from_plan_record_emission_llvm_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<EmissionLLVMWorkerInput>>& inputs, const vector_t<shared_p<EmissionLLVMWorkerResult>>& results, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches);
}
