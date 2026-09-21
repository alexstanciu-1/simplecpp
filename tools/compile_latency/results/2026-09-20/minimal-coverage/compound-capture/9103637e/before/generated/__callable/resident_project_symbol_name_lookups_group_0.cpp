#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentProjectSymbolNameLookupRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_project_symbol_name_lookups.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_status_hash_collision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_has_existing_hash_collision.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_name_match_count_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_materialized_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_row_from_name.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_append_row.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_has_existing_hash_collision.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_status_hash_collision_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_name_match_count_by_name_id.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_append_from_symbol_index.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_append_row.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_row_from_name.hpp"
#include "__callable/__latency_fn_resident_project_symbol_name_lookups_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
bool_t resident_project_symbol_name_lookups::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_project_symbol_name_lookups::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_project_symbol_name_lookups_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_project_symbol_name_lookups_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_project_symbol_name_lookups_status_hash_collision_id() {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::status_hash_collision_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_project_symbol_name_lookups_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[3]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_row_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> lookupId) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[4]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(lookupId, php::count(report->resident_project_symbol_name_lookups))))) {
		ResidentProjectSymbolNameLookupRow row = report->resident_project_symbol_name_lookups[__latency_fn_structure_row_ids_dense_index(lookupId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->lookup_id), cast<int_t<>>(lookupId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_project_symbol_name_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->lookup_id), cast<int_t<>>(lookupId)))) {
			return row;
		}
	}
	ResidentProjectSymbolNameLookupRow empty = ResidentProjectSymbolNameLookupRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
bool_t __latency_fn_resident_project_symbol_name_lookups_has_existing_hash_collision(shared_p<CompilerProjectRunReport> report, ResidentProjectSymbolNameLookupRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::has_existing_hash_collision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[5]);
	auto __latency_local_0 = report->resident_project_symbol_name_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(existing->owner_run_id), cast<int_t<>>(candidate->owner_run_id)) && php::identical(cast<int_t<>>(existing->name_hash), cast<int_t<>>(candidate->name_hash))) && php::identical(cast<int_t<>>(existing->name_length), cast<int_t<>>(candidate->name_length))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_row_from_name(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> nameId) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::row_from_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[6]);
	string_t name = required_cast<string_t>(__latency_fn_project_symbol_index_materialized_string(symbols->names, nameId, string_t("")));
	int_t<std::uint32_t> matchCount = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_function_name_match_count_by_name_id(symbols, nameId));
	ProjectSymbolIndexRow symbol = __latency_fn_project_symbol_index_function_symbol_by_name_id(symbols, nameId);
	ResidentProjectSymbolNameLookupRow row = ResidentProjectSymbolNameLookupRow{};
	row->owner_run_id = ownerRunId;
	row->name_hash = __latency_fn_source_buffers_content_hash32(name);
	row->name_length = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int(str::byte_length(name));
	row->match_count = matchCount;
	row->symbol_kind_id = __latency_fn_project_symbol_index_symbol_kind_function_id();
	row->status_id = __latency_fn_resident_project_symbol_name_lookups_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_project_symbol_name_lookups_blocked_reason_none_id();
	if (static_cast<bool>((php::identical(cast<int_t<>>(matchCount), static_cast<int_t<> >(1)) && (cast<int_t<>>(symbol->symbol_id) > static_cast<int_t<> >(0))))) {
		row->symbol_id = symbol->symbol_id;
		row->source_unit_id = symbol->source_unit_id;
		row->source_row_id = symbol->source_row_id;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
void __latency_fn_resident_project_symbol_name_lookups_append_row(shared_p<CompilerProjectRunReport>& report, ResidentProjectSymbolNameLookupRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[7]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_project_symbol_name_lookups_has_existing_hash_collision(report, row)))) {
		row->status_id = __latency_fn_resident_project_symbol_name_lookups_status_hash_collision_id();
	}
	row->lookup_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_project_symbol_name_lookups));
	(void) report->resident_project_symbol_name_lookups.append(row);
	report->resident_project_symbol_name_lookup_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int(php::count(report->resident_project_symbol_name_lookups));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_project_symbol_name_lookups_status_hash_collision_id())))) {
		report->resident_project_symbol_name_lookup_hash_collision_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(report->resident_project_symbol_name_lookup_hash_collision_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
		report->resident_project_symbol_name_lookup_unique_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(report->resident_project_symbol_name_lookup_unique_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>((cast<int_t<>>(row->match_count) > static_cast<int_t<> >(1)))) {
			report->resident_project_symbol_name_lookup_duplicate_name_count = __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((cast<int_t<>>(report->resident_project_symbol_name_lookup_duplicate_name_count) + static_cast<int_t<> >(1)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
void __latency_fn_resident_project_symbol_name_lookups_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[8]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_project_symbol_name_lookups_uint32_from_int(rowCount), __latency_fn_resident_project_symbol_name_lookups_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentProjectSymbolNameLookupRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_project_symbol_name_lookups[]; }
namespace scpp {
void __latency_fn_resident_project_symbol_name_lookups_append_from_symbol_index(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_project_symbol_name_lookups::append_from_symbol_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_project_symbol_name_lookups.phs", __latency_lines_resident_project_symbol_name_lookups[9]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_project_symbol_name_lookups));
	int_t<> nameIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((nameIndex < php::count(symbols->names)))) {
		int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_project_symbol_name_lookups_uint32_from_int((nameIndex + static_cast<int_t<> >(1))));
		int_t<std::uint32_t> matchCount = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_function_name_match_count_by_name_id(symbols, nameId));
		if (static_cast<bool>((cast<int_t<>>(matchCount) > static_cast<int_t<> >(0)))) {
			__latency_fn_resident_project_symbol_name_lookups_append_row(report, __latency_fn_resident_project_symbol_name_lookups_row_from_name(symbols, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(nameId)));
		}
		nameIndex = (nameIndex + static_cast<int_t<> >(1));
	}
	__latency_fn_resident_project_symbol_name_lookups_append_memory_estimate(report, (php::count(report->resident_project_symbol_name_lookups) - startCount));
}

}
