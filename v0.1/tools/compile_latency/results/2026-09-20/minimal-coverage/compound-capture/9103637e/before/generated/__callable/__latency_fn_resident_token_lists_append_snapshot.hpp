#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentTokenListSnapshotRow;
void __latency_fn_resident_token_lists_append_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentTokenListSnapshotRow row);
}
