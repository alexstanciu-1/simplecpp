#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ResidentDependencyEdgeSnapshotRow.hpp"
#include "__types/resident_dependency_edges.hpp"
#include "__callable/__latency_fn_resident_dependency_edges_append_from_graph.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_resident_dependency_edges_has_dependent_reference.hpp"
namespace scpp { extern const int __latency_lines_resident_dependency_edges[]; }
namespace scpp {
bool_t resident_dependency_edges::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_dependency_edges::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_dependency_edges[]; }
namespace scpp {
void __latency_fn_resident_dependency_edges_append_from_graph(shared_p<CompilerProjectRunReport>& report, ProjectDependencyGraph graph, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dependency_edges::append_from_graph", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dependency_edges.phs", __latency_lines_resident_dependency_edges[0]);
	auto __latency_local_0 = graph->edges;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto edge = __latency_local_1.value_copy();
		ResidentDependencyEdgeSnapshotRow row = ResidentDependencyEdgeSnapshotRow{};
		row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dependency_edge_snapshots));
		row->owner_run_id = ownerRunId;
		row->edge_id = edge->edge_id;
		row->from_source_unit_id = edge->from_source_unit_id;
		row->from_symbol_id = edge->from_symbol_id;
		row->to_source_unit_id = edge->to_source_unit_id;
		row->to_symbol_id = edge->to_symbol_id;
		row->reference_id = edge->reference_id;
		row->edge_kind_id = edge->edge_kind_id;
		row->status_id = edge->status_id;
		(void) report->resident_dependency_edge_snapshots.append(row);
	}
	report->resident_dependency_edge_snapshot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dependency_edge_snapshots));
}

}

namespace scpp { extern const int __latency_lines_resident_dependency_edges[]; }
namespace scpp {
bool_t __latency_fn_resident_dependency_edges_has_dependent_reference(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> targetSymbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_dependency_edges::has_dependent_reference", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dependency_edges.phs", __latency_lines_resident_dependency_edges[1]);
	auto __latency_local_0 = report->resident_dependency_edge_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto edge = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(edge->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(edge->to_symbol_id), cast<int_t<>>(targetSymbolId))) && (cast<int_t<>>(edge->from_symbol_id) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(edge->edge_kind_id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}
