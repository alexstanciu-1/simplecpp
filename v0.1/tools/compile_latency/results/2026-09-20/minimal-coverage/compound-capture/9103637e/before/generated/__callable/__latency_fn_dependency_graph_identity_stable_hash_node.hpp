#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraphNodeRow;
class ProjectSymbolIndex;
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_node(ProjectDependencyGraphNodeRow row, shared_p<ProjectSymbolIndex> symbols);
}
