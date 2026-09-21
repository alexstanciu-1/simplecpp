#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityCoverageArtifact;
class ProjectCallableContractArtifact;
struct TypeRefTable;
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts(TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts);
}
