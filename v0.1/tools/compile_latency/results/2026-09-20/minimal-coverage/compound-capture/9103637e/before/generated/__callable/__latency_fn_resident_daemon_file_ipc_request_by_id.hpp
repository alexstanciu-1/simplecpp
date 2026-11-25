#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
ResidentDaemonRequestRow __latency_fn_resident_daemon_file_ipc_request_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> requestId);
}
