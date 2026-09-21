#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerBuildResult;
class SourceUnitTable;
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results_are_publishable(shared_p<SourceUnitTable> sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results);
}
