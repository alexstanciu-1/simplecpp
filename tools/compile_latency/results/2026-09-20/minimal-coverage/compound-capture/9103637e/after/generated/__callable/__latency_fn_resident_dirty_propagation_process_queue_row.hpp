#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyQueueRow;
void __latency_fn_resident_dirty_propagation_process_queue_row(shared_p<CompilerProjectRunReport>& report, ResidentDirtyQueueRow row);
}
