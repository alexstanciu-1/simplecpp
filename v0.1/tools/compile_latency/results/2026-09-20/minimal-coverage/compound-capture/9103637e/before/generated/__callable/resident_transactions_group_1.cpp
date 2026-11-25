#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentIncrementalFileChangeRow.hpp"
#include "__types/ResidentIncrementalTransactionRow.hpp"
#include "__types/ResidentProjectSnapshotRow.hpp"
#include "__types/resident_transactions.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_all_new_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_dirty_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_mix_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_mix_hash.hpp"
#include "__callable/__latency_fn_resident_transactions_transaction_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_empty_snapshot.hpp"
#include "__callable/__latency_fn_resident_transactions_make_transaction.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_cold_all_new_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_changed_aggregate_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_change_id.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_previous_id.hpp"
#include "__callable/__latency_fn_resident_transactions_transaction_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_make_file_change.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_transactions_append_transaction_with_change.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_blocked_changed_id.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_ready_id.hpp"
#include "__callable/__latency_fn_resident_transactions_transaction_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_file_change_aggregate_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::file_change_aggregate_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_dirty_all_new_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::dirty_all_new_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_dirty_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::dirty_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[18]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_dirty_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::dirty_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[19]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_transactions_mix_hash(int_t<std::uint32_t> hash, int_t<std::uint32_t> value) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::mix_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[20]);
	int_t<> mixed = required_cast<int_t<>>((((cast<int_t<>>(hash) * static_cast<int_t<> >(16777619)) + cast<int_t<>>(value)) % static_cast<int_t<> >(4294967295)));
	return __latency_fn_structure_row_ids_uint32_from_int(mixed);
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_transactions_transaction_hash(ResidentIncrementalTransactionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::transaction_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[21]);
	int_t<std::uint32_t> hash = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2166136261)));
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->owner_run_id);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->generation);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->previous_snapshot_id);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->current_snapshot_id);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->input_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->new_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->modified_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->no_change_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->dirty_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->reused_source_count);
	hash = __latency_fn_resident_transactions_mix_hash(cast<int_t<std::uint32_t>>(hash), row->changed_field_count);
	return cast<int_t<std::uint32_t>>(hash);
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
ResidentProjectSnapshotRow __latency_fn_resident_transactions_empty_snapshot() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::empty_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[22]);
	ResidentProjectSnapshotRow snapshot = ResidentProjectSnapshotRow{};
	return snapshot;
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
ResidentIncrementalTransactionRow __latency_fn_resident_transactions_make_transaction(ResidentProjectSnapshotRow current, ResidentProjectSnapshotRow previous, int_t<std::uint16_t> scenarioId, int_t<std::uint16_t> statusId, int_t<std::uint16_t> changeStatusId, int_t<std::uint16_t> reuseStatusId, int_t<std::uint32_t> changedFieldCount) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::make_transaction", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[23]);
	ResidentIncrementalTransactionRow row = ResidentIncrementalTransactionRow{};
	row->owner_run_id = current->owner_run_id;
	row->generation = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->input_source_count = current->source_count;
	row->changed_field_count = changedFieldCount;
	row->scenario_id = scenarioId;
	row->status_id = statusId;
	row->change_status_id = changeStatusId;
	row->reuse_status_id = reuseStatusId;
	if (static_cast<bool>((php::identical(cast<int_t<>>(scenarioId), cast<int_t<>>(__latency_fn_resident_transactions_scenario_cold_all_new_id())) || php::identical(cast<int_t<>>(scenarioId), cast<int_t<>>(__latency_fn_resident_transactions_scenario_warm_no_previous_id()))))) {
		row->new_source_count = current->source_count;
		row->dirty_source_count = current->source_count;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(scenarioId), cast<int_t<>>(__latency_fn_resident_transactions_scenario_warm_no_change_id())))) {
		row->no_change_source_count = current->source_count;
		row->reused_source_count = current->source_count;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(scenarioId), cast<int_t<>>(__latency_fn_resident_transactions_scenario_warm_changed_aggregate_id())))) {
		row->modified_source_count = current->source_count;
		row->dirty_source_count = current->source_count;
	}
	row->transaction_hash = __latency_fn_resident_transactions_transaction_hash(row);
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
ResidentIncrementalFileChangeRow __latency_fn_resident_transactions_make_file_change(ResidentIncrementalTransactionRow transaction, ResidentProjectSnapshotRow current, ResidentProjectSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyStatusId, int_t<std::uint16_t> reuseStatusId) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::make_file_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[24]);
	ResidentIncrementalFileChangeRow row = ResidentIncrementalFileChangeRow{};
	row->transaction_id = transaction->transaction_id;
	row->owner_run_id = transaction->owner_run_id;
	row->source_unit_id = __latency_fn_structure_row_ids_none_id();
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->previous_source_count = previous->source_count;
	row->current_source_count = current->source_count;
	row->previous_source_byte_count = previous->source_byte_count;
	row->current_source_byte_count = current->source_byte_count;
	row->previous_snapshot_hash = previous->snapshot_hash;
	row->current_snapshot_hash = current->snapshot_hash;
	row->change_kind_id = changeKindId;
	row->dirty_status_id = dirtyStatusId;
	row->reuse_status_id = reuseStatusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
void __latency_fn_resident_transactions_append_transaction_with_change(shared_p<CompilerProjectRunReport>& report, ResidentIncrementalTransactionRow transaction, ResidentIncrementalFileChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::append_transaction_with_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[25]);
	transaction->transaction_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_transactions));
	transaction->generation = transaction->transaction_id;
	transaction->file_change_first_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_file_changes));
	transaction->file_change_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	transaction->transaction_hash = __latency_fn_resident_transactions_transaction_hash(transaction);
	change->transaction_id = transaction->transaction_id;
	change->change_id = transaction->file_change_first_id;
	(void) report->resident_transactions.append(transaction);
	(void) report->resident_file_changes.append(change);
	report->resident_transaction_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_transactions));
	report->resident_file_change_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_file_changes));
	if (static_cast<bool>(php::identical(cast<int_t<>>(transaction->reuse_status_id), cast<int_t<>>(__latency_fn_resident_transactions_reuse_ready_id())))) {
		report->resident_transaction_reuse_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_transaction_reuse_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(transaction->reuse_status_id), cast<int_t<>>(__latency_fn_resident_transactions_reuse_blocked_changed_id())))) {
		report->resident_transaction_changed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_transaction_changed_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((cast<int_t<>>(transaction->dirty_source_count) > static_cast<int_t<> >(0)))) {
		report->resident_transaction_dirty_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_transaction_dirty_count) + static_cast<int_t<> >(1)));
	}
}

}
