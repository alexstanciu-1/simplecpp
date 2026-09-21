#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityConsumerRow;
struct CapabilityReadinessRow;
struct OperationReadiness;
OperationReadiness __latency_fn_operation_readiness_readiness_from_capability(CapabilityConsumerRow consumer, CapabilityReadinessRow capabilityReadiness);
}
