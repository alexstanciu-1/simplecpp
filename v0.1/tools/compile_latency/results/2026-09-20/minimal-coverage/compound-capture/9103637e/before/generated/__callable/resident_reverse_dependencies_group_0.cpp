#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentDependencyEdgeSnapshotRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/resident_reverse_dependencies.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_append_lookup.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_row_from_edge.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_no_dependent_lookup_row.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_append_for_symbol_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_append_lookup.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_row_from_edge.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_no_dependent_lookup_row.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_lookup_for_symbol_change.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_matches_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_hit_count_for_symbol.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
bool_t resident_reverse_dependencies::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_reverse_dependencies::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::lookup_kind_symbol_provider_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependencies_status_dependent_found_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::status_dependent_found_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_reverse_dependencies_status_no_dependents_id() {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::status_no_dependents_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependencies_append_lookup(shared_p<CompilerProjectRunReport>& report, ResidentReverseDependencyLookupRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::append_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[3]);
	row->lookup_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_reverse_dependency_lookups));
	(void) report->resident_reverse_dependency_lookups.append(row);
	report->resident_reverse_dependency_lookup_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_reverse_dependency_lookups));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_dependent_found_id())))) {
		report->resident_reverse_dependency_hit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_hit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_no_dependents_id())))) {
		report->resident_reverse_dependency_no_dependent_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_no_dependent_count) + static_cast<int_t<> >(1)));
	}
	return row->lookup_id;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
ResidentReverseDependencyLookupRow __latency_fn_resident_reverse_dependencies_lookup_row_from_edge(ResidentDependencyEdgeSnapshotRow edge, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::lookup_row_from_edge", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[4]);
	ResidentReverseDependencyLookupRow row = ResidentReverseDependencyLookupRow{};
	row->owner_run_id = change->owner_run_id;
	row->provider_source_unit_id = change->source_unit_id;
	row->provider_symbol_id = change->symbol_id;
	row->consumer_source_unit_id = edge->from_source_unit_id;
	row->consumer_symbol_id = edge->from_symbol_id;
	row->edge_snapshot_id = edge->snapshot_id;
	row->reason_change_id = change->change_id;
	row->lookup_kind_id = __latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id();
	row->edge_kind_id = edge->edge_kind_id;
	row->status_id = __latency_fn_resident_reverse_dependencies_status_dependent_found_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
ResidentReverseDependencyLookupRow __latency_fn_resident_reverse_dependencies_no_dependent_lookup_row(ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::no_dependent_lookup_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[5]);
	ResidentReverseDependencyLookupRow row = ResidentReverseDependencyLookupRow{};
	row->owner_run_id = change->owner_run_id;
	row->provider_source_unit_id = change->source_unit_id;
	row->provider_symbol_id = change->symbol_id;
	row->reason_change_id = change->change_id;
	row->lookup_kind_id = __latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id();
	row->edge_kind_id = __latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id();
	row->status_id = __latency_fn_resident_reverse_dependencies_status_no_dependents_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependencies_append_for_symbol_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::append_for_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[6]);
	int_t<std::uint32_t> indexedLookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_reverse_dependency_indexes_append_lookup_for_symbol_change(report, change));
	if (static_cast<bool>((cast<int_t<>>(indexedLookupId) > static_cast<int_t<> >(0)))) {
		return cast<int_t<std::uint32_t>>(indexedLookupId);
	}
	report->resident_reverse_dependency_index_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_index_fallback_scan_count) + static_cast<int_t<> >(1)));
	int_t<> found = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> firstLookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	auto __latency_local_0 = report->resident_dependency_edge_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto edge = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(edge->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(edge->to_symbol_id), cast<int_t<>>(change->symbol_id))) && (cast<int_t<>>(edge->from_symbol_id) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(edge->edge_kind_id), cast<int_t<>>(__latency_fn_project_dependency_graph_edge_kind_resolved_symbol_reference_id()))))) {
			int_t<std::uint32_t> lookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_reverse_dependencies_append_lookup(report, __latency_fn_resident_reverse_dependencies_lookup_row_from_edge(edge, change)));
			if (static_cast<bool>(php::identical(cast<int_t<>>(firstLookupId), static_cast<int_t<> >(0)))) {
				firstLookupId = cast<int_t<std::uint32_t>>(lookupId);
			}
			found = (found + static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>(php::identical(found, static_cast<int_t<> >(0)))) {
		firstLookupId = __latency_fn_resident_reverse_dependencies_append_lookup(report, __latency_fn_resident_reverse_dependencies_no_dependent_lookup_row(change));
	}
	return cast<int_t<std::uint32_t>>(firstLookupId);
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
bool_t __latency_fn_resident_reverse_dependencies_lookup_matches_change(ResidentReverseDependencyLookupRow lookup, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::lookup_matches_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[7]);
	return (__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(lookup, change) && php::identical(cast<int_t<>>(lookup->status_id), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_dependent_found_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
bool_t __latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(ResidentReverseDependencyLookupRow lookup, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::lookup_belongs_to_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[8]);
	return ((php::identical(cast<int_t<>>(lookup->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(lookup->provider_symbol_id), cast<int_t<>>(change->symbol_id))) && php::identical(cast<int_t<>>(lookup->reason_change_id), cast<int_t<>>(change->change_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependencies_hit_count_for_symbol(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> providerSymbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::hit_count_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[9]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_reverse_dependency_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto lookup = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(lookup->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(lookup->provider_symbol_id), cast<int_t<>>(providerSymbolId))) && php::identical(cast<int_t<>>(lookup->status_id), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_dependent_found_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}
