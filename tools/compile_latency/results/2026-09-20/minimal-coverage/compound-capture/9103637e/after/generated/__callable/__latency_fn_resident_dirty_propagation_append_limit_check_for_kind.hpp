#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyBudgetRow;
bool_t __latency_fn_resident_dirty_propagation_append_limit_check_for_kind(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow budget, int_t<std::uint16_t> limitKindId);
}
