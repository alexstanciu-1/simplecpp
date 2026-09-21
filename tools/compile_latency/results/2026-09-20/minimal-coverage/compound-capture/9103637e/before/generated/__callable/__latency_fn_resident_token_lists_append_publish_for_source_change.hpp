#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitChangeRow;
void __latency_fn_resident_token_lists_append_publish_for_source_change(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, ResidentSourceUnitChangeRow change, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, vector_t<int_t<std::uint32_t>>& currentSnapshotIds);
}
