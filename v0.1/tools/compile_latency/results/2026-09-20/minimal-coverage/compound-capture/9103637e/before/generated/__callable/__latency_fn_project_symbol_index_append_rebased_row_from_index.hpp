#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
class SourceUnitTable;
struct SourceUnitTableRow;
ProjectSymbolIndexRow __latency_fn_project_symbol_index_append_rebased_row_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, ProjectSymbolIndexRow sourceRow);
}
