#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerTaskRow;
SchedulerTaskRow __latency_fn_scheduler_api_task_by_output_order(shared_p<SchedulerApiArtifact> artifact, int_t<std::uint32_t> outputOrderId);
}
