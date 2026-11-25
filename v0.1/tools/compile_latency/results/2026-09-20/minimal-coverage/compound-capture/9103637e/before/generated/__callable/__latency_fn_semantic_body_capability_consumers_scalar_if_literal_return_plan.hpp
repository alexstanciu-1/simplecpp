#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityConsumerPlan;
class ScalarBodyBackendCollection;
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_if_literal_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> fallbackLiteralSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> returnTypeRefId);
}
