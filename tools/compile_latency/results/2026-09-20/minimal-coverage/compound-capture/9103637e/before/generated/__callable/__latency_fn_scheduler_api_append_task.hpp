#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerTaskRow;
int_t<std::uint32_t> __latency_fn_scheduler_api_append_task(shared_p<SchedulerApiArtifact>& artifact, SchedulerTaskRow row);
}
