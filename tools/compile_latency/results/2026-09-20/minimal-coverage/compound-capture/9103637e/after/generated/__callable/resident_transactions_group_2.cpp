#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentIncrementalFileChangeRow.hpp"
#include "__types/ResidentIncrementalTransactionRow.hpp"
#include "__types/ResidentProjectSnapshotRow.hpp"
#include "__types/resident_snapshots.hpp"
#include "__callable/__latency_fn_resident_transactions_append_cold_from_snapshot.hpp"
#include "__callable/__latency_fn_resident_transactions_append_transaction_with_change.hpp"
#include "__callable/__latency_fn_resident_transactions_change_all_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_all_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_empty_snapshot.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_make_file_change.hpp"
#include "__callable/__latency_fn_resident_transactions_make_transaction.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_cold_all_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_status_classified_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_changed_field_count.hpp"
#include "__callable/__latency_fn_resident_snapshots_equals.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_by_owner_run_id.hpp"
#include "__callable/__latency_fn_resident_transactions_append_from_snapshot_comparison.hpp"
#include "__callable/__latency_fn_resident_transactions_append_transaction_with_change.hpp"
#include "__callable/__latency_fn_resident_transactions_change_changed_aggregate_id.hpp"
#include "__callable/__latency_fn_resident_transactions_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_transactions_change_no_previous_id.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_all_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_changed_id.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_none_id.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_changed_id.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_no_change_id.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_no_previous_id.hpp"
#include "__callable/__latency_fn_resident_transactions_make_file_change.hpp"
#include "__callable/__latency_fn_resident_transactions_make_transaction.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_blocked_changed_id.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_ready_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_changed_aggregate_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_change_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_previous_id.hpp"
#include "__callable/__latency_fn_resident_transactions_status_classified_id.hpp"
#include "__callable/__latency_fn_resident_transactions_status_missing_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
void __latency_fn_resident_transactions_append_cold_from_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentProjectSnapshotRow current) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::append_cold_from_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[26]);
	ResidentProjectSnapshotRow previous = __latency_fn_resident_transactions_empty_snapshot();
	ResidentIncrementalTransactionRow transaction = __latency_fn_resident_transactions_make_transaction(current, previous, __latency_fn_resident_transactions_scenario_cold_all_new_id(), __latency_fn_resident_transactions_status_classified_id(), __latency_fn_resident_transactions_change_all_new_id(), __latency_fn_resident_transactions_reuse_none_id(), __latency_fn_structure_row_ids_none_id());
	ResidentIncrementalFileChangeRow change = __latency_fn_resident_transactions_make_file_change(transaction, current, previous, __latency_fn_resident_transactions_file_change_aggregate_new_id(), __latency_fn_resident_transactions_dirty_all_new_id(), __latency_fn_resident_transactions_reuse_none_id());
	__latency_fn_resident_transactions_append_transaction_with_change(report, transaction, change);
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
void __latency_fn_resident_transactions_append_from_snapshot_comparison(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::append_from_snapshot_comparison", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[27]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(report->resident_snapshot_count), static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(report->resident_snapshot_count) - static_cast<int_t<> >(1)));
	ResidentProjectSnapshotRow current = report->resident_snapshots[index];
	ResidentProjectSnapshotRow previous = __latency_fn_resident_snapshots_snapshot_by_owner_run_id(previousReport, current->owner_run_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
		ResidentIncrementalTransactionRow transaction = __latency_fn_resident_transactions_make_transaction(current, previous, __latency_fn_resident_transactions_scenario_warm_no_previous_id(), __latency_fn_resident_transactions_status_missing_previous_id(), __latency_fn_resident_transactions_change_no_previous_id(), __latency_fn_resident_transactions_reuse_none_id(), __latency_fn_structure_row_ids_none_id());
		ResidentIncrementalFileChangeRow change = __latency_fn_resident_transactions_make_file_change(transaction, current, previous, __latency_fn_resident_transactions_file_change_aggregate_no_previous_id(), __latency_fn_resident_transactions_dirty_all_new_id(), __latency_fn_resident_transactions_reuse_none_id());
		__latency_fn_resident_transactions_append_transaction_with_change(report, transaction, change);
		return;
	}
	int_t<std::uint32_t> changedFieldCount = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_snapshots_changed_field_count(previous, current));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_snapshots_equals(previous, current)))) {
		ResidentIncrementalTransactionRow transaction = __latency_fn_resident_transactions_make_transaction(current, previous, __latency_fn_resident_transactions_scenario_warm_no_change_id(), __latency_fn_resident_transactions_status_classified_id(), __latency_fn_resident_transactions_change_no_change_id(), __latency_fn_resident_transactions_reuse_ready_id(), cast<int_t<std::uint32_t>>(changedFieldCount));
		ResidentIncrementalFileChangeRow change = __latency_fn_resident_transactions_make_file_change(transaction, current, previous, __latency_fn_resident_transactions_file_change_aggregate_no_change_id(), __latency_fn_resident_transactions_dirty_none_id(), __latency_fn_resident_transactions_reuse_ready_id());
		__latency_fn_resident_transactions_append_transaction_with_change(report, transaction, change);
		return;
	}
	ResidentIncrementalTransactionRow transaction = __latency_fn_resident_transactions_make_transaction(current, previous, __latency_fn_resident_transactions_scenario_warm_changed_aggregate_id(), __latency_fn_resident_transactions_status_classified_id(), __latency_fn_resident_transactions_change_changed_aggregate_id(), __latency_fn_resident_transactions_reuse_blocked_changed_id(), cast<int_t<std::uint32_t>>(changedFieldCount));
	ResidentIncrementalFileChangeRow change = __latency_fn_resident_transactions_make_file_change(transaction, current, previous, __latency_fn_resident_transactions_file_change_aggregate_changed_id(), __latency_fn_resident_transactions_dirty_changed_id(), __latency_fn_resident_transactions_reuse_blocked_changed_id());
	__latency_fn_resident_transactions_append_transaction_with_change(report, transaction, change);
}

}
