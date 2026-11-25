#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentReverseDependencyIndexRefRow.hpp"
#include "__types/ResidentReverseDependencyIndexRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/resident_reverse_dependency_indexes.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_by_provider.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_ref_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_lookup_row_from_ref.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_append_lookup.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_no_dependent_lookup_row.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_append_lookup_for_symbol_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_by_provider.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_lookup_row_from_ref.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_ref_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
ResidentReverseDependencyIndexRow __latency_fn_resident_reverse_dependency_indexes_index_by_provider(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> providerSymbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::index_by_provider", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[10]);
	auto __latency_local_0 = report->resident_reverse_dependency_indexes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->provider_symbol_id), cast<int_t<>>(providerSymbolId))))) {
			return row;
		}
	}
	ResidentReverseDependencyIndexRow empty = ResidentReverseDependencyIndexRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
ResidentReverseDependencyIndexRefRow __latency_fn_resident_reverse_dependency_indexes_ref_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> refId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::ref_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[11]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(refId, php::count(report->resident_reverse_dependency_index_refs))))) {
		ResidentReverseDependencyIndexRefRow row = report->resident_reverse_dependency_index_refs[__latency_fn_structure_row_ids_dense_index(refId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->ref_id), cast<int_t<>>(refId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_reverse_dependency_index_refs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->ref_id), cast<int_t<>>(refId)))) {
			return row;
		}
	}
	ResidentReverseDependencyIndexRefRow empty = ResidentReverseDependencyIndexRefRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
ResidentReverseDependencyLookupRow __latency_fn_resident_reverse_dependency_indexes_lookup_row_from_ref(ResidentReverseDependencyIndexRefRow ref, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::lookup_row_from_ref", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[12]);
	ResidentReverseDependencyLookupRow row = ResidentReverseDependencyLookupRow{};
	row->owner_run_id = change->owner_run_id;
	row->provider_source_unit_id = change->source_unit_id;
	row->provider_symbol_id = change->symbol_id;
	row->consumer_source_unit_id = ref->consumer_source_unit_id;
	row->consumer_symbol_id = ref->consumer_symbol_id;
	row->edge_snapshot_id = ref->edge_snapshot_id;
	row->reason_change_id = change->change_id;
	row->lookup_kind_id = __latency_fn_resident_reverse_dependencies_lookup_kind_symbol_provider_id();
	row->edge_kind_id = ref->edge_kind_id;
	row->status_id = __latency_fn_resident_reverse_dependencies_status_dependent_found_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependency_indexes[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_reverse_dependency_indexes_append_lookup_for_symbol_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependency_indexes::append_lookup_for_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependency_indexes.phs", __latency_lines_resident_reverse_dependency_indexes[13]);
	ResidentReverseDependencyIndexRow index = __latency_fn_resident_reverse_dependency_indexes_index_by_provider(report, change->owner_run_id, change->symbol_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(index->index_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	report->resident_reverse_dependency_index_lookup_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_index_lookup_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(index->ref_count), static_cast<int_t<> >(0)))) {
		report->resident_reverse_dependency_index_no_dependent_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_index_no_dependent_count) + static_cast<int_t<> >(1)));
		return __latency_fn_resident_reverse_dependencies_append_lookup(report, __latency_fn_resident_reverse_dependencies_no_dependent_lookup_row(change));
	}
	int_t<std::uint32_t> firstLookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<> refOffset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((refOffset < cast<int_t<>>(index->ref_count)))) {
		int_t<std::uint32_t> refId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(index->first_ref_id) + refOffset)));
		ResidentReverseDependencyIndexRefRow ref = __latency_fn_resident_reverse_dependency_indexes_ref_by_id(report, cast<int_t<std::uint32_t>>(refId));
		if (static_cast<bool>((cast<int_t<>>(ref->ref_id) > static_cast<int_t<> >(0)))) {
			int_t<std::uint32_t> lookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_reverse_dependencies_append_lookup(report, __latency_fn_resident_reverse_dependency_indexes_lookup_row_from_ref(ref, change)));
			if (static_cast<bool>(php::identical(cast<int_t<>>(firstLookupId), static_cast<int_t<> >(0)))) {
				firstLookupId = cast<int_t<std::uint32_t>>(lookupId);
			}
			report->resident_reverse_dependency_index_hit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_reverse_dependency_index_hit_count) + static_cast<int_t<> >(1)));
		}
		refOffset = (refOffset + static_cast<int_t<> >(1));
	}
	return cast<int_t<std::uint32_t>>(firstLookupId);
}

}
