#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
struct ResidentDaemonSourceSlotScanRow;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_dirty_source_projection_from_scan(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonRequestRow request, ResidentDaemonSourceSlotScanRow scan, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> transactionId, int_t<std::uint16_t> executionModeId);
}
