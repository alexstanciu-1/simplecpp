#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_scan(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> statFirstId, int_t<std::uint32_t> statCount, int_t<std::uint32_t> candidateEventCount, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount, int_t<std::uint16_t> statusId);
}
