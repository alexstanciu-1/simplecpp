#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerTaskRow;
vector_t<SchedulerTaskRow> __latency_fn_scheduler_api_tasks_by_output_order(shared_p<SchedulerApiArtifact> artifact);
}
