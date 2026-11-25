#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectSymbolIndexRow __latency_fn_project_symbol_index_function_symbol_by_name(shared_p<ProjectSymbolIndex> index, const string_t& functionName);
}
