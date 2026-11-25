#include <scpp/lang/php.hpp>
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/partition_merge_reductions.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_artifact_kind_partition_merge_reduction_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_source_model_partition_readiness_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_model_sequential_simulated_partitions_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_kind_ready_output_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_kind_blocked_input_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_artifact_kind_partition_merge_reduction_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_new_artifact.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_reduction_model_sequential_simulated_partitions_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_source_model_partition_readiness_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_readiness_row_less.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_max_input_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_input_order_index_by_row_id.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_max_input_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_input_order_for_row_id_from_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
bool_t partition_merge_reductions::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == partition_merge_reductions::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_partition_merge_reductions_artifact_kind_partition_merge_reduction_id() {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::artifact_kind_partition_merge_reduction_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_partition_merge_reductions_source_model_partition_readiness_id() {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::source_model_partition_readiness_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_partition_merge_reductions_reduction_model_sequential_simulated_partitions_id() {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::reduction_model_sequential_simulated_partitions_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_partition_merge_reductions_reduction_kind_ready_output_id() {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::reduction_kind_ready_output_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_partition_merge_reductions_reduction_kind_blocked_input_id() {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::reduction_kind_blocked_input_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
shared_p<PartitionMergeReductionArtifact> __latency_fn_partition_merge_reductions_new_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[5]);
	shared_p<PartitionMergeReductionArtifact> artifact = create<PartitionMergeReductionArtifact>();
	artifact->artifact_kind_id = __latency_fn_partition_merge_reductions_artifact_kind_partition_merge_reduction_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_partition_merge_reductions_source_model_partition_readiness_id();
	artifact->reduction_model_id = __latency_fn_partition_merge_reductions_reduction_model_sequential_simulated_partitions_id();
	php::vector_reserve(artifact->rows, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
bool_t __latency_fn_partition_merge_reductions_readiness_row_less(PartitionReadinessRow left, PartitionReadinessRow right) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::readiness_row_less", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[6]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(right->row_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(left->merge_order_key, right->merge_order_key)))) {
		return bool_t((left->merge_order_key < right->merge_order_key));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->owner_run_id), cast<int_t<>>(right->owner_run_id))))) {
		return bool_t((cast<int_t<>>(left->owner_run_id) < cast<int_t<>>(right->owner_run_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->owner_kind_id), cast<int_t<>>(right->owner_kind_id))))) {
		return bool_t((cast<int_t<>>(left->owner_kind_id) < cast<int_t<>>(right->owner_kind_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))))) {
		return bool_t((cast<int_t<>>(left->source_unit_id) < cast<int_t<>>(right->source_unit_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id))))) {
		return bool_t((cast<int_t<>>(left->symbol_id) < cast<int_t<>>(right->symbol_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->owner_row_id), cast<int_t<>>(right->owner_row_id))))) {
		return bool_t((cast<int_t<>>(left->owner_row_id) < cast<int_t<>>(right->owner_row_id)));
	}
	return bool_t((cast<int_t<>>(left->row_id) < cast<int_t<>>(right->row_id)));
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_partition_merge_reductions_max_input_row_id(shared_p<PartitionReadinessArtifact> input) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::max_input_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[7]);
	int_t<> maxRowId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = input->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->row_id) > maxRowId))) {
			maxRowId = cast<int_t<>>(row->row_id);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxRowId);
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
vector_t<int_t<std::uint32_t>> __latency_fn_partition_merge_reductions_input_order_index_by_row_id(shared_p<PartitionReadinessArtifact> input) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::input_order_index_by_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[8]);
	int_t<std::uint32_t> maxRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_partition_merge_reductions_max_input_row_id(input));
	vector_t<int_t<std::uint32_t>> inputOrderByRowId = {};
	php::vector_reserve(inputOrderByRowId, cast<int_t<>>(maxRowId));
	while (static_cast<bool>((php::count(inputOrderByRowId) < cast<int_t<>>(maxRowId)))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) inputOrderByRowId.push_back(__latency_local_0);
		}
	}
	int_t<> inputOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_1 = input->rows;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->row_id, php::count(inputOrderByRowId))))) {
			inputOrderByRowId.at(__latency_fn_structure_row_ids_dense_index(row->row_id)) = __latency_fn_structure_row_ids_uint32_from_int(inputOrder);
		}
		inputOrder = (inputOrder + static_cast<int_t<> >(1));
	}
	return inputOrderByRowId;
}

}

namespace scpp { extern const int __latency_lines_partition_merge_reductions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_partition_merge_reductions_input_order_for_row_id_from_index(vector_t<int_t<std::uint32_t>>& inputOrderByRowId, int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("partition_merge_reductions::input_order_for_row_id_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_merge_reductions.phs", __latency_lines_partition_merge_reductions[9]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, php::count(inputOrderByRowId))))) {
		return cast<int_t<std::uint32_t>>(inputOrderByRowId.at(__latency_fn_structure_row_ids_dense_index(rowId)));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}
