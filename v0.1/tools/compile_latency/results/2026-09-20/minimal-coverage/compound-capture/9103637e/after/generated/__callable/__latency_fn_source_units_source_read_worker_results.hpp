#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceReadWorkerProbeInput;
class SourceReadWorkerResult;
vector_t<shared_p<SourceReadWorkerResult>> __latency_fn_source_units_source_read_worker_results(const vector_t<shared_p<SourceReadWorkerProbeInput>>& inputs, int_t<> workerCount);
}
