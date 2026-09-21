#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ProjectDependencyGraph;
void __latency_fn_resident_dependency_edges_append_from_graph(shared_p<CompilerProjectRunReport>& report, ProjectDependencyGraph graph, int_t<std::uint32_t> ownerRunId);
}
