#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectSymbolIndexRow __latency_fn_project_symbol_index_row_by_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> symbolId);
}
