#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityReadinessWorkerInput;
class ProjectCallableContractArtifact;
struct ProjectSymbolIndexRow;
struct TypeRefTable;
vector_t<shared_p<CapabilityReadinessWorkerInput>> __latency_fn_type_capability_readiness_capability_readiness_worker_inputs(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts);
}
