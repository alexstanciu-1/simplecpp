#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityConsumerPlan;
class CapabilityCoverageArtifact;
struct TypeRefTable;
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(TypeRefTable typeRefs, shared_p<CapabilityConsumerPlan> plan);
}
