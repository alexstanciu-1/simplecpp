#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentDirtyProcessedRow;
struct ResidentDirtyQueueRow;
ResidentDirtyProcessedRow __latency_fn_resident_dirty_propagation_row_from_queue_for_processed(ResidentDirtyQueueRow row);
}
