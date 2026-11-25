#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_request(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> sessionId, const string_t& projectRoot, const string_t& requestPath, const string_t& responsePath, int_t<std::uint32_t> createdAgeMs, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount, int_t<std::uint16_t> commandKindId, int_t<std::uint16_t> statusId);
}
