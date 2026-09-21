#include <scpp/lang/php.hpp>
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/ProjectDirtyFanoutRow.hpp"
#include "__types/ProjectDirtyReuseProjectionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_artifact_kind_project_dependency_graph_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_graph_model_ownership_reference_edges_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_new_graph.hpp"
#include "__callable/__latency_fn_project_dependency_graph_reserve_graph.hpp"
#include "__callable/__latency_fn_project_dependency_graph_resolution_status_analyzer_only_backend_blocked_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_source_model_symbol_reference_contract_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_reserve_graph.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_symbol_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_declared_in_source_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_owns_symbol_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_blocked_unresolved_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_ready_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_resolved_analyzer_only_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_body_only_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_export_surface_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_fanout_local_body_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_fanout_resolved_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_reuse_ready_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_symbol_key_for_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_dependency_graph_source_unit_key_for_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_key.hpp"
#include "__callable/__latency_fn_project_dependency_graph_source_unit_key_for_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_symbol_key_for_id.hpp"
namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_project_dependency_graph_dirty_status_no_dependents_id() {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::dirty_status_no_dependents_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[33]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDependencyGraph __latency_fn_project_dependency_graph_new_graph(int_t<> nodeCapacity, int_t<> edgeCapacity, int_t<> projectionCapacity, int_t<> fanoutCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::new_graph", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[34]);
	ProjectDependencyGraph graph = ProjectDependencyGraph{};
	graph->artifact_kind_id = __latency_fn_project_dependency_graph_artifact_kind_project_dependency_graph_id();
	graph->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	graph->source_model_id = __latency_fn_project_dependency_graph_source_model_symbol_reference_contract_id();
	graph->graph_model_id = __latency_fn_project_dependency_graph_graph_model_ownership_reference_edges_id();
	graph->resolution_status_id = __latency_fn_project_dependency_graph_resolution_status_analyzer_only_backend_blocked_id();
	__latency_fn_project_dependency_graph_reserve_graph(graph, nodeCapacity, edgeCapacity, projectionCapacity, fanoutCapacity);
	return graph;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
void __latency_fn_project_dependency_graph_reserve_graph(ProjectDependencyGraph& graph, int_t<> nodeCapacity, int_t<> edgeCapacity, int_t<> projectionCapacity, int_t<> fanoutCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::reserve_graph", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[35]);
	php::vector_reserve(graph->nodes, nodeCapacity);
	php::vector_reserve(graph->edges, edgeCapacity);
	php::vector_reserve(graph->dirty_projections, projectionCapacity);
	php::vector_reserve(graph->dirty_fanouts, fanoutCapacity);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_node_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::node_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[36]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_node_kind_source_unit_id())))) {
		return string_t("source_unit");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_node_kind_symbol_id())))) {
		return string_t("symbol");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_edge_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::edge_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[37]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_declared_in_source_id())))) {
		return string_t("declared_in_source");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id())))) {
		return string_t("resolved_symbol_reference");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_owns_symbol_id())))) {
		return string_t("owns_symbol");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_edge_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::edge_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[38]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_status_resolved_analyzer_only_id())))) {
		return string_t("resolved_analyzer_only");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_status_blocked_unresolved_reference_id())))) {
		return string_t("blocked_unresolved_reference");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_dirty_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::dirty_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[39]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_reuse_ready_id())))) {
		return string_t("reuse_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_body_only_id())))) {
		return string_t("dirty_body_only");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_export_surface_id())))) {
		return string_t("dirty_export_surface");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_fanout_local_body_id())))) {
		return string_t("fanout_ready_local_body");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_fanout_resolved_reference_id())))) {
		return string_t("fanout_ready_resolved_reference");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_project_dependency_graph_dirty_status_no_dependents_id())))) {
		return string_t("no_dependents");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_symbol_key_for_id(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::symbol_key_for_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[40]);
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_row_by_id(symbols, symbolId);
	return __latency_fn_project_symbol_index_symbol_key(symbols, symbol);
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_source_unit_key_for_id(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::source_unit_key_for_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[41]);
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(sourceUnitId)))) {
			return __latency_fn_project_symbol_index_source_unit_key(symbols, symbol);
		}
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
string_t __latency_fn_project_dependency_graph_node_key(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::node_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[42]);
	if (static_cast<bool>((cast<int_t<>>(symbolId) > static_cast<int_t<> >(0)))) {
		return (string_t("symbol:") + cast<string_t>(__latency_fn_project_dependency_graph_symbol_key_for_id(symbols, cast<int_t<std::uint32_t>>(symbolId))));
	}
	return (string_t("source_unit:") + cast<string_t>(__latency_fn_project_dependency_graph_source_unit_key_for_id(symbols, cast<int_t<std::uint32_t>>(sourceUnitId))));
}

}
