#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct PartitionReadinessRow;
void __latency_fn_partition_readiness_add_report_counts(shared_p<CompilerProjectRunReport>& report, PartitionReadinessRow row);
}
