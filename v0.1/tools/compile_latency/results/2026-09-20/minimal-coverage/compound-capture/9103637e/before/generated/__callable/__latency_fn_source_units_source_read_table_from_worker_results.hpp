#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceReadTable;
class SourceReadWorkerResult;
shared_p<SourceReadTable> __latency_fn_source_units_source_read_table_from_worker_results(const shared_p<SourceReadTable>& descriptorSourceReads, const vector_t<shared_p<SourceReadWorkerResult>>& workerResults);
}
