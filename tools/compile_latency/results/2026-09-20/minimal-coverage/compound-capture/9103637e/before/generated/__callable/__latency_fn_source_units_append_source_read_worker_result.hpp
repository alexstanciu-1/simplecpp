#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceReadTable;
class SourceReadWorkerResult;
void __latency_fn_source_units_append_source_read_worker_result(shared_p<SourceReadTable>& table, const shared_p<SourceReadTable>& descriptorSourceReads, shared_p<SourceReadWorkerResult> workerResult);
}
