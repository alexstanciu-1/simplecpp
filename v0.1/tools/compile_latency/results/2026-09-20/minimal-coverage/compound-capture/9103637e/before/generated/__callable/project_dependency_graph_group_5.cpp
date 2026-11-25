#include <scpp/lang/php.hpp>
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_dependency_graph_add_reference_edges.hpp"
#include "__callable/__latency_fn_project_dependency_graph_add_symbol.hpp"
#include "__callable/__latency_fn_project_dependency_graph_from_symbols_references_and_contracts.hpp"
#include "__callable/__latency_fn_project_dependency_graph_new_graph.hpp"
namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraph __latency_fn_project_dependency_graph_from_symbols_references_and_contracts(shared_p<ProjectSymbolIndex> symbols, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::from_symbols_references_and_contracts", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[60]);
	int_t<> symbolCount = required_cast<int_t<>>(cast<int_t<>>(symbols->symbol_count));
	int_t<> referenceCount = required_cast<int_t<>>(cast<int_t<>>(references->reference_count));
	ProjectDependencyGraph graph = __latency_fn_project_dependency_graph_new_graph((symbolCount * static_cast<int_t<> >(2)), ((symbolCount * static_cast<int_t<> >(2)) + referenceCount), (symbolCount * static_cast<int_t<> >(3)), (symbolCount * static_cast<int_t<> >(2)));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		__latency_fn_project_dependency_graph_add_symbol(graph, symbol, references);
	}
	__latency_fn_project_dependency_graph_add_reference_edges(graph, references, contracts);
	return graph;
}

}
