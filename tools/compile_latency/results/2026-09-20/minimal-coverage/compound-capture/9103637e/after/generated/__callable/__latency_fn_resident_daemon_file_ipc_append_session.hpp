#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_session(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& projectRoot, const string_t& sessionPath, const string_t& requestDir, const string_t& responseDir, int_t<std::uint32_t> pid, int_t<std::uint32_t> generation, int_t<std::uint32_t> heartbeatAgeMs, int_t<std::uint16_t> statusId);
}
