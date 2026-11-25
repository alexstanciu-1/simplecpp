#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
struct ProjectSymbolParameterRow;
vector_t<ProjectSymbolParameterRow> __latency_fn_project_symbol_index_parameter_rows_for_symbol(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row);
}
