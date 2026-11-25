#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
class SourceUnitTable;
struct SourceUnitTableRow;
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_rows_for_source_unit_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit);
}
