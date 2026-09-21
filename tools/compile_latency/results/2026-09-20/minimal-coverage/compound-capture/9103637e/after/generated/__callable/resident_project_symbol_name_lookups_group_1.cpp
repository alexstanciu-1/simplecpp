#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentProjectSymbolNameLookupRow.hpp"
#include "__types/resident_project_symbol_name_lookups.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_lookup_slot_count.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_slot_index.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_build_lookup_slots.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_slot_index.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_has_lookup_hash_length_collision.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_has_lookup_hash_length_collision.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_lookup_by_hash_and_length.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_row_by_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_slot_index.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<> __latency_fn_resident_project_symbol_name_lookups_lookup_slot_count(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::lookup_slot_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[10]);
	int_t<> rowCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_project_symbol_name_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			rowCount = (rowCount + static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	int_t<> slotCount = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((slotCount < ((rowCount * static_cast<int_t<> >(2)) + static_cast<int_t<> >(1))))) {
		slotCount = (slotCount * static_cast<int_t<> >(2));
	}
	return slotCount;
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<> __latency_fn_resident_project_symbol_name_lookups_slot_index(int_t<std::uint32_t> nameHash, int_t<std::uint32_t> nameLength, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::slot_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[11]);
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return ((cast<int_t<>>(nameHash) + (cast<int_t<>>(nameLength) * static_cast<int_t<> >(16777619))) % slotCount);
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
void __latency_fn_resident_project_symbol_name_lookups_build_lookup_slots(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& lookupIds) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::build_lookup_slots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[12]);
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(lookupIds, slotCount);
	report->resident_project_symbol_name_lookup_slot_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(report->resident_project_symbol_name_lookup_slot_count) + slotCount));
	auto __latency_local_0 = report->resident_project_symbol_name_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId))))) {
			continue;
		}
		int_t<> slot = required_cast<int_t<>>(__latency_fn_resident_project_symbol_name_lookups_slot_index(row->name_hash, row->name_length, slotCount));
		while (static_cast<bool>((cast<int_t<>>(lookupIds.at(slot)) > static_cast<int_t<> >(0)))) {
			slot = ((slot + static_cast<int_t<> >(1)) % slotCount);
		}
		lookupIds.at(slot) = row->lookup_id;
	}
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
bool_t __latency_fn_resident_project_symbol_name_lookups_has_lookup_hash_length_collision(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> lookupId, int_t<std::uint32_t> nameHash, int_t<std::uint32_t> nameLength) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::has_lookup_hash_length_collision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[13]);
	auto __latency_local_0 = report->resident_project_symbol_name_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::not_identical(cast<int_t<>>(row->lookup_id), cast<int_t<>>(lookupId))) && php::identical(cast<int_t<>>(row->name_hash), cast<int_t<>>(nameHash))) && php::identical(cast<int_t<>>(row->name_length), cast<int_t<>>(nameLength))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_lookup_by_hash_and_length(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> lookupReport, vector_t<int_t<std::uint32_t>>& lookupIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> nameHash, int_t<std::uint32_t> nameLength) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::lookup_by_hash_and_length", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[14]);
	int_t<> slotCount = required_cast<int_t<>>(php::count(lookupIds));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		metricsReport->resident_project_symbol_name_lookup_fallback_scan_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
		ResidentProjectSymbolNameLookupRow empty = ResidentProjectSymbolNameLookupRow{};
		return empty;
	}
	int_t<> slot = required_cast<int_t<>>(__latency_fn_resident_project_symbol_name_lookups_slot_index(cast<int_t<std::uint32_t>>(nameHash), cast<int_t<std::uint32_t>>(nameLength), slotCount));
	int_t<> probeCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((probeCount < slotCount))) {
		metricsReport->resident_project_symbol_name_lookup_probe_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_probe_count) + static_cast<int_t<> >(1)));
		int_t<std::uint32_t> lookupId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(lookupIds.at(slot)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(lookupId), static_cast<int_t<> >(0)))) {
			metricsReport->resident_project_symbol_name_lookup_miss_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_miss_count) + static_cast<int_t<> >(1)));
			ResidentProjectSymbolNameLookupRow empty = ResidentProjectSymbolNameLookupRow{};
			return empty;
		}
		ResidentProjectSymbolNameLookupRow row = __latency_fn_resident_project_symbol_name_lookups_row_by_id(lookupReport, cast<int_t<std::uint32_t>>(lookupId));
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->name_hash), cast<int_t<>>(nameHash))) && php::identical(cast<int_t<>>(row->name_length), cast<int_t<>>(nameLength))))) {
			if (static_cast<bool>(((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_hash_collision_count) > static_cast<int_t<> >(0)) && __latency_fn_resident_project_symbol_name_lookups_has_lookup_hash_length_collision(lookupReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(lookupId), cast<int_t<std::uint32_t>>(nameHash), cast<int_t<std::uint32_t>>(nameLength))))) {
				metricsReport->resident_project_symbol_name_lookup_fallback_scan_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
				metricsReport->resident_project_symbol_name_lookup_miss_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_miss_count) + static_cast<int_t<> >(1)));
				ResidentProjectSymbolNameLookupRow empty = ResidentProjectSymbolNameLookupRow{};
				return empty;
			}
			metricsReport->resident_project_symbol_name_lookup_hit_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_hit_count) + static_cast<int_t<> >(1)));
			return row;
		}
		probeCount = (probeCount + static_cast<int_t<> >(1));
		slot = ((slot + static_cast<int_t<> >(1)) % slotCount);
	}
	metricsReport->resident_project_symbol_name_lookup_miss_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(metricsReport->resident_project_symbol_name_lookup_miss_count) + static_cast<int_t<> >(1)));
	ResidentProjectSymbolNameLookupRow empty = ResidentProjectSymbolNameLookupRow{};
	return empty;
}

}
