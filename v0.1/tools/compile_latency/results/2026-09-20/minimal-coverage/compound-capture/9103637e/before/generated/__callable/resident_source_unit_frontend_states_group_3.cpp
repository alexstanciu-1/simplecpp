#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitEarlySkipArtifact.hpp"
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_snapshot.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_owner_snapshots.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_should_reuse_previous.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_snapshot(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitSnapshotRow snapshot) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_reused_previous_from_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[30]);
	ResidentSourceUnitFrontendStateRow previousState = __latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source(previous, snapshot->owner_run_id, snapshot->source_unit_id);
	return __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot(report, previous, snapshot, previousState);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_owner_snapshots(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_reused_previous_from_owner_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[31]);
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_states_max_source_unit_id_from_owner_snapshots(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	vector_t<int_t<std::uint32_t>> previousStateIds = {};
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_source_unit_frontend_states_build_state_lookup_ids(previous, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousStateIds);
		__latency_fn_resident_source_unit_frontend_states_append_lookup_metrics(report, slotCount);
	}
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitFrontendStateRow previousState = __latency_fn_resident_source_unit_frontend_states_state_from_lookup(report, previous, previousStateIds, cast<int_t<std::uint32_t>>(ownerRunId), snapshot->source_unit_id);
			__latency_fn_resident_source_unit_frontend_states_append_reused_previous_from_state_snapshot(report, previous, snapshot, previousState);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_states_should_reuse_previous(ResidentSourceUnitEarlySkipArtifact earlySkip, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::should_reuse_previous", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[32]);
	auto __latency_local_0 = earlySkip->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(decision->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(decision->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}
