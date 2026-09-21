#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityConsumerRow;
struct CapabilityProviderRow;
class CompilerProjectRunReport;
struct OperationReadiness;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
void __latency_fn_storage_lifetime_readiness_record_storage_lifetime_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, OperationReadiness readyOperation, OperationReadiness blockedOperation, CapabilityConsumerRow readyConsumer, CapabilityConsumerRow blockedConsumer, CapabilityProviderRow provider, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow, int_t<> workerCount, bool_t upstreamCapabilityReadinessWorkerReady, bool_t& storageLifetimeWorkerCandidateReady);
}
