#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ResidentDaemonIpcArtifact;
struct ResidentDaemonRequestRow;
class SourceUnitTable;
int_t<std::uint32_t> __latency_fn_resident_daemon_incremental_proof_append_request_rescan(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonRequestRow request, shared_p<CompilerProjectRunReport> previousReport, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId);
}
