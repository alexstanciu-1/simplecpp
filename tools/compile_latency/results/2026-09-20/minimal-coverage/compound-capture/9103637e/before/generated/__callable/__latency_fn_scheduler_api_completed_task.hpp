#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct DeterministicWorkOrderRow;
struct SchedulerTaskRow;
SchedulerTaskRow __latency_fn_scheduler_api_completed_task(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> workerId, DeterministicWorkOrderRow work);
}
