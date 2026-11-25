#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct PartitionReadinessRow;
void __latency_fn_partition_readiness_append_report_row(shared_p<CompilerProjectRunReport>& report, PartitionReadinessRow row);
}
