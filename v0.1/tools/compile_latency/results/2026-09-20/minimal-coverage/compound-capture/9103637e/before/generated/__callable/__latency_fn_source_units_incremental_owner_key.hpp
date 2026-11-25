#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitTable;
struct SourceUnitTableRow;
string_t __latency_fn_source_units_incremental_owner_key(shared_p<SourceUnitTable> table, SourceUnitTableRow row);
}
