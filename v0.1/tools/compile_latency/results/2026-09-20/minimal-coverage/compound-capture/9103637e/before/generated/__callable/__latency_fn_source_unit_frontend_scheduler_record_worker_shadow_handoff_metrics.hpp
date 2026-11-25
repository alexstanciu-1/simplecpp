#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitTable;
void __latency_fn_source_unit_frontend_scheduler_record_worker_shadow_handoff_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<> workerCount, int_t<> payloadTableCount, int_t<> descriptorCount, bool_t handoffMatches);
}
