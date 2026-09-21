#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_reused_snapshots_for_owner.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_reused_snapshots_for_owner(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_reused_snapshots_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[32]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_snapshots));
	auto __latency_local_0 = previous->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentFunctionBodySnapshotRow row = snapshot;
			row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_snapshots));
			row->owner_run_id = ownerRunId;
			row->snapshot_kind_id = __latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id();
			__latency_fn_resident_function_body_ownership_append_snapshot(report, row);
		}
	}
	__latency_fn_resident_function_body_ownership_append_memory_estimate(report, (php::count(report->resident_function_body_snapshots) - startCount));
}

}
