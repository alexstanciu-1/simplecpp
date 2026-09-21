#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
class SchedulerApiArtifact;
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_worker_shadow_artifact_from_work_order(shared_p<DeterministicWorkOrderArtifact> workOrder, int_t<std::uint32_t> generation, int_t<> workerCount);
}
