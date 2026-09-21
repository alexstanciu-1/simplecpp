#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityConsumerPlan;
class ScalarBodyBackendCollection;
void __latency_fn_semantic_body_capability_consumers_append_scalar_body_common(shared_p<CapabilityConsumerPlan>& plan, shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> fallbackTypeRefId);
}
