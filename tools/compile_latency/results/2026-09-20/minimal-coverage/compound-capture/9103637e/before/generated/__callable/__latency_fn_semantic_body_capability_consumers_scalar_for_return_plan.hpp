#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityConsumerPlan;
class ScalarBodyBackendCollection;
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_for_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> forConditionSourceRowId, int_t<std::uint16_t> forConditionFeatureId, int_t<std::uint32_t> forConditionProviderTypeRefId, int_t<std::uint32_t> forConditionTypeRefId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnTypeRefId);
}
