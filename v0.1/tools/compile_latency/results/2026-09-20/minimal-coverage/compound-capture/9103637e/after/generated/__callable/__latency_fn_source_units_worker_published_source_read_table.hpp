#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceReadTable;
shared_p<SourceReadTable> __latency_fn_source_units_worker_published_source_read_table(shared_p<CompilerProjectRunReport>& report, shared_p<SourceReadTable> coordinatorSourceReads, int_t<> workerCount);
}
