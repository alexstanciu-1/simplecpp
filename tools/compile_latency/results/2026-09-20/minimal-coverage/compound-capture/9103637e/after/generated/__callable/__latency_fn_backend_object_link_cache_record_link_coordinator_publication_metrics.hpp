#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
class CompilerProjectRunReport;
void __latency_fn_backend_object_link_cache_record_link_coordinator_publication_metrics(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions, int_t<> workerCount, bool_t upstreamObjectOutputWorkerReady, bool_t upstreamObjectOutputWorkerSelected);
}
