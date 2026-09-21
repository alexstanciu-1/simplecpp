#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ResidentProjectSnapshotDiffRow.hpp"
#include "__types/ResidentProjectSnapshotRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/resident_snapshots.hpp"
#include "__callable/__latency_fn_resident_snapshots_mix_hash.hpp"
#include "__callable/__latency_fn_resident_snapshots_source_content_hash_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_equals.hpp"
#include "__callable/__latency_fn_resident_snapshots_changed_field_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_snapshots_from_compiler_project_run_row.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_hash.hpp"
#include "__callable/__latency_fn_resident_snapshots_source_content_hash_for_owner.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_by_owner_run_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_changed_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_changed_field_count.hpp"
#include "__callable/__latency_fn_resident_snapshots_compare_last_with_previous.hpp"
#include "__callable/__latency_fn_resident_snapshots_equals.hpp"
#include "__callable/__latency_fn_resident_snapshots_reuse_blocked_changed_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_reuse_ready_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_by_owner_run_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_changed_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_snapshots_source_content_hash_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::source_content_hash_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[12]);
	int_t<std::uint32_t> hash = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2166136261)));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceSnapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceSnapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), sourceSnapshot->source_unit_id);
			hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), sourceSnapshot->content_hash);
			hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), sourceSnapshot->source_length);
		}
	}
	return cast<int_t<std::uint32_t>>(hash);
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
bool_t __latency_fn_resident_snapshots_equals(ResidentProjectSnapshotRow left, ResidentProjectSnapshotRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::equals", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[13]);
	return ((((((((((((((php::identical(cast<int_t<>>(left->source_count), cast<int_t<>>(right->source_count)) && php::identical(cast<int_t<>>(left->source_byte_count), cast<int_t<>>(right->source_byte_count))) && php::identical(cast<int_t<>>(left->source_content_hash), cast<int_t<>>(right->source_content_hash))) && php::identical(cast<int_t<>>(left->token_count), cast<int_t<>>(right->token_count))) && php::identical(cast<int_t<>>(left->frontend_node_count), cast<int_t<>>(right->frontend_node_count))) && php::identical(cast<int_t<>>(left->frontend_declaration_count), cast<int_t<>>(right->frontend_declaration_count))) && php::identical(cast<int_t<>>(left->symbol_count), cast<int_t<>>(right->symbol_count))) && php::identical(cast<int_t<>>(left->reference_count), cast<int_t<>>(right->reference_count))) && php::identical(cast<int_t<>>(left->callable_contract_count), cast<int_t<>>(right->callable_contract_count))) && php::identical(cast<int_t<>>(left->capability_readiness_count), cast<int_t<>>(right->capability_readiness_count))) && php::identical(cast<int_t<>>(left->backend_request_count), cast<int_t<>>(right->backend_request_count))) && php::identical(cast<int_t<>>(left->lowering_step_count), cast<int_t<>>(right->lowering_step_count))) && php::identical(cast<int_t<>>(left->artifact_write_count), cast<int_t<>>(right->artifact_write_count))) && php::identical(cast<int_t<>>(left->skipped_write_count), cast<int_t<>>(right->skipped_write_count))) && php::identical(cast<int_t<>>(left->snapshot_hash), cast<int_t<>>(right->snapshot_hash)));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_snapshots_changed_field_count(ResidentProjectSnapshotRow left, ResidentProjectSnapshotRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::changed_field_count", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[14]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->source_count), cast<int_t<>>(right->source_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->source_byte_count), cast<int_t<>>(right->source_byte_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->source_content_hash), cast<int_t<>>(right->source_content_hash))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->token_count), cast<int_t<>>(right->token_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->frontend_node_count), cast<int_t<>>(right->frontend_node_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->symbol_count), cast<int_t<>>(right->symbol_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->reference_count), cast<int_t<>>(right->reference_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->callable_contract_count), cast<int_t<>>(right->callable_contract_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->capability_readiness_count), cast<int_t<>>(right->capability_readiness_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->backend_request_count), cast<int_t<>>(right->backend_request_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->lowering_step_count), cast<int_t<>>(right->lowering_step_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->artifact_write_count), cast<int_t<>>(right->artifact_write_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->skipped_write_count), cast<int_t<>>(right->skipped_write_count))))) {
		count = (count + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
ResidentProjectSnapshotRow __latency_fn_resident_snapshots_append_snapshot(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::append_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[15]);
	ResidentProjectSnapshotRow snapshot = __latency_fn_resident_snapshots_from_compiler_project_run_row(row);
	snapshot->source_content_hash = __latency_fn_resident_snapshots_source_content_hash_for_owner(report, row->run_id);
	snapshot->snapshot_hash = __latency_fn_resident_snapshots_snapshot_hash(snapshot);
	snapshot->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_snapshots));
	(void) report->resident_snapshots.append(snapshot);
	report->resident_snapshot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_snapshots));
	return snapshot;
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
ResidentProjectSnapshotRow __latency_fn_resident_snapshots_snapshot_by_owner_run_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::snapshot_by_owner_run_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[16]);
	auto __latency_local_0 = report->resident_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return snapshot;
		}
	}
	ResidentProjectSnapshotRow empty = ResidentProjectSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
void __latency_fn_resident_snapshots_compare_last_with_previous(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::compare_last_with_previous", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(report->resident_snapshot_count), static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(report->resident_snapshot_count) - static_cast<int_t<> >(1)));
	ResidentProjectSnapshotRow current = report->resident_snapshots[index];
	ResidentProjectSnapshotRow previousSnapshot = __latency_fn_resident_snapshots_snapshot_by_owner_run_id(previous, current->owner_run_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousSnapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return;
	}
	ResidentProjectSnapshotDiffRow diff = ResidentProjectSnapshotDiffRow{};
	diff->diff_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_snapshot_diffs));
	diff->owner_run_id = current->owner_run_id;
	diff->previous_snapshot_id = previousSnapshot->snapshot_id;
	diff->current_snapshot_id = current->snapshot_id;
	diff->changed_field_count = __latency_fn_resident_snapshots_changed_field_count(previousSnapshot, current);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_snapshots_equals(previousSnapshot, current)))) {
		current->status_id = __latency_fn_resident_snapshots_status_reuse_ready_id();
		current->change_status_id = __latency_fn_resident_snapshots_change_no_change_id();
		diff->status_id = __latency_fn_resident_snapshots_change_no_change_id();
		diff->reuse_status_id = __latency_fn_resident_snapshots_reuse_ready_id();
		report->resident_snapshot_reuse_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_snapshot_reuse_count) + static_cast<int_t<> >(1)));
	}
	else {
		current->status_id = __latency_fn_resident_snapshots_status_changed_id();
		current->change_status_id = __latency_fn_resident_snapshots_change_changed_id();
		diff->status_id = __latency_fn_resident_snapshots_change_changed_id();
		diff->reuse_status_id = __latency_fn_resident_snapshots_reuse_blocked_changed_id();
		report->resident_snapshot_changed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_snapshot_changed_count) + static_cast<int_t<> >(1)));
	}
	report->resident_snapshots[index] = current;
	(void) report->resident_snapshot_diffs.append(diff);
	report->resident_snapshot_diff_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_snapshot_diffs));
}

}
