#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityConsumerPlan;
class ScalarBodyBackendCollection;
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_while_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> whileSourceRowId, int_t<std::uint32_t> whileConditionSourceRowId, int_t<std::uint16_t> whileConditionFeatureId, int_t<std::uint32_t> whileConditionProviderTypeRefId, int_t<std::uint32_t> whileConditionTypeRefId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnTypeRefId);
}
