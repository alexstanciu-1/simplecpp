#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class PipelineBatchConfig;
class ResidentDaemonIpcArtifact;
shared_p<CompilerProjectRunReport> __latency_fn_resident_daemon_incremental_proof_run_changed_file_request(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& requestPath, shared_p<CompilerProjectRunReport> previousReport, shared_p<PipelineBatchConfig> batch);
}
