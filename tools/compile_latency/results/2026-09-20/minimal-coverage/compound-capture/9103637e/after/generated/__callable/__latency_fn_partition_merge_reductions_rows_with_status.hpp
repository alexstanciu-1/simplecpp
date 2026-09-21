#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct PartitionReadinessRow;
vector_t<PartitionReadinessRow> __latency_fn_partition_merge_reductions_rows_with_status(shared_p<PartitionReadinessArtifact> input, int_t<std::uint16_t> statusId);
}
