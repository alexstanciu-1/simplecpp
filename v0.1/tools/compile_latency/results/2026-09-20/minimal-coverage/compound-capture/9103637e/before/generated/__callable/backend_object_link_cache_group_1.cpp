#include <scpp/lang/php.hpp>
#include "__types/BackendLinkCacheDecisionRow.hpp"
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__types/BackendObjectLinkCacheArtifact.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_artifact_kind_object_link_cache_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_model_logical_proof_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_source_model_backend_partitions_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_key_hash_for_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_logical_object_key_hash_for_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_key_hash_for_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_status_miss_compile_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_dirty_status_dirty_input_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_dirty_status_reuse_unknown_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_logical_object_key_hash_for_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_action_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_action_compile_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_row_from_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_execution_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_partition_stable_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_append_object.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_action_compile_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_action_reuse_deferred_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_set_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_command_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_cache_key_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_cache_status_miss_compile_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_command_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_action_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_action_relink_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_cache_key_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_row_from_execution.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_set_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_relink_reason_initial_object_set_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_relink_reason_object_partition_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_execution_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_object_link_cache_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_object_link_cache_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<BackendObjectLinkCacheArtifact> __latency_fn_backend_object_link_cache_new_artifact(int_t<> objectCapacity, int_t<> linkCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[18]);
	shared_p<BackendObjectLinkCacheArtifact> artifact = create<BackendObjectLinkCacheArtifact>();
	artifact->artifact_kind_id = __latency_fn_backend_object_link_cache_artifact_kind_object_link_cache_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_backend_object_link_cache_source_model_backend_partitions_id();
	artifact->cache_model_id = __latency_fn_backend_object_link_cache_cache_model_logical_proof_id();
	php::vector_reserve(artifact->objects, objectCapacity);
	php::vector_reserve(artifact->links, linkCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_cache_key_hash_for_partition(BackendPartitionExecutionRow partition, int_t<std::uint64_t> inputPartitionHash) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::cache_key_hash_for_partition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[19]);
	string_t identity = required_cast<string_t>((string_t("backend_object_cache:v2:") + cast<string_t>(cast<int_t<>>(partition->execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->source_unit_id)) + string_t(":") + cast<string_t>(inputPartitionHash) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->partition_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->backend_row_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->input_surface_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->output_object_key_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_logical_object_key_hash_for_partition(BackendPartitionExecutionRow partition) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::logical_object_key_hash_for_partition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[20]);
	string_t identity = required_cast<string_t>((string_t("backend_object_key:v2:") + cast<string_t>(cast<int_t<>>(partition->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(partition->output_object_key_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendObjectCacheDecisionRow __latency_fn_backend_object_link_cache_object_row_from_partition(int_t<std::uint32_t> cacheObjectId, BackendPartitionExecutionRow partition) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_row_from_partition", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[21]);
	BackendObjectCacheDecisionRow row = BackendObjectCacheDecisionRow{};
	row->cache_object_id = cacheObjectId;
	row->partition_execution_id = partition->execution_id;
	row->owner_symbol_id = partition->owner_symbol_id;
	row->source_unit_id = partition->source_unit_id;
	row->input_partition_hash = __latency_fn_backend_partition_readiness_partition_stable_hash(partition);
	row->cache_key_hash = __latency_fn_backend_object_link_cache_cache_key_hash_for_partition(partition, row->input_partition_hash);
	row->logical_object_key_hash = __latency_fn_backend_object_link_cache_logical_object_key_hash_for_partition(partition);
	row->owner_key_id = partition->owner_key_id;
	row->source_unit_key_id = partition->source_unit_key_id;
	row->partition_key_id = partition->partition_key_id;
	row->backend_row_key_id = partition->backend_row_key_id;
	row->output_object_key_id = partition->output_object_key_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(partition->execution_status_id), cast<int_t<>>(__latency_fn_backend_partition_readiness_execution_status_ready_id())))) {
		row->cache_status_id = __latency_fn_backend_object_link_cache_cache_status_miss_compile_deferred_id();
		row->dirty_status_id = __latency_fn_backend_object_link_cache_dirty_status_dirty_input_id();
		row->object_action_id = __latency_fn_backend_object_link_cache_object_action_compile_deferred_id();
		row->status_id = __latency_fn_backend_object_link_cache_status_ready_id();
		return row;
	}
	row->cache_status_id = __latency_fn_backend_object_link_cache_cache_status_blocked_id();
	row->dirty_status_id = __latency_fn_backend_object_link_cache_dirty_status_reuse_unknown_id();
	row->object_action_id = __latency_fn_backend_object_link_cache_object_action_blocked_id();
	row->status_id = __latency_fn_backend_object_link_cache_status_blocked_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
void __latency_fn_backend_object_link_cache_append_object(shared_p<BackendObjectLinkCacheArtifact>& artifact, BackendObjectCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::append_object", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[22]);
	(void) artifact->objects.append(row);
	artifact->object_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->objects));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->object_action_id), cast<int_t<>>(__latency_fn_backend_object_link_cache_object_action_compile_deferred_id())))) {
		artifact->object_compile_deferred_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->object_compile_deferred_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->object_action_id), cast<int_t<>>(__latency_fn_backend_object_link_cache_object_action_reuse_deferred_id())))) {
		artifact->object_reuse_deferred_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->object_reuse_deferred_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->object_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->object_blocked_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_object_set_hash(shared_p<BackendObjectLinkCacheArtifact> artifact, BackendProjectLinkExecutionRow link) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_set_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[23]);
	string_t identity = required_cast<string_t>((string_t("backend_object_set:v2:") + cast<string_t>(cast<int_t<>>(link->link_execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->object_set_shape_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->object_partition_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->ready_partition_count))));
	auto __latency_local_0 = artifact->objects;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto objectRow = __latency_local_1.value_copy();
		identity = (cast<string_t>(identity) + string_t(":") + cast<string_t>(cast<int_t<>>(objectRow->cache_object_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(objectRow->partition_execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(objectRow->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(objectRow->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(objectRow->status_id)));
	}
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_command_hash(BackendProjectLinkExecutionRow link) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::command_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[24]);
	string_t identity = required_cast<string_t>((string_t("backend_link_command:v2:") + cast<string_t>(cast<int_t<>>(link->program_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->object_set_shape_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->command_surface_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_link_cache_key_hash(BackendProjectLinkExecutionRow link, shared_p<BackendObjectLinkCacheArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_cache_key_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[25]);
	string_t identity = required_cast<string_t>((string_t("backend_link_cache:v2:") + cast<string_t>(cast<int_t<>>(link->link_execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->program_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->object_partition_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(link->ready_partition_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->object_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->object_blocked_count))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendLinkCacheDecisionRow __latency_fn_backend_object_link_cache_link_row_from_execution(int_t<std::uint32_t> cacheLinkId, BackendProjectLinkExecutionRow link, shared_p<BackendObjectLinkCacheArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_row_from_execution", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[26]);
	BackendLinkCacheDecisionRow row = BackendLinkCacheDecisionRow{};
	row->cache_link_id = cacheLinkId;
	row->link_execution_id = link->link_execution_id;
	row->program_id = link->program_id;
	row->object_set_shape_id = link->object_set_shape_id;
	row->object_set_node_count = link->object_set_node_count;
	row->object_set_edge_count = link->object_set_edge_count;
	row->object_partition_count = link->object_partition_count;
	row->ready_partition_count = link->ready_partition_count;
	row->object_set_hash = __latency_fn_backend_object_link_cache_object_set_hash(artifact, link);
	row->command_hash = __latency_fn_backend_object_link_cache_command_hash(link);
	row->link_cache_key_hash = __latency_fn_backend_object_link_cache_link_cache_key_hash(link, artifact);
	row->command_surface_id = link->command_surface_id;
	if (static_cast<bool>((php::identical(cast<int_t<>>(link->execution_status_id), cast<int_t<>>(__latency_fn_backend_partition_readiness_execution_status_ready_id())) && php::identical(cast<int_t<>>(artifact->object_blocked_count), static_cast<int_t<> >(0))))) {
		row->cache_status_id = __latency_fn_backend_object_link_cache_cache_status_miss_compile_deferred_id();
		row->relink_reason_id = __latency_fn_backend_object_link_cache_relink_reason_initial_object_set_id();
		row->link_action_id = __latency_fn_backend_object_link_cache_link_action_relink_deferred_id();
		row->status_id = __latency_fn_backend_object_link_cache_status_ready_id();
		return row;
	}
	row->cache_status_id = __latency_fn_backend_object_link_cache_cache_status_blocked_id();
	row->relink_reason_id = __latency_fn_backend_object_link_cache_relink_reason_object_partition_blocked_id();
	row->link_action_id = __latency_fn_backend_object_link_cache_link_action_blocked_id();
	row->status_id = __latency_fn_backend_object_link_cache_status_blocked_id();
	return row;
}

}
