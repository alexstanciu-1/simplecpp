#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendObjectLinkCacheArtifact;
class CompilerProjectRunReport;
class ObjectOutputWorkerInput;
class ObjectOutputWorkerResult;
void __latency_fn_backend_object_link_cache_record_object_output_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<ObjectOutputWorkerInput>>& inputs, const vector_t<shared_p<ObjectOutputWorkerResult>>& results, shared_p<BackendObjectLinkCacheArtifact> coordinatorCache, shared_p<BackendObjectLinkCacheArtifact> workerCache, int_t<std::uint32_t> coordinatorSemanticHash, int_t<std::uint32_t> workerSemanticHash, int_t<std::uint32_t> coordinatorPublishedRows, int_t<std::uint32_t> workerPublishedRows, bool_t matches);
}
