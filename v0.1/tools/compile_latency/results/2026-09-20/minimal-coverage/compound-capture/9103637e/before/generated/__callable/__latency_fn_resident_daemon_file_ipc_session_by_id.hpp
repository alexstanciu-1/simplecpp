#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonSessionRow;
ResidentDaemonSessionRow __latency_fn_resident_daemon_file_ipc_session_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> sessionId);
}
