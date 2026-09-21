#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonResponseRow;
ResidentDaemonResponseRow __latency_fn_resident_daemon_file_ipc_response_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> responseId);
}
