#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyLimitCheckRow;
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_limit_check(shared_p<CompilerProjectRunReport>& report, ResidentDirtyLimitCheckRow row);
}
