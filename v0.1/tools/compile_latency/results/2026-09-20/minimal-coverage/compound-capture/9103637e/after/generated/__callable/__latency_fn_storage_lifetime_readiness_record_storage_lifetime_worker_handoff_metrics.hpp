#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct StorageLifetimeRequestRow;
class StorageLifetimeWorkerInput;
class StorageLifetimeWorkerResult;
void __latency_fn_storage_lifetime_readiness_record_storage_lifetime_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<StorageLifetimeWorkerInput>>& inputs, const vector_t<shared_p<StorageLifetimeWorkerResult>>& results, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches);
}
