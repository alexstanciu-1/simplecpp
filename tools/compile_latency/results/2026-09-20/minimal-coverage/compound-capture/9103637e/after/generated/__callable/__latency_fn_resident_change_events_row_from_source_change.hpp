#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentChangeEventRow;
struct ResidentSourceUnitChangeRow;
ResidentChangeEventRow __latency_fn_resident_change_events_row_from_source_change(ResidentSourceUnitChangeRow change);
}
