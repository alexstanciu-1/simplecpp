#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
void __latency_fn_mt_publication_metrics_record_worker_gate(shared_p<CompilerProjectRunReport>& report, const string_t& stageKey, const string_t& blockedField, const string_t& upstreamWorkerReadyField, const string_t& upstreamCoordinatorBlockedField, const string_t& workerCandidateReadyField, const string_t& o3MeasurementRequiredField, const string_t& speedupClaimBlockedField, const string_t& payloadCopyBytesField, bool_t hasWorkerCandidate, bool_t upstreamWorkerReady, bool_t workerCandidateReady);
}
