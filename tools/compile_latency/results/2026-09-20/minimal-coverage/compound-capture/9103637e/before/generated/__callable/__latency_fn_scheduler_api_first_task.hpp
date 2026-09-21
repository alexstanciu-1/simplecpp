#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerTaskRow;
SchedulerTaskRow __latency_fn_scheduler_api_first_task(shared_p<SchedulerApiArtifact> artifact);
}
