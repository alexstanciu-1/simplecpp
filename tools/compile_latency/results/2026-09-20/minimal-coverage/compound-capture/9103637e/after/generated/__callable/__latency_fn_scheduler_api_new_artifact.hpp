#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_new_artifact(int_t<> sessionCapacity, int_t<> workerCapacity, int_t<> taskCapacity);
}
