#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceReadTable;
void __latency_fn_source_units_record_source_read_worker_probe_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceReadTable> sourceReads, int_t<> workerCount);
}
