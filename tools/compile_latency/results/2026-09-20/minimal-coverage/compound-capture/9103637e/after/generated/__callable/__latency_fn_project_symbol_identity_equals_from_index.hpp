#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
bool_t __latency_fn_project_symbol_identity_equals_from_index(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow left, ProjectSymbolIndexRow right);
}
