#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
bool_t __latency_fn_resident_daemon_file_ipc_update_request_event_window(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount);
}
