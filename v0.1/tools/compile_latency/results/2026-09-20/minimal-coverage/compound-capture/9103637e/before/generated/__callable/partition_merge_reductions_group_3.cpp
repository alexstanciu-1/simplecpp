#include <scpp/lang/php.hpp>
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
PartitionMergeReductionRow __latency_fn_partition_merge_reductions_first_output_row(shared_p<PartitionMergeReductionArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::first_output_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[24]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->output_order_id), static_cast<int_t<> >(1)))) {
			return row;
		}
	}
	PartitionMergeReductionRow empty = PartitionMergeReductionRow{};
	return empty;
}

}
