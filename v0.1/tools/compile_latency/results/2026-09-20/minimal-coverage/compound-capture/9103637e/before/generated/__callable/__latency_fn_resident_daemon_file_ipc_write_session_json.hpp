#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonSessionRow;
bool_t __latency_fn_resident_daemon_file_ipc_write_session_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonSessionRow row);
}
