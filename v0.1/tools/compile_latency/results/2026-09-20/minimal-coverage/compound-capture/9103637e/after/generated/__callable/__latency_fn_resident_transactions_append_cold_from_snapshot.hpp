#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSnapshotRow;
void __latency_fn_resident_transactions_append_cold_from_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentProjectSnapshotRow current);
}
