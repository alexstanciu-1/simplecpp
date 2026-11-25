#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_content_stat(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> sourceSlotId, const string_t& path, int_t<std::uint32_t> previousSize, int_t<std::uint32_t> currentSize, int_t<std::uint32_t> previousContentHash, int_t<std::uint32_t> currentContentHash, int_t<std::uint16_t> scanStatusId);
}
