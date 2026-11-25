#include <scpp/lang/php.hpp>
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/ProjectDirtyFanoutRow.hpp"
#include "__types/ProjectDirtyReuseProjectionRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_node.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_status_ready_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_edge.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_dirty_projection.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_dirty_fanout.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_node.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_edge.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_dirty_projection.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_dirty_fanout.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraphNodeRow __latency_fn_project_dependency_graph_make_node(int_t<std::uint32_t> nodeId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint16_t> nodeKindId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::make_node", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[43]);
	ProjectDependencyGraphNodeRow row = ProjectDependencyGraphNodeRow{};
	row->node_id = nodeId;
	row->source_unit_id = sourceUnitId;
	row->symbol_id = symbolId;
	row->node_kind_id = nodeKindId;
	row->status_id = __latency_fn_project_dependency_graph_node_status_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraphEdgeRow __latency_fn_project_dependency_graph_make_edge(int_t<std::uint32_t> edgeId, int_t<std::uint32_t> fromSourceUnitId, int_t<std::uint32_t> fromSymbolId, int_t<std::uint32_t> toSourceUnitId, int_t<std::uint32_t> toSymbolId, int_t<std::uint32_t> referenceId, int_t<std::uint32_t> callableContractId, int_t<std::uint16_t> edgeKindId, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::make_edge", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[44]);
	ProjectDependencyGraphEdgeRow row = ProjectDependencyGraphEdgeRow{};
	row->edge_id = edgeId;
	row->from_source_unit_id = fromSourceUnitId;
	row->from_symbol_id = fromSymbolId;
	row->to_source_unit_id = toSourceUnitId;
	row->to_symbol_id = toSymbolId;
	row->reference_id = referenceId;
	row->callable_contract_id = callableContractId;
	row->edge_kind_id = edgeKindId;
	row->status_id = statusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDirtyReuseProjectionRow __latency_fn_project_dependency_graph_make_dirty_projection(int_t<std::uint32_t> projectionId, ProjectSymbolIndexRow symbol, int_t<std::uint16_t> recomputeTargetId, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyScopeId, int_t<std::uint16_t> reuseScopeId, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::make_dirty_projection", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[45]);
	ProjectDirtyReuseProjectionRow row = ProjectDirtyReuseProjectionRow{};
	row->projection_id = projectionId;
	row->symbol_id = symbol->symbol_id;
	row->source_unit_id = symbol->source_unit_id;
	row->recompute_target_id = recomputeTargetId;
	row->change_kind_id = changeKindId;
	row->dirty_scope_id = dirtyScopeId;
	row->reuse_scope_id = reuseScopeId;
	row->status_id = statusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDirtyFanoutRow __latency_fn_project_dependency_graph_make_dirty_fanout(int_t<std::uint32_t> fanoutId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> dependentSymbolId, int_t<std::uint16_t> recomputeTargetId, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> graphEdgeKindId, int_t<std::uint16_t> reuseScopeId, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::make_dirty_fanout", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[46]);
	ProjectDirtyFanoutRow row = ProjectDirtyFanoutRow{};
	row->fanout_id = fanoutId;
	row->symbol_id = symbolId;
	row->dependent_symbol_id = dependentSymbolId;
	row->recompute_target_id = recomputeTargetId;
	row->change_kind_id = changeKindId;
	row->graph_edge_kind_id = graphEdgeKindId;
	row->reuse_scope_id = reuseScopeId;
	row->status_id = statusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_node(ProjectDependencyGraph& graph, ProjectDependencyGraphNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::append_node", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[47]);
	int_t<std::uint32_t> nextId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(graph->nodes)));
	row->node_id = nextId;
	(void) graph->nodes.append(row);
	graph->node_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(graph->nodes));
	return cast<int_t<std::uint32_t>>(nextId);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_edge(ProjectDependencyGraph& graph, ProjectDependencyGraphEdgeRow row) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::append_edge", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[48]);
	int_t<std::uint32_t> nextId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(graph->edges)));
	row->edge_id = nextId;
	(void) graph->edges.append(row);
	graph->edge_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(graph->edges));
	return cast<int_t<std::uint32_t>>(nextId);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_dirty_projection(ProjectDependencyGraph& graph, ProjectDirtyReuseProjectionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::append_dirty_projection", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[49]);
	int_t<std::uint32_t> nextId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(graph->dirty_projections)));
	row->projection_id = nextId;
	(void) graph->dirty_projections.append(row);
	graph->dirty_projection_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(graph->dirty_projections));
	return cast<int_t<std::uint32_t>>(nextId);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_dependency_graph_append_dirty_fanout(ProjectDependencyGraph& graph, ProjectDirtyFanoutRow row) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::append_dirty_fanout", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[50]);
	int_t<std::uint32_t> nextId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(graph->dirty_fanouts)));
	row->fanout_id = nextId;
	(void) graph->dirty_fanouts.append(row);
	graph->dirty_fanout_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(graph->dirty_fanouts));
	return cast<int_t<std::uint32_t>>(nextId);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraphNodeRow __latency_fn_project_dependency_graph_node_by_id(ProjectDependencyGraph graph, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::node_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[51]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(nodeId, cast<int_t<>>(graph->node_count))))) {
		ProjectDependencyGraphNodeRow row = graph->nodes[__latency_fn_structure_row_ids_dense_index(nodeId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->node_id), cast<int_t<>>(nodeId)))) {
			return row;
		}
	}
	auto __latency_local_0 = graph->nodes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->node_id), cast<int_t<>>(nodeId)))) {
			return row;
		}
	}
	ProjectDependencyGraphNodeRow empty = ProjectDependencyGraphNodeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraphEdgeRow __latency_fn_project_dependency_graph_edge_by_id(ProjectDependencyGraph graph, int_t<std::uint32_t> edgeId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::edge_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[52]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(edgeId, cast<int_t<>>(graph->edge_count))))) {
		ProjectDependencyGraphEdgeRow row = graph->edges[__latency_fn_structure_row_ids_dense_index(edgeId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->edge_id), cast<int_t<>>(edgeId)))) {
			return row;
		}
	}
	auto __latency_local_0 = graph->edges;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->edge_id), cast<int_t<>>(edgeId)))) {
			return row;
		}
	}
	ProjectDependencyGraphEdgeRow empty = ProjectDependencyGraphEdgeRow{};
	return empty;
}

}
