#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ResidentDaemonIpcArtifact;
struct ResidentDaemonSourceSlotStatRow;
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_event_for_stat(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonSourceSlotStatRow stat);
}
