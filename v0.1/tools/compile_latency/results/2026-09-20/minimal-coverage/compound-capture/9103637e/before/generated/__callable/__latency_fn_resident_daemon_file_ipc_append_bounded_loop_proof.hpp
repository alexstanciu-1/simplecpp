#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_bounded_loop_proof(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> sessionId, int_t<std::uint32_t> requestId, int_t<std::uint32_t> pollIntervalMs, int_t<std::uint32_t> testIdleTimeoutMs);
}
