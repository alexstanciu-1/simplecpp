#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyProcessedRow;
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_processed(shared_p<CompilerProjectRunReport>& report, ResidentDirtyProcessedRow row);
}
