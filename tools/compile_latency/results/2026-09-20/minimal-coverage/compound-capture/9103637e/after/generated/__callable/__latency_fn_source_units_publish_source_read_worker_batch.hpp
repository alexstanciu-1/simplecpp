#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceReadTable;
class SourceReadWorkerPublicationStats;
class SourceReadWorkerResult;
void __latency_fn_source_units_publish_source_read_worker_batch(shared_p<SourceReadTable>& table, shared_p<SourceReadTable> descriptorSourceReads, const vector_t<shared_p<SourceReadWorkerResult>>& batch, shared_p<SourceReadWorkerPublicationStats>& stats);
}
