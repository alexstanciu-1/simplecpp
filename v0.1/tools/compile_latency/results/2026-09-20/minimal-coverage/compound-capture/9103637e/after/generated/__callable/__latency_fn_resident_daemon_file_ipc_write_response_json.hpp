#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
struct ResidentDaemonResponseRow;
bool_t __latency_fn_resident_daemon_file_ipc_write_response_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonResponseRow row, ResidentDaemonRequestRow request);
}
