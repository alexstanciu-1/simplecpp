#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitFrontendWorkerBuildResult;
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
void __latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_publication_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results, int_t<> workerCount, bool_t selected, int_t<std::uint32_t> taskRunElapsedUs, int_t<std::uint32_t> frontendPublishElapsedUs, int_t<std::uint32_t> symbolPublishElapsedUs, int_t<std::uint32_t> publicationElapsedUs, int_t<std::uint32_t> totalElapsedUs);
}
