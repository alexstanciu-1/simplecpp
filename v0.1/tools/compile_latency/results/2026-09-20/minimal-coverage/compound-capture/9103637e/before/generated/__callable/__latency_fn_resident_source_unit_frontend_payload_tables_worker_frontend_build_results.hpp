#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerBuildResult;
class SourceUnitFrontendWorkerPayloadInput;
vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, int_t<> workerCount);
}
