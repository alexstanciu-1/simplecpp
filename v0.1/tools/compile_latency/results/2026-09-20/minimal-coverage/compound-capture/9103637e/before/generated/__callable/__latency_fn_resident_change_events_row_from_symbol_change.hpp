#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentChangeEventRow;
struct ResidentSymbolDefinitionChangeRow;
ResidentChangeEventRow __latency_fn_resident_change_events_row_from_symbol_change(ResidentSymbolDefinitionChangeRow change);
}
