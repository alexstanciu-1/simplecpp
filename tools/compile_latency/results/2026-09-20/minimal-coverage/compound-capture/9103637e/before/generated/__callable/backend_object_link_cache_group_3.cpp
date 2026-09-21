#include <scpp/lang/php.hpp>
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__types/BackendObjectLinkCacheArtifact.hpp"
#include "__types/BackendPartitionExecutionArtifact.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/ObjectOutputWorkerInput.hpp"
#include "__types/ObjectOutputWorkerResult.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_mix_object.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_for_objects.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_mix_object.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_rows_match.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_cache_rows_match.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_rows_match.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_input_from_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_input_from_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_inputs_from_partitions.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_partition_from_object_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_result_from_row.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<> __latency_fn_backend_object_link_cache_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[36]);
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % __latency_fn_backend_object_link_cache_semantic_hash_modulus()));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		return (mixed + __latency_fn_backend_object_link_cache_semantic_hash_modulus());
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<> __latency_fn_backend_object_link_cache_semantic_hash_mix_object(int_t<> hash, BackendObjectCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::semantic_hash_mix_object", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[37]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_backend_object_link_cache_semantic_hash_mix_int(hash, cast<int_t<>>(row->cache_object_id)));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->partition_execution_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->owner_symbol_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->source_unit_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->cache_status_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->dirty_status_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->object_action_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->owner_key_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->source_unit_key_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->partition_key_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->backend_row_key_id));
	next = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(next, cast<int_t<>>(row->output_object_key_id));
	return next;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_object_link_cache_semantic_hash_for_objects(shared_p<BackendObjectLinkCacheArtifact> cache) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::semantic_hash_for_objects", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[38]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(31));
	hash = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(hash, cast<int_t<>>(cache->object_count));
	hash = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(hash, cast<int_t<>>(cache->object_compile_deferred_count));
	hash = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(hash, cast<int_t<>>(cache->object_reuse_deferred_count));
	hash = __latency_fn_backend_object_link_cache_semantic_hash_mix_int(hash, cast<int_t<>>(cache->object_blocked_count));
	auto __latency_local_0 = cache->objects;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto objectRow = __latency_local_1.value_copy();
		hash = __latency_fn_backend_object_link_cache_semantic_hash_mix_object(hash, objectRow);
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
bool_t __latency_fn_backend_object_link_cache_object_rows_match(BackendObjectCacheDecisionRow left, BackendObjectCacheDecisionRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_rows_match", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[39]);
	return (((((((((((((((php::identical(cast<int_t<>>(left->cache_object_id), cast<int_t<>>(right->cache_object_id)) && php::identical(cast<int_t<>>(left->partition_execution_id), cast<int_t<>>(right->partition_execution_id))) && php::identical(cast<int_t<>>(left->owner_symbol_id), cast<int_t<>>(right->owner_symbol_id))) && php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))) && php::identical(left->input_partition_hash, right->input_partition_hash)) && php::identical(left->cache_key_hash, right->cache_key_hash)) && php::identical(left->logical_object_key_hash, right->logical_object_key_hash)) && php::identical(cast<int_t<>>(left->cache_status_id), cast<int_t<>>(right->cache_status_id))) && php::identical(cast<int_t<>>(left->dirty_status_id), cast<int_t<>>(right->dirty_status_id))) && php::identical(cast<int_t<>>(left->object_action_id), cast<int_t<>>(right->object_action_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->owner_key_id), cast<int_t<>>(right->owner_key_id))) && php::identical(cast<int_t<>>(left->source_unit_key_id), cast<int_t<>>(right->source_unit_key_id))) && php::identical(cast<int_t<>>(left->partition_key_id), cast<int_t<>>(right->partition_key_id))) && php::identical(cast<int_t<>>(left->backend_row_key_id), cast<int_t<>>(right->backend_row_key_id))) && php::identical(cast<int_t<>>(left->output_object_key_id), cast<int_t<>>(right->output_object_key_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
bool_t __latency_fn_backend_object_link_cache_object_cache_rows_match(shared_p<BackendObjectLinkCacheArtifact> left, shared_p<BackendObjectLinkCacheArtifact> right) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_cache_rows_match", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[40]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->object_count), cast<int_t<>>(right->object_count))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(left->objects)))) {
		if (static_cast<bool>((!__latency_fn_backend_object_link_cache_object_rows_match(left->objects[index], right->objects[index])))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<ObjectOutputWorkerInput> __latency_fn_backend_object_link_cache_object_worker_input_from_partition(int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> cacheObjectId, BackendPartitionExecutionRow partition) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_worker_input_from_partition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[41]);
	shared_p<ObjectOutputWorkerInput> input = create<ObjectOutputWorkerInput>();
	input->owner_run_id = cast<int_t<>>(ownerRunId);
	input->cache_object_id = cast<int_t<>>(cacheObjectId);
	input->execution_id = cast<int_t<>>(partition->execution_id);
	input->owner_symbol_id = cast<int_t<>>(partition->owner_symbol_id);
	input->source_unit_id = cast<int_t<>>(partition->source_unit_id);
	input->object_action_kind_id = cast<int_t<>>(partition->object_action_kind_id);
	input->execution_status_id = cast<int_t<>>(partition->execution_status_id);
	input->blocked_reason_id = cast<int_t<>>(partition->blocked_reason_id);
	input->owner_key_id = cast<int_t<>>(partition->owner_key_id);
	input->source_unit_key_id = cast<int_t<>>(partition->source_unit_key_id);
	input->partition_key_id = cast<int_t<>>(partition->partition_key_id);
	input->backend_row_key_id = cast<int_t<>>(partition->backend_row_key_id);
	input->logical_llvm_function_name_id = cast<int_t<>>(partition->logical_llvm_function_name_id);
	input->emitted_llvm_function_name_id = cast<int_t<>>(partition->emitted_llvm_function_name_id);
	input->emission_source_key_id = cast<int_t<>>(partition->emission_source_key_id);
	input->input_surface_key_id = cast<int_t<>>(partition->input_surface_key_id);
	input->output_object_key_id = cast<int_t<>>(partition->output_object_key_id);
	return input;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
vector_t<shared_p<ObjectOutputWorkerInput>> __latency_fn_backend_object_link_cache_object_worker_inputs_from_partitions(int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_worker_inputs_from_partitions", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[42]);
	vector_t<shared_p<ObjectOutputWorkerInput>> inputs = {};
	php::vector_reserve(inputs, cast<int_t<>>(partitions->partition_count));
	auto __latency_local_0 = partitions->partitions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto partition = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_backend_object_link_cache_object_worker_input_from_partition(cast<int_t<std::uint32_t>>(ownerRunId), __latency_fn_structure_row_ids_next_dense_id(php::count(inputs)), partition);
		(void) inputs.push_back(__latency_local_2);
		}
	}
	return inputs;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendPartitionExecutionRow __latency_fn_backend_object_link_cache_partition_from_object_worker_input(shared_p<ObjectOutputWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::partition_from_object_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[43]);
	BackendPartitionExecutionRow row = BackendPartitionExecutionRow{};
	row->execution_id = __latency_fn_structure_row_ids_uint32_from_int(input->execution_id);
	row->owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->owner_symbol_id);
	row->source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_id);
	row->object_action_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->object_action_kind_id);
	row->execution_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->execution_status_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_reason_id);
	row->owner_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->owner_key_id);
	row->source_unit_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->source_unit_key_id);
	row->partition_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->partition_key_id);
	row->backend_row_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->backend_row_key_id);
	row->logical_llvm_function_name_id = __latency_fn_structure_row_ids_uint16_from_int(input->logical_llvm_function_name_id);
	row->emitted_llvm_function_name_id = __latency_fn_structure_row_ids_uint16_from_int(input->emitted_llvm_function_name_id);
	row->emission_source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->emission_source_key_id);
	row->input_surface_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->input_surface_key_id);
	row->output_object_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->output_object_key_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<ObjectOutputWorkerResult> __latency_fn_backend_object_link_cache_object_worker_result_from_row(BackendObjectCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_worker_result_from_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[44]);
	shared_p<ObjectOutputWorkerResult> result = create<ObjectOutputWorkerResult>();
	result->cache_object_id = cast<int_t<>>(row->cache_object_id);
	result->partition_execution_id = cast<int_t<>>(row->partition_execution_id);
	result->owner_symbol_id = cast<int_t<>>(row->owner_symbol_id);
	result->source_unit_id = cast<int_t<>>(row->source_unit_id);
	result->input_partition_hash = row->input_partition_hash;
	result->cache_key_hash = row->cache_key_hash;
	result->logical_object_key_hash = row->logical_object_key_hash;
	result->cache_status_id = cast<int_t<>>(row->cache_status_id);
	result->dirty_status_id = cast<int_t<>>(row->dirty_status_id);
	result->object_action_id = cast<int_t<>>(row->object_action_id);
	result->status_id = cast<int_t<>>(row->status_id);
	result->owner_key_id = cast<int_t<>>(row->owner_key_id);
	result->source_unit_key_id = cast<int_t<>>(row->source_unit_key_id);
	result->partition_key_id = cast<int_t<>>(row->partition_key_id);
	result->backend_row_key_id = cast<int_t<>>(row->backend_row_key_id);
	result->output_object_key_id = cast<int_t<>>(row->output_object_key_id);
	return result;
}

}
