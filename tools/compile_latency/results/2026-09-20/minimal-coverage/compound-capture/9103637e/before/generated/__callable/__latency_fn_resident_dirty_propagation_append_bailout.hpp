#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyBailoutRow;
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_bailout(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBailoutRow row);
}
