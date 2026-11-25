#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyQueueRow;
ResidentDirtyQueueRow __latency_fn_resident_dirty_propagation_first_queue_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId);
}
