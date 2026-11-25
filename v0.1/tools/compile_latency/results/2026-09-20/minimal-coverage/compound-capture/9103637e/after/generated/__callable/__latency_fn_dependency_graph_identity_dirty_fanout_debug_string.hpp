#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDirtyFanoutRow;
class ProjectSymbolIndex;
string_t __latency_fn_dependency_graph_identity_dirty_fanout_debug_string(ProjectDirtyFanoutRow row, shared_p<ProjectSymbolIndex> symbols);
}
