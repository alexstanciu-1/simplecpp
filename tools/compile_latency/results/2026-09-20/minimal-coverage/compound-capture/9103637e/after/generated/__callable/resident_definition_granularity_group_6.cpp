#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_cold_changes_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_cold_changes_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_cold_changes_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_changes_from_previous_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_changes_from_previous_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_changes_from_previous_for_owner.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_cold_changes_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_cold_changes_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[63]);
	__latency_fn_resident_definition_granularity_append_source_cold_changes_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId));
	__latency_fn_resident_definition_granularity_append_symbol_cold_changes_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_changes_from_previous_for_owner(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_changes_from_previous_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[64]);
	__latency_fn_resident_definition_granularity_append_source_changes_from_previous_for_owner(report, previousReport, cast<int_t<std::uint32_t>>(ownerRunId));
	__latency_fn_resident_definition_granularity_append_symbol_changes_from_previous_for_owner(report, previousReport, cast<int_t<std::uint32_t>>(ownerRunId));
}

}
