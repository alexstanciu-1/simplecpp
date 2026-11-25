#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentChangeEventRow;
struct ResidentDirtyQueueRow;
ResidentDirtyQueueRow __latency_fn_resident_dirty_propagation_row_from_event(ResidentChangeEventRow event);
}
