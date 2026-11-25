#include <scpp/lang/php.hpp>
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix_row_value__exec.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix_output_row__exec.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix_row_value.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_output_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_finalize_artifact.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_output_hash.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_sorted_status_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_input_order_for_row_id_from_index.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_row_from_readiness.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_sorted_status_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_status_rows_or_reduce.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reverse_rows.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_are_reduction_ordered.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_are_reverse_reduction_ordered.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_rows_with_status.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_sorted_rows_by_reduction_order.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_append_status_rows_or_reduce.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_finalize_artifact.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_input_order_index_by_row_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_new_artifact.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_append_artifact_row(shared_p<PartitionMergeReductionArtifact>& artifact, PartitionMergeReductionRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::append_artifact_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		row->row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	}
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((cast<int_t<>>(row->output_order_id) > static_cast<int_t<> >(0)))) {
		artifact->output_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->output_row_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<> __latency_fn_partition_merge_reductions_stable_hash_mix(int_t<> hash, int_t<> value, int_t<> multiplier, int_t<> modulus) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::stable_hash_mix", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[18]);
	int_t<> normalized = required_cast<int_t<>>((value % modulus));
	return ((((hash * multiplier) + normalized) + static_cast<int_t<> >(17)) % modulus);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_stable_hash_mix_row_value__exec(int_t<>& hashA, int_t<>& hashB, int_t<> value) {
	hashA = __latency_fn_partition_merge_reductions_stable_hash_mix(hashA, value, static_cast<int_t<> >(131), static_cast<int_t<> >(1000000007));
	hashB = __latency_fn_partition_merge_reductions_stable_hash_mix(hashB, value, static_cast<int_t<> >(137), static_cast<int_t<> >(1000000009));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_stable_hash_mix_output_row__exec(int_t<>& hashA, int_t<>& hashB, PartitionMergeReductionRow row) {
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->output_order_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->owner_run_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->source_unit_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->symbol_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->owner_row_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->input_snapshot_generation));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->local_row_first_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->local_row_count));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->merge_order_key));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->published_row_first_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->published_row_count));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->owner_kind_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->publication_model_id));
	__latency_fn_partition_merge_reductions_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->status_id));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_partition_merge_reductions_stable_output_hash(shared_p<PartitionMergeReductionArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::stable_output_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[19]);
	int_t<> hashA = required_cast<int_t<>>(static_cast<int_t<> >(146959811));
	int_t<> hashB = required_cast<int_t<>>(static_cast<int_t<> >(216613626));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->output_order_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_partition_merge_reductions_stable_hash_mix_output_row(hashA, hashB, row);
		}
	}
	int_t<> combined = required_cast<int_t<>>(((hashA * static_cast<int_t<> >(1000000009)) + hashB));
	if (static_cast<bool>(php::identical(combined, static_cast<int_t<> >(0)))) {
		combined = static_cast<int_t<> >(1);
	}
	return __latency_fn_structure_row_ids_uint64_from_int(combined);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_finalize_artifact(shared_p<PartitionMergeReductionArtifact>& artifact) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::finalize_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[20]);
	artifact->stable_output_hash = __latency_fn_partition_merge_reductions_stable_output_hash(artifact);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_append_sorted_status_rows(shared_p<PartitionMergeReductionArtifact>& artifact, vector_t<PartitionReadinessRow>& rows, vector_t<int_t<std::uint32_t>>& inputOrderByRowId, int_t<std::uint32_t>& outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::append_sorted_status_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[21]);
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto inputRow = __latency_local_1.value_copy();
		int_t<std::uint32_t> reductionOrderId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows)));
		int_t<std::uint32_t> inputOrderId = required_cast<int_t<std::uint32_t>>(__latency_fn_partition_merge_reductions_input_order_for_row_id_from_index(inputOrderByRowId, inputRow->row_id));
		int_t<std::uint32_t> rowOutputOrderId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		if (static_cast<bool>(php::identical(cast<int_t<>>(inputRow->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
			rowOutputOrderId = cast<int_t<std::uint32_t>>(outputOrderId);
			outputOrderId = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(outputOrderId) + static_cast<int_t<> >(1)));
		}
		__latency_fn_partition_merge_reductions_append_artifact_row(artifact, __latency_fn_partition_merge_reductions_row_from_readiness(inputRow, cast<int_t<std::uint32_t>>(inputOrderId), cast<int_t<std::uint32_t>>(reductionOrderId), cast<int_t<std::uint32_t>>(rowOutputOrderId)));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
void __latency_fn_partition_merge_reductions_append_status_rows_or_reduce(shared_p<PartitionMergeReductionArtifact>& artifact, shared_p<PartitionReadinessArtifact> input, int_t<std::uint16_t> statusId, vector_t<int_t<std::uint32_t>>& inputOrderByRowId, int_t<std::uint32_t>& outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::append_status_rows_or_reduce", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[22]);
	vector_t<PartitionReadinessRow> statusRows = required_cast<vector_t<PartitionReadinessRow>>(__latency_fn_partition_merge_reductions_rows_with_status(input, cast<int_t<std::uint16_t>>(statusId)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_partition_merge_reductions_rows_are_reduction_ordered(statusRows)))) {
		__latency_fn_partition_merge_reductions_append_sorted_status_rows(artifact, statusRows, inputOrderByRowId, outputOrderId);
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_partition_merge_reductions_rows_are_reverse_reduction_ordered(statusRows)))) {
		vector_t<PartitionReadinessRow> reversedRows = required_cast<vector_t<PartitionReadinessRow>>(__latency_fn_partition_merge_reductions_reverse_rows(statusRows));
		__latency_fn_partition_merge_reductions_append_sorted_status_rows(artifact, reversedRows, inputOrderByRowId, outputOrderId);
		return;
	}
	vector_t<PartitionReadinessRow> sortedRows = required_cast<vector_t<PartitionReadinessRow>>(__latency_fn_partition_merge_reductions_sorted_rows_by_reduction_order(statusRows));
	__latency_fn_partition_merge_reductions_append_sorted_status_rows(artifact, sortedRows, inputOrderByRowId, outputOrderId);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
shared_p<PartitionMergeReductionArtifact> __latency_fn_partition_merge_reductions_from_partition_readiness(shared_p<PartitionReadinessArtifact> input) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::from_partition_readiness", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[23]);
	shared_p<PartitionMergeReductionArtifact> artifact = __latency_fn_partition_merge_reductions_new_artifact(php::count(input->rows));
	artifact->input_partition_count = input->row_count;
	vector_t<int_t<std::uint32_t>> inputOrderByRowId = required_cast<vector_t<int_t<std::uint32_t>>>(__latency_fn_partition_merge_reductions_input_order_index_by_row_id(input));
	int_t<std::uint32_t> outputOrderId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_partition_merge_reductions_append_status_rows_or_reduce(artifact, input, __latency_fn_partition_readiness_status_ready_id(), inputOrderByRowId, outputOrderId);
	__latency_fn_partition_merge_reductions_append_status_rows_or_reduce(artifact, input, __latency_fn_partition_readiness_status_blocked_id(), inputOrderByRowId, outputOrderId);
	__latency_fn_partition_merge_reductions_finalize_artifact(artifact);
	return artifact;
}

}
