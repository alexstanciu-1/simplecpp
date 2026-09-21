#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDirtyFanoutRow;
class ProjectSymbolIndex;
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_fanout(ProjectDirtyFanoutRow row, shared_p<ProjectSymbolIndex> symbols);
}
