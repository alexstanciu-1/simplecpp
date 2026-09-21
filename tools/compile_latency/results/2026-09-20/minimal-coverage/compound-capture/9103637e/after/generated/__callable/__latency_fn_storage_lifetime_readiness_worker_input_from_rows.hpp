#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityConsumerRow;
struct CapabilityProviderRow;
struct OperationReadiness;
struct ProjectSymbolIndexRow;
class StorageLifetimeWorkerInput;
shared_p<StorageLifetimeWorkerInput> __latency_fn_storage_lifetime_readiness_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, OperationReadiness readyOperation, OperationReadiness blockedOperation, CapabilityConsumerRow readyConsumer, CapabilityConsumerRow blockedConsumer, CapabilityProviderRow provider);
}
