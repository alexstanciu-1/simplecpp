#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
bool_t __latency_fn_resident_daemon_file_ipc_write_request_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonRequestRow row);
}
