#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
string_t __latency_fn_resident_daemon_file_ipc_request_debug_string(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonRequestRow row);
}
