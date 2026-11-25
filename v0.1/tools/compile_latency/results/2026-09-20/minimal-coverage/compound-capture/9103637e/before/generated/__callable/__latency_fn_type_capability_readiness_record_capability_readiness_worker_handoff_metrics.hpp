#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityCoverageArtifact;
class CapabilityReadinessWorkerInput;
class CapabilityReadinessWorkerResult;
class CompilerProjectRunReport;
struct TypeRefTable;
void __latency_fn_type_capability_readiness_record_capability_readiness_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<CapabilityReadinessWorkerInput>>& inputs, const vector_t<shared_p<CapabilityReadinessWorkerResult>>& results, TypeRefTable typeRefs, shared_p<CapabilityCoverageArtifact> coverage, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches);
}
