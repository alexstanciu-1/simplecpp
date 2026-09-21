#include <scpp/lang/php.hpp>
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/ProjectDirtyFanoutRow.hpp"
#include "__types/ProjectDirtyReuseProjectionRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_projection_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_fanout_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_source_node_exists.hpp"
#include "__callable/__latency_fn_project_dependency_graph_contract_id_for_reference.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dependent_references_for_target.hpp"
#include "__callable/__latency_fn_project_dependency_graph_add_symbol.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_dirty_fanout.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_dirty_projection.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_edge.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_node.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dependent_references_for_target.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_change_none_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_change_symbol_body_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_change_symbol_export_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_reuse_all_symbol_rows_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_reuse_public_surface_and_dependents_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_reuse_source_unit_rows_only_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_scope_export_surface_and_dependents_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_scope_local_body_backend_owner_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_scope_none_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_body_only_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_export_surface_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_fanout_local_body_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_fanout_resolved_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_reuse_ready_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_declared_in_source_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_owns_symbol_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_ready_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_dirty_fanout.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_dirty_projection.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_edge.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_node.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_symbol_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_backend_refresh_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_dependent_resolution_from_graph_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_dependent_resolution_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_dependent_resolution_later_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_local_lowering_backend_refresh_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_recompute_target_none_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_source_node_exists.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_add_reference_edges.hpp"
#include "__callable/__latency_fn_project_dependency_graph_append_edge.hpp"
#include "__callable/__latency_fn_project_dependency_graph_contract_id_for_reference.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_blocked_unresolved_reference_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_status_resolved_analyzer_only_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_make_edge.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDirtyReuseProjectionRow __latency_fn_project_dependency_graph_dirty_projection_by_id(ProjectDependencyGraph graph, int_t<std::uint32_t> projectionId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::dirty_projection_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[53]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(projectionId, cast<int_t<>>(graph->dirty_projection_count))))) {
		ProjectDirtyReuseProjectionRow row = graph->dirty_projections[__latency_fn_structure_row_ids_dense_index(projectionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			return row;
		}
	}
	auto __latency_local_0 = graph->dirty_projections;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			return row;
		}
	}
	ProjectDirtyReuseProjectionRow empty = ProjectDirtyReuseProjectionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
ProjectDirtyFanoutRow __latency_fn_project_dependency_graph_dirty_fanout_by_id(ProjectDependencyGraph graph, int_t<std::uint32_t> fanoutId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::dirty_fanout_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[54]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(fanoutId, cast<int_t<>>(graph->dirty_fanout_count))))) {
		ProjectDirtyFanoutRow row = graph->dirty_fanouts[__latency_fn_structure_row_ids_dense_index(fanoutId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->fanout_id), cast<int_t<>>(fanoutId)))) {
			return row;
		}
	}
	auto __latency_local_0 = graph->dirty_fanouts;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->fanout_id), cast<int_t<>>(fanoutId)))) {
			return row;
		}
	}
	ProjectDirtyFanoutRow empty = ProjectDirtyFanoutRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
