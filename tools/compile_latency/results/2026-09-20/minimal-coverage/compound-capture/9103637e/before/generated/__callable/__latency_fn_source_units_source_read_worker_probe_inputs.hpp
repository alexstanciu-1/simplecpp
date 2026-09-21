#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceReadTable;
class SourceReadWorkerProbeInput;
vector_t<shared_p<SourceReadWorkerProbeInput>> __latency_fn_source_units_source_read_worker_probe_inputs(const shared_p<SourceReadTable>& sourceReads);
}
