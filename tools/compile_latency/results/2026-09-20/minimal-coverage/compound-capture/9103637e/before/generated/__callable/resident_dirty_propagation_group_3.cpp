#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentDirtyBudgetRow.hpp"
#include "__types/ResidentDirtyProcessedRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_default_budget_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_budget_active_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_budget.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_row_from_queue_for_processed.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_processed_status_can_select.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_processed_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_upgraded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_processed_entity_matches_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_processed_for_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_active_processed_position_for_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_processed_entity_matches_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_processed_status_can_select.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_processed.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_skipped_duplicate_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_upgraded_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_active_processed_position_for_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_processed.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_has_processed_for_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_process_queue_row.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_row_from_queue_for_processed.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_processed_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_skipped_duplicate_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_superseded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_upgraded_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_queue_count_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
ResidentDirtyBudgetRow __latency_fn_resident_dirty_propagation_default_budget_row(int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::default_budget_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[38]);
	ResidentDirtyBudgetRow row = ResidentDirtyBudgetRow{};
	row->owner_run_id = ownerRunId;
	row->max_queue_rows = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(100000));
	row->max_processed_rows = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(100000));
	row->max_fanout_refs = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(100000));
	row->max_scratch_bytes = __latency_fn_structure_row_ids_uint32_from_int(((static_cast<int_t<> >(16) * static_cast<int_t<> >(1024)) * static_cast<int_t<> >(1024)));
	row->max_fallback_scans = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
	row->max_depth = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(32));
	row->status_id = __latency_fn_resident_dirty_propagation_status_budget_active_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_budget(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_budget", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[39]);
	row->budget_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dirty_budgets));
	(void) report->resident_dirty_budgets.append(row);
	report->resident_dirty_budget_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dirty_budgets));
	return row->budget_id;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
ResidentDirtyProcessedRow __latency_fn_resident_dirty_propagation_row_from_queue_for_processed(ResidentDirtyQueueRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::row_from_queue_for_processed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[40]);
	ResidentDirtyProcessedRow processed = ResidentDirtyProcessedRow{};
	processed->owner_run_id = row->owner_run_id;
	processed->source_unit_id = row->source_unit_id;
	processed->symbol_id = row->symbol_id;
	processed->queue_id = row->queue_id;
	processed->reason_change_id = row->reason_change_id;
	processed->stage_mask = row->stage_mask;
	processed->entity_kind_id = row->entity_kind_id;
	processed->dirty_reason_id = row->dirty_reason_id;
	processed->reason_strength_id = row->reason_strength_id;
	return processed;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_processed_status_can_select(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::processed_status_can_select", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[41]);
	return (php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_processed_id())) || php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_upgraded_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_processed_entity_matches_queue(ResidentDirtyProcessedRow processed, ResidentDirtyQueueRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::processed_entity_matches_queue", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[42]);
	return (((php::identical(cast<int_t<>>(processed->owner_run_id), cast<int_t<>>(row->owner_run_id)) && php::identical(cast<int_t<>>(processed->source_unit_id), cast<int_t<>>(row->source_unit_id))) && php::identical(cast<int_t<>>(processed->symbol_id), cast<int_t<>>(row->symbol_id))) && php::identical(cast<int_t<>>(processed->entity_kind_id), cast<int_t<>>(row->entity_kind_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
bool_t __latency_fn_resident_dirty_propagation_has_processed_for_queue(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> queueId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::has_processed_for_queue", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[43]);
	auto __latency_local_0 = report->resident_dirty_processed_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto processed = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(processed->queue_id), cast<int_t<>>(queueId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<> __latency_fn_resident_dirty_propagation_active_processed_position_for_queue(shared_p<CompilerProjectRunReport> report, ResidentDirtyQueueRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::active_processed_position_for_queue", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[44]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((position < php::count(report->resident_dirty_processed_rows)))) {
		ResidentDirtyProcessedRow processed = report->resident_dirty_processed_rows[position];
		if (static_cast<bool>((__latency_fn_resident_dirty_propagation_processed_status_can_select(processed->status_id) && __latency_fn_resident_dirty_propagation_processed_entity_matches_queue(processed, row)))) {
			return position;
		}
		position = (position + static_cast<int_t<> >(1));
	}
	return (-static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_processed(shared_p<CompilerProjectRunReport>& report, ResidentDirtyProcessedRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_processed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[45]);
	row->processed_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dirty_processed_rows));
	(void) report->resident_dirty_processed_rows.append(row);
	report->resident_dirty_processed_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dirty_processed_rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_skipped_duplicate_id())))) {
		report->resident_dirty_processed_skipped_duplicate_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_processed_skipped_duplicate_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_status_upgraded_id())))) {
		report->resident_dirty_processed_upgraded_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_processed_upgraded_count) + static_cast<int_t<> >(1)));
	}
	return row->processed_id;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
void __latency_fn_resident_dirty_propagation_process_queue_row(shared_p<CompilerProjectRunReport>& report, ResidentDirtyQueueRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::process_queue_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[46]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_dirty_propagation_has_processed_for_queue(report, row->queue_id)))) {
		return;
	}
	ResidentDirtyProcessedRow processed = __latency_fn_resident_dirty_propagation_row_from_queue_for_processed(row);
	int_t<> activePosition = required_cast<int_t<>>(__latency_fn_resident_dirty_propagation_active_processed_position_for_queue(report, row));
	if (static_cast<bool>(php::condition_truthy((activePosition >= static_cast<int_t<> >(0))))) {
		ResidentDirtyProcessedRow previous = report->resident_dirty_processed_rows[activePosition];
		if (static_cast<bool>(php::condition_truthy((cast<int_t<>>(previous->reason_strength_id) >= cast<int_t<>>(row->reason_strength_id))))) {
			processed->duplicate_of_processed_id = previous->processed_id;
			processed->status_id = __latency_fn_resident_dirty_propagation_status_skipped_duplicate_id();
			__latency_fn_resident_dirty_propagation_append_processed(report, processed);
			return;
		}
		previous->status_id = __latency_fn_resident_dirty_propagation_status_superseded_id();
		report->resident_dirty_processed_rows[activePosition] = previous;
		processed->upgraded_from_processed_id = previous->processed_id;
		processed->status_id = __latency_fn_resident_dirty_propagation_status_upgraded_id();
		__latency_fn_resident_dirty_propagation_append_processed(report, processed);
		return;
	}
	processed->status_id = __latency_fn_resident_dirty_propagation_status_processed_id();
	__latency_fn_resident_dirty_propagation_append_processed(report, processed);
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_queue_count_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::queue_count_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[47]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}
