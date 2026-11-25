#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitFrontendWorkerLockedPublicationStats;
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
void __latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_locked_publication_metrics(shared_p<CompilerProjectRunReport>& report, const shared_p<SourceUnitTable>& sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, const shared_p<SourceUnitFrontendWorkerLockedPublicationStats>& stats, int_t<> workerCount, bool_t selected, int_t<std::uint32_t> taskRunElapsedUs, int_t<std::uint32_t> totalElapsedUs);
}
