#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyBudgetRow;
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_observed_count_for_limit_kind(shared_p<CompilerProjectRunReport> report, ResidentDirtyBudgetRow budget, int_t<std::uint16_t> limitKindId);
}
