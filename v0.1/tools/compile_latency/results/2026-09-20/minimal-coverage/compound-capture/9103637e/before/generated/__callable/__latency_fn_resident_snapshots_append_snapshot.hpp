#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct CompilerProjectRunRow;
struct ResidentProjectSnapshotRow;
ResidentProjectSnapshotRow __latency_fn_resident_snapshots_append_snapshot(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow row);
}