bool_t __latency_fn_project_dependency_graph_source_node_exists(ProjectDependencyGraph graph, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::source_node_exists", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[55]);
	auto __latency_local_0 = graph->nodes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto node = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(node->source_unit_id), cast<int_t<>>(sourceUnitId)) && php::identical(cast<int_t<>>(node->symbol_id), static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(node->node_kind_id), cast<int_t<>>(__latency_fn_project_dependency_graph_node_kind_source_unit_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_dependency_graph_contract_id_for_reference(shared_p<ProjectCallableContractArtifact> contracts, int_t<std::uint32_t> referenceId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::contract_id_for_reference", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[56]);
	auto __latency_local_0 = contracts->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto contract = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(contract->reference_id), cast<int_t<>>(referenceId)))) {
			return contract->contract_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
vector_t<ProjectReferenceResolutionRow> __latency_fn_project_dependency_graph_dependent_references_for_target(shared_p<ProjectReferenceResolution> references, int_t<std::uint32_t> targetSymbolId) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::dependent_references_for_target", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[57]);
	vector_t<ProjectReferenceResolutionRow> rows = {};
	auto __latency_local_0 = references->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto reference = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(reference->resolved_symbol_id), cast<int_t<>>(targetSymbolId)))) {
			(void) rows.push_back(reference);
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
void __latency_fn_project_dependency_graph_add_symbol(ProjectDependencyGraph& graph, ProjectSymbolIndexRow symbol, shared_p<ProjectReferenceResolution> references) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::add_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[58]);
	if (static_cast<bool>((!__latency_fn_project_dependency_graph_source_node_exists(graph, symbol->source_unit_id)))) {
		ProjectDependencyGraphNodeRow sourceNode = __latency_fn_project_dependency_graph_make_node(__latency_fn_structure_row_ids_none_id(), symbol->source_unit_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_project_dependency_graph_node_kind_source_unit_id());
		__latency_fn_project_dependency_graph_append_node(graph, sourceNode);
	}
	ProjectDependencyGraphNodeRow symbolNode = __latency_fn_project_dependency_graph_make_node(__latency_fn_structure_row_ids_none_id(), symbol->source_unit_id, symbol->symbol_id, __latency_fn_project_dependency_graph_node_kind_symbol_id());
	__latency_fn_project_dependency_graph_append_node(graph, symbolNode);
	ProjectDependencyGraphEdgeRow ownsEdge = __latency_fn_project_dependency_graph_make_edge(__latency_fn_structure_row_ids_none_id(), symbol->source_unit_id, __latency_fn_structure_row_ids_none_id(), symbol->source_unit_id, symbol->symbol_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_project_dependency_graph_edge_kind_owns_symbol_id(), __latency_fn_project_dependency_graph_edge_status_ready_id());
	__latency_fn_project_dependency_graph_append_edge(graph, ownsEdge);
	ProjectDependencyGraphEdgeRow declaredEdge = __latency_fn_project_dependency_graph_make_edge(__latency_fn_structure_row_ids_none_id(), symbol->source_unit_id, symbol->symbol_id, symbol->source_unit_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_project_dependency_graph_edge_kind_declared_in_source_id(), __latency_fn_project_dependency_graph_edge_status_ready_id());
	__latency_fn_project_dependency_graph_append_edge(graph, declaredEdge);
	__latency_fn_project_dependency_graph_append_dirty_projection(graph, __latency_fn_project_dependency_graph_make_dirty_projection(__latency_fn_structure_row_ids_none_id(), symbol, __latency_fn_project_dependency_graph_recompute_target_none_id(), __latency_fn_project_dependency_graph_dirty_change_none_id(), __latency_fn_project_dependency_graph_dirty_scope_none_id(), __latency_fn_project_dependency_graph_dirty_reuse_all_symbol_rows_id(), __latency_fn_project_dependency_graph_dirty_status_reuse_ready_id()));
	__latency_fn_project_dependency_graph_append_dirty_projection(graph, __latency_fn_project_dependency_graph_make_dirty_projection(__latency_fn_structure_row_ids_none_id(), symbol, __latency_fn_project_dependency_graph_recompute_target_local_lowering_backend_refresh_id(), __latency_fn_project_dependency_graph_dirty_change_symbol_body_id(), __latency_fn_project_dependency_graph_dirty_scope_local_body_backend_owner_id(), __latency_fn_project_dependency_graph_dirty_reuse_public_surface_and_dependents_id(), __latency_fn_project_dependency_graph_dirty_status_body_only_id()));
	__latency_fn_project_dependency_graph_append_dirty_projection(graph, __latency_fn_project_dependency_graph_make_dirty_projection(__latency_fn_structure_row_ids_none_id(), symbol, __latency_fn_project_dependency_graph_recompute_target_dependent_resolution_later_id(), __latency_fn_project_dependency_graph_dirty_change_symbol_export_id(), __latency_fn_project_dependency_graph_dirty_scope_export_surface_and_dependents_id(), __latency_fn_project_dependency_graph_dirty_reuse_source_unit_rows_only_id(), __latency_fn_project_dependency_graph_dirty_status_export_surface_id()));
	__latency_fn_project_dependency_graph_append_dirty_fanout(graph, __latency_fn_project_dependency_graph_make_dirty_fanout(__latency_fn_structure_row_ids_none_id(), symbol->symbol_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_project_dependency_graph_recompute_target_backend_refresh_id(), __latency_fn_project_dependency_graph_dirty_change_symbol_body_id(), __latency_fn_project_dependency_graph_edge_kind_declared_in_source_id(), __latency_fn_project_dependency_graph_dirty_reuse_public_surface_and_dependents_id(), __latency_fn_project_dependency_graph_dirty_status_fanout_local_body_id()));
	vector_t<ProjectReferenceResolutionRow> dependentRows = required_cast<vector_t<ProjectReferenceResolutionRow>>(__latency_fn_project_dependency_graph_dependent_references_for_target(references, symbol->symbol_id));
	if (static_cast<bool>((php::count(dependentRows) > static_cast<int_t<> >(0)))) {
		auto& __latency_local_0 = dependentRows;
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto dependent = __latency_local_1.value_copy();
			__latency_fn_project_dependency_graph_append_dirty_fanout(graph, __latency_fn_project_dependency_graph_make_dirty_fanout(__latency_fn_structure_row_ids_none_id(), symbol->symbol_id, dependent->from_symbol_id, __latency_fn_project_dependency_graph_recompute_target_dependent_resolution_id(), __latency_fn_project_dependency_graph_dirty_change_symbol_export_id(), __latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id(), __latency_fn_project_dependency_graph_dirty_reuse_source_unit_rows_only_id(), __latency_fn_project_dependency_graph_dirty_status_fanout_resolved_reference_id()));
		}
	}
	else {
		__latency_fn_project_dependency_graph_append_dirty_fanout(graph, __latency_fn_project_dependency_graph_make_dirty_fanout(__latency_fn_structure_row_ids_none_id(), symbol->symbol_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_project_dependency_graph_recompute_target_dependent_resolution_from_graph_id(), __latency_fn_project_dependency_graph_dirty_change_symbol_export_id(), __latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id(), __latency_fn_project_dependency_graph_dirty_reuse_source_unit_rows_only_id(), __latency_fn_project_dependency_graph_dirty_status_no_dependents_id()));
	}
}

}

namespace scpp { extern const int __latency_lines_project_dependency_graph[]; }
namespace scpp {
void __latency_fn_project_dependency_graph_add_reference_edges(ProjectDependencyGraph& graph, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("project_dependency_graph::add_reference_edges", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_dependency_graph.phs", __latency_lines_project_dependency_graph[59]);
	auto __latency_local_0 = references->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto reference = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(reference->resolved_symbol_id) > static_cast<int_t<> >(0)))) {
			int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_project_dependency_graph_edge_status_blocked_unresolved_reference_id());
			if (static_cast<bool>(php::identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id())))) {
				statusId = __latency_fn_project_dependency_graph_edge_status_resolved_analyzer_only_id();
			}
			ProjectDependencyGraphEdgeRow edge = __latency_fn_project_dependency_graph_make_edge(__latency_fn_structure_row_ids_none_id(), reference->from_source_unit_id, reference->from_symbol_id, reference->resolved_source_unit_id, reference->resolved_symbol_id, reference->reference_id, __latency_fn_project_dependency_graph_contract_id_for_reference(contracts, reference->reference_id), __latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id(), cast<int_t<std::uint16_t>>(statusId));
			__latency_fn_project_dependency_graph_append_edge(graph, edge);
		}
	}
}

}
