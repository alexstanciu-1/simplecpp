#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentPublicSurfaceIncrementalAcceptanceRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_append_from_recompute_targets_if_needed.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_append_row.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_row_from_symbol_change.hpp"
namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_public_surface_incremental_acceptance_append_from_recompute_targets_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::append_from_recompute_targets_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[31]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_public_surface_incremental_acceptances));
	auto __latency_local_0 = report->resident_symbol_definition_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id()))))) {
			__latency_fn_resident_public_surface_incremental_acceptance_append_row(report, __latency_fn_resident_public_surface_incremental_acceptance_row_from_symbol_change(report, change));
		}
	}
	__latency_fn_resident_public_surface_incremental_acceptance_append_memory_estimate(report, (php::count(report->resident_public_surface_incremental_acceptances) - startCount));
}

}
