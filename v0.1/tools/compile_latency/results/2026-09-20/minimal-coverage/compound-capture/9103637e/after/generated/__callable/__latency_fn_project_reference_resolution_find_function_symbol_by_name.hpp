#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectSymbolIndexRow __latency_fn_project_reference_resolution_find_function_symbol_by_name(shared_p<ProjectSymbolIndex> symbols, const string_t& functionName);
}
