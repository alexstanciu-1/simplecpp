#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_response(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, const string_t& stdoutText, const string_t& stderrText, const string_t& summaryText, int_t<std::uint32_t> elapsedUs, int_t<std::uint16_t> statusId, int_t<std::uint16_t> exitCode);
}
