#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentDirtyProcessedRow;
struct ResidentDirtyQueueRow;
bool_t __latency_fn_resident_dirty_propagation_processed_entity_matches_queue(ResidentDirtyProcessedRow processed, ResidentDirtyQueueRow row);
}
