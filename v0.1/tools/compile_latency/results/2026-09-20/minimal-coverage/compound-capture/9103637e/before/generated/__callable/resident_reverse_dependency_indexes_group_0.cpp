#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentDependencyEdgeSnapshotRow.hpp"
#include "__types/ResidentReverseDependencyIndexRefRow.hpp"
#include "__types/ResidentReverseDependencyIndexRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_reverse_dependency_indexes.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_status_no_consumers_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_ref_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_has_index_for_owner.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_row_from_symbol.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_status_no_consumers_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_edge_matches_index.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_ref_from_edge.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_ref_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_memory_estimate.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_for_owner.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_index.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_ref_from_edge.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_edge_matches_index.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_has_index_for_owner.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_row_from_symbol.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
bool_t resident_reverse_dependency_indexes::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_reverse_dependency_indexes::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependency_indexes_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependency_indexes_status_no_consumers_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::status_no_consumers_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependency_indexes_ref_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::ref_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
bool_t __latency_fn_resident_reverse_dependency_indexes_has_index_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::has_index_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[3]);
	auto __latency_local_0 = report->resident_reverse_dependency_indexes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
ResidentReverseDependencyIndexRow __latency_fn_resident_reverse_dependency_indexes_index_row_from_symbol(ResidentSymbolDefinitionSnapshotRow symbol) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::index_row_from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[4]);
	ResidentReverseDependencyIndexRow row = ResidentReverseDependencyIndexRow{};
	row->owner_run_id = symbol->owner_run_id;
	row->provider_source_unit_id = symbol->source_unit_id;
	row->provider_symbol_id = symbol->symbol_id;
	row->first_ref_id = __latency_fn_structure_row_ids_none_id();
	row->ref_count = __latency_fn_structure_row_ids_none_id();
	row->edge_kind_id = __latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id();
	row->status_id = __latency_fn_resident_reverse_dependency_indexes_status_no_consumers_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependency_indexes_append_index(shared_p<CompilerProjectRunReport>& report, ResidentReverseDependencyIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::append_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[5]);
	row->index_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_reverse_dependency_indexes));
	(void) report->resident_reverse_dependency_indexes.append(row);
	report->resident_reverse_dependency_index_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_reverse_dependency_indexes));
	return row->index_id;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
bool_t __latency_fn_resident_reverse_dependency_indexes_edge_matches_index(ResidentDependencyEdgeSnapshotRow edge, ResidentReverseDependencyIndexRow index) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::edge_matches_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[6]);
	return ((((php::identical(cast<int_t<>>(edge->owner_run_id), cast<int_t<>>(index->owner_run_id)) && php::identical(cast<int_t<>>(edge->to_symbol_id), cast<int_t<>>(index->provider_symbol_id))) && php::identical(cast<int_t<>>(edge->to_source_unit_id), cast<int_t<>>(index->provider_source_unit_id))) && (cast<int_t<>>(edge->from_symbol_id) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(edge->edge_kind_id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependency_indexes_append_ref_from_edge(shared_p<CompilerProjectRunReport>& report, ResidentReverseDependencyIndexRow index, ResidentDependencyEdgeSnapshotRow edge) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::append_ref_from_edge", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[7]);
	ResidentReverseDependencyIndexRefRow row = ResidentReverseDependencyIndexRefRow{};
	row->ref_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_reverse_dependency_index_refs));
	row->owner_run_id = index->owner_run_id;
	row->reverse_index_id = index->index_id;
	row->provider_source_unit_id = index->provider_source_unit_id;
	row->provider_symbol_id = index->provider_symbol_id;
	row->consumer_source_unit_id = edge->from_source_unit_id;
	row->consumer_symbol_id = edge->from_symbol_id;
	row->edge_snapshot_id = edge->snapshot_id;
	row->edge_kind_id = edge->edge_kind_id;
	row->status_id = __latency_fn_resident_reverse_dependency_indexes_ref_status_ready_id();
	(void) report->resident_reverse_dependency_index_refs.append(row);
	report->resident_reverse_dependency_index_ref_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_reverse_dependency_index_refs));
	return row->ref_id;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
void __latency_fn_resident_reverse_dependency_indexes_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> indexRowCount, int_t<> refRowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[8]);
	int_t<> rowCount = required_cast<int_t<>>((indexRowCount + refRowCount));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> bytes = required_cast<int_t<>>(((indexRowCount * static_cast<int_t<> >(sizeof(ResidentReverseDependencyIndexRow))) + (refRowCount * static_cast<int_t<> >(sizeof(ResidentReverseDependencyIndexRefRow)))));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(rowCount), __latency_fn_structure_row_ids_uint32_from_int(bytes), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
void __latency_fn_resident_reverse_dependency_indexes_append_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::append_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[9]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_reverse_dependency_indexes_has_index_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startIndexCount = required_cast<int_t<>>(php::count(report->resident_reverse_dependency_indexes));
	int_t<> startRefCount = required_cast<int_t<>>(php::count(report->resident_reverse_dependency_index_refs));
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(symbol->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_reverse_dependency_indexes_append_index(report, __latency_fn_resident_reverse_dependency_indexes_index_row_from_symbol(symbol));
		}
	}
	int_t<> indexPosition = required_cast<int_t<>>(startIndexCount);
	while (static_cast<bool>((indexPosition < php::count(report->resident_reverse_dependency_indexes)))) {
		ResidentReverseDependencyIndexRow indexRow = report->resident_reverse_dependency_indexes[indexPosition];
		if (static_cast<bool>(php::identical(cast<int_t<>>(indexRow->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			int_t<std::uint32_t> firstRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
			int_t<> refCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
			auto __latency_local_2 = report->resident_dependency_edge_snapshots;
			for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
				auto edge = __latency_local_3.value_copy();
				if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_reverse_dependency_indexes_edge_matches_index(edge, indexRow)))) {
					int_t<std::uint32_t> refId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_reverse_dependency_indexes_append_ref_from_edge(report, indexRow, edge));
					if (static_cast<bool>(php::identical(cast<int_t<>>(firstRefId), static_cast<int_t<> >(0)))) {
						firstRefId = cast<int_t<std::uint32_t>>(refId);
					}
					refCount = (refCount + static_cast<int_t<> >(1));
				}
			}
			indexRow->first_ref_id = firstRefId;
			indexRow->ref_count = __latency_fn_structure_row_ids_uint32_from_int(refCount);
			if (static_cast<bool>((refCount > static_cast<int_t<> >(0)))) {
				indexRow->status_id = __latency_fn_resident_reverse_dependency_indexes_status_ready_id();
			}
			report->resident_reverse_dependency_indexes[indexPosition] = indexRow;
		}
		indexPosition = (indexPosition + static_cast<int_t<> >(1));
	}
	__latency_fn_resident_reverse_dependency_indexes_append_memory_estimate(report, (php::count(report->resident_reverse_dependency_indexes) - startIndexCount), (php::count(report->resident_reverse_dependency_index_refs) - startRefCount));
}

}
