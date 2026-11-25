#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerWorkerRow;
int_t<std::uint32_t> __latency_fn_scheduler_api_append_worker(shared_p<SchedulerApiArtifact>& artifact, SchedulerWorkerRow row);
}
