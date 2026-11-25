#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
class CompilerProjectRunReport;
void __latency_fn_backend_object_link_cache_record_object_output_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions, int_t<> workerCount, bool_t upstreamEmissionLLVMWorkerReady, bool_t& objectOutputWorkerCandidateReady, bool_t& objectOutputWorkerSelected);
}
