#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraphEdgeRow;
class ProjectSymbolIndex;
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_edge(ProjectDependencyGraphEdgeRow row, shared_p<ProjectSymbolIndex> symbols);
}
