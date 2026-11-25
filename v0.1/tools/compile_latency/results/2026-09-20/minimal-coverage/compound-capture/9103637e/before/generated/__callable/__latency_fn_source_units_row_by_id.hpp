#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitTable;
struct SourceUnitTableRow;
SourceUnitTableRow __latency_fn_source_units_row_by_id(shared_p<SourceUnitTable> table, int_t<std::uint32_t> sourceUnitId);
}
