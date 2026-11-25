#include <scpp/lang/php.hpp>
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_with_status.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_readiness_row_less.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_are_reduction_ordered.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_readiness_row_less.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_are_reverse_reduction_ordered.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reverse_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_copied_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_copied_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_readiness_row_less.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_sorted_rows_by_reduction_order.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_kind_blocked_input_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_kind_ready_output_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_row_from_readiness.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
vector_t<PartitionReadinessRow> __latency_fn_partition_merge_reductions_rows_with_status(shared_p<PartitionReadinessArtifact> input, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::rows_with_status", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[10]);
	vector_t<PartitionReadinessRow> rows = {};
	php::vector_reserve(rows, php::count(input->rows));
	auto __latency_local_0 = input->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(statusId)))) {
			(void) rows.push_back(row);
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
bool_t __latency_fn_partition_merge_reductions_rows_are_reduction_ordered(vector_t<PartitionReadinessRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::rows_are_reduction_ordered", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[11]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((index < php::count(rows)))) {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_partition_merge_reductions_readiness_row_less(rows.at(index), rows.at((index - static_cast<int_t<> >(1))))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
bool_t __latency_fn_partition_merge_reductions_rows_are_reverse_reduction_ordered(vector_t<PartitionReadinessRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::rows_are_reverse_reduction_ordered", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[12]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((index < php::count(rows)))) {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_partition_merge_reductions_readiness_row_less(rows.at((index - static_cast<int_t<> >(1))), rows.at(index))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
vector_t<PartitionReadinessRow> __latency_fn_partition_merge_reductions_reverse_rows(vector_t<PartitionReadinessRow>& inputRows) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::reverse_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[13]);
	vector_t<PartitionReadinessRow> rows = {};
	php::vector_reserve(rows, php::count(inputRows));
	int_t<> index = required_cast<int_t<>>((php::count(inputRows) - static_cast<int_t<> >(1)));
	while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
		{
		auto __latency_local_0 = inputRows.at(index);
		(void) rows.push_back(__latency_local_0);
		}
		index = (index - static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
vector_t<PartitionReadinessRow> __latency_fn_partition_merge_reductions_copied_rows(vector_t<PartitionReadinessRow>& inputRows) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::copied_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[14]);
	vector_t<PartitionReadinessRow> rows = {};
	php::vector_reserve(rows, php::count(inputRows));
	auto& __latency_local_0 = inputRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		(void) rows.push_back(row);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
vector_t<PartitionReadinessRow> __latency_fn_partition_merge_reductions_sorted_rows_by_reduction_order(vector_t<PartitionReadinessRow>& inputRows) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::sorted_rows_by_reduction_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[15]);
	vector_t<PartitionReadinessRow> rows = required_cast<vector_t<PartitionReadinessRow>>(__latency_fn_partition_merge_reductions_copied_rows(inputRows));
	int_t<> rowCount = required_cast<int_t<>>(php::count(rows));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(1)))) {
		return rows;
	}
	vector_t<PartitionReadinessRow> scratch = required_cast<vector_t<PartitionReadinessRow>>(__latency_fn_partition_merge_reductions_copied_rows(inputRows));
	int_t<> width = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((width < rowCount))) {
		int_t<> start = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((start < rowCount))) {
			int_t<> left = required_cast<int_t<>>(start);
			int_t<> mid = required_cast<int_t<>>((start + width));
			int_t<> right = required_cast<int_t<>>((start + (width * static_cast<int_t<> >(2))));
			if (static_cast<bool>((mid > rowCount))) {
				mid = rowCount;
			}
			if (static_cast<bool>((right > rowCount))) {
				right = rowCount;
			}
			int_t<> leftIndex = required_cast<int_t<>>(left);
			int_t<> rightIndex = required_cast<int_t<>>(mid);
			int_t<> outputIndex = required_cast<int_t<>>(left);
			while (static_cast<bool>(((leftIndex < mid) && (rightIndex < right)))) {
				if (static_cast<bool>(php::condition_truthy(__latency_fn_partition_merge_reductions_readiness_row_less(rows.at(rightIndex), rows.at(leftIndex))))) {
					scratch.at(outputIndex) = rows.at(rightIndex);
					rightIndex = (rightIndex + static_cast<int_t<> >(1));
				}
				else {
					scratch.at(outputIndex) = rows.at(leftIndex);
					leftIndex = (leftIndex + static_cast<int_t<> >(1));
				}
				outputIndex = (outputIndex + static_cast<int_t<> >(1));
			}
			while (static_cast<bool>((leftIndex < mid))) {
				scratch.at(outputIndex) = rows.at(leftIndex);
				leftIndex = (leftIndex + static_cast<int_t<> >(1));
				outputIndex = (outputIndex + static_cast<int_t<> >(1));
			}
			while (static_cast<bool>((rightIndex < right))) {
				scratch.at(outputIndex) = rows.at(rightIndex);
				rightIndex = (rightIndex + static_cast<int_t<> >(1));
				outputIndex = (outputIndex + static_cast<int_t<> >(1));
			}
			start = (start + (width * static_cast<int_t<> >(2)));
		}
		int_t<> copyIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((copyIndex < rowCount))) {
			rows.at(copyIndex) = scratch.at(copyIndex);
			copyIndex = (copyIndex + static_cast<int_t<> >(1));
		}
		width = (width * static_cast<int_t<> >(2));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
PartitionMergeReductionRow __latency_fn_partition_merge_reductions_row_from_readiness(PartitionReadinessRow inputRow, int_t<std::uint32_t> inputOrderId, int_t<std::uint32_t> reductionOrderId, int_t<std::uint32_t> outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::row_from_readiness", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[16]);
	PartitionMergeReductionRow row = PartitionMergeReductionRow{};
	row->owner_run_id = inputRow->owner_run_id;
	row->source_unit_id = inputRow->source_unit_id;
	row->symbol_id = inputRow->symbol_id;
	row->owner_row_id = inputRow->owner_row_id;
	row->input_snapshot_generation = inputRow->input_snapshot_generation;
	row->local_row_first_id = inputRow->local_row_first_id;
	row->local_row_count = inputRow->local_row_count;
	row->merge_order_key = inputRow->merge_order_key;
	row->published_row_first_id = inputRow->published_row_first_id;
	row->published_row_count = inputRow->published_row_count;
	row->input_readiness_row_id = inputRow->row_id;
	row->input_order_id = inputOrderId;
	row->reduction_order_id = reductionOrderId;
	row->output_order_id = outputOrderId;
	row->owner_kind_id = inputRow->owner_kind_id;
	row->publication_model_id = inputRow->publication_model_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(inputRow->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		row->reduction_kind_id = __latency_fn_partition_merge_reductions_reduction_kind_ready_output_id();
		row->blocked_reason_id = __latency_fn_partition_readiness_blocked_reason_none_id();
	}
	else {
		row->reduction_kind_id = __latency_fn_partition_merge_reductions_reduction_kind_blocked_input_id();
		row->blocked_reason_id = inputRow->blocked_reason_id;
	}
	row->status_id = inputRow->status_id;
	return row;
}

}
