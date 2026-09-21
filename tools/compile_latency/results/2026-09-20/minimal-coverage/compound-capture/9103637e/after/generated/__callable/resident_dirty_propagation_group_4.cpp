#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentDirtyBailoutRow.hpp"
#include "__types/ResidentDirtyBudgetRow.hpp"
#include "__types/ResidentDirtyLimitCheckRow.hpp"
#include "__types/ResidentDirtyProcessedRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__types/ResidentReverseDependencyIndexRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_max_depth_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_symbol_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_by_provider.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_queue_count_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_scratch_bytes_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_depth_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_max_depth_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_observed_count_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_queue_count_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_scratch_bytes_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_count_for_limit_kind.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_depth_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_budget_for_owner.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_bailout_for_budget.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_limit_checks_for_budget.hpp"
namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_max_depth_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::max_depth_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[48]);
	int_t<> maxDepth = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(row->depth) > maxDepth)))) {
			maxDepth = cast<int_t<>>(row->depth);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxDepth);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::fanout_ref_count_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[49]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->entity_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_entity_kind_symbol_id()))) && php::identical(cast<int_t<>>(row->dirty_reason_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id()))))) {
			ResidentReverseDependencyIndexRow index = __latency_fn_resident_reverse_dependency_indexes_index_by_provider(report, ownerRunId, row->symbol_id);
			if (static_cast<bool>((cast<int_t<>>(index->index_id) > static_cast<int_t<> >(0)))) {
				count = (count + cast<int_t<>>(index->ref_count));
			}
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_scratch_bytes_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::scratch_bytes_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[50]);
	int_t<> queueCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_resident_dirty_propagation_queue_count_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))));
	int_t<> fanoutCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))));
	int_t<> bytes = required_cast<int_t<>>((((queueCount * static_cast<int_t<> >(sizeof(ResidentDirtyQueueRow))) + (queueCount * static_cast<int_t<> >(sizeof(ResidentDirtyProcessedRow)))) + (fanoutCount * static_cast<int_t<> >(sizeof(ResidentReverseDependencyLookupRow)))));
	return __latency_fn_structure_row_ids_uint32_from_int(bytes);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_observed_count_for_limit_kind(shared_p<CompilerProjectRunReport> report, ResidentDirtyBudgetRow budget, int_t<std::uint16_t> limitKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::observed_count_for_limit_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[51]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id())))) {
		return __latency_fn_resident_dirty_propagation_queue_count_for_owner(report, budget->owner_run_id);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id())))) {
		return __latency_fn_resident_dirty_propagation_queue_count_for_owner(report, budget->owner_run_id);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())))) {
		return __latency_fn_resident_dirty_propagation_fanout_ref_count_for_owner(report, budget->owner_run_id);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_depth_id())))) {
		return __latency_fn_resident_dirty_propagation_max_depth_for_owner(report, budget->owner_run_id);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id())))) {
		return __latency_fn_resident_dirty_propagation_scratch_bytes_for_owner(report, budget->owner_run_id);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id())))) {
		return report->resident_reverse_dependency_index_fallback_scan_count;
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_limit_count_for_limit_kind(ResidentDirtyBudgetRow budget, int_t<std::uint16_t> limitKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::limit_count_for_limit_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[52]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_queue_rows_id())))) {
		return budget->max_queue_rows;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_processed_rows_id())))) {
		return budget->max_processed_rows;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fanout_refs_id())))) {
		return budget->max_fanout_refs;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_depth_id())))) {
		return __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(budget->max_depth));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_scratch_bytes_id())))) {
		return budget->max_scratch_bytes;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(limitKindId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_limit_kind_fallback_scans_id())))) {
		return budget->max_fallback_scans;
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
ResidentDirtyBudgetRow __latency_fn_resident_dirty_propagation_budget_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::budget_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[53]);
	int_t<> position = required_cast<int_t<>>((php::count(report->resident_dirty_budgets) - static_cast<int_t<> >(1)));
	while (static_cast<bool>(php::condition_truthy((position >= static_cast<int_t<> >(0))))) {
		ResidentDirtyBudgetRow row = report->resident_dirty_budgets[position];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return row;
		}
		position = (position - static_cast<int_t<> >(1));
	}
	ResidentDirtyBudgetRow row = ResidentDirtyBudgetRow{};
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_has_bailout_for_budget(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> budgetId, int_t<std::uint16_t> limitKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::has_bailout_for_budget", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[54]);
	auto __latency_local_0 = report->resident_dirty_bailouts;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->budget_id), cast<int_t<>>(budgetId)) && php::identical(cast<int_t<>>(row->limit_kind_id), cast<int_t<>>(limitKindId))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_has_limit_checks_for_budget(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> budgetId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::has_limit_checks_for_budget", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[55]);
	auto __latency_local_0 = report->resident_dirty_limit_checks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->budget_id), cast<int_t<>>(budgetId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}
