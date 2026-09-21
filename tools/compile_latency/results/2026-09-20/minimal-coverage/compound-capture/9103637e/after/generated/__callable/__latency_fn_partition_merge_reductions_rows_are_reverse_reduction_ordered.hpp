#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionReadinessRow;
bool_t __latency_fn_partition_merge_reductions_rows_are_reverse_reduction_ordered(vector_t<PartitionReadinessRow>& rows);
}
