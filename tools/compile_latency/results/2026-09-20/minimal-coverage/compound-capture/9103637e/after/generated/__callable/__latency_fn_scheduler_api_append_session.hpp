#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SchedulerApiArtifact;
struct SchedulerSessionRow;
int_t<std::uint32_t> __latency_fn_scheduler_api_append_session(shared_p<SchedulerApiArtifact>& artifact, SchedulerSessionRow row);
}
