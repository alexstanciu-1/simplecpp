#include <scpp/lang/php.hpp>
#include "__types/BackendLinkCacheDecisionRow.hpp"
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__types/BackendObjectLinkCacheArtifact.hpp"
#include "__types/BackendPartitionExecutionArtifact.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_append_link.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_action_relink_deferred_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_action_reuse_deferred_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_append_link.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_append_object.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_from_backend_partitions.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_row_from_execution.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_row_from_partition.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_by_id.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_first_link.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_publication_model_main_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_row.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_simulated_object_output_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_published_row_total.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_modulus.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
void __latency_fn_backend_object_link_cache_append_link(shared_p<BackendObjectLinkCacheArtifact>& artifact, BackendLinkCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::append_link", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[27]);
	(void) artifact->links.append(row);
	artifact->link_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->links));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->link_action_id), cast<int_t<>>(__latency_fn_backend_object_link_cache_link_action_relink_deferred_id())))) {
		artifact->link_relink_deferred_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->link_relink_deferred_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->link_action_id), cast<int_t<>>(__latency_fn_backend_object_link_cache_link_action_reuse_deferred_id())))) {
		artifact->link_reuse_deferred_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->link_reuse_deferred_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->link_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->link_blocked_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<BackendObjectLinkCacheArtifact> __latency_fn_backend_object_link_cache_from_backend_partitions(shared_p<BackendPartitionExecutionArtifact> partitions) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::from_backend_partitions", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[28]);
	shared_p<BackendObjectLinkCacheArtifact> artifact = __latency_fn_backend_object_link_cache_new_artifact(cast<int_t<>>(partitions->partition_count), cast<int_t<>>(partitions->link_count));
	auto __latency_local_0 = partitions->partitions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto partition = __latency_local_1.value_copy();
		__latency_fn_backend_object_link_cache_append_object(artifact, __latency_fn_backend_object_link_cache_object_row_from_partition(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->objects)), partition));
	}
	auto __latency_local_2 = partitions->links;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto link = __latency_local_3.value_copy();
		__latency_fn_backend_object_link_cache_append_link(artifact, __latency_fn_backend_object_link_cache_link_row_from_execution(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->links)), link, artifact));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendObjectCacheDecisionRow __latency_fn_backend_object_link_cache_object_by_id(shared_p<BackendObjectLinkCacheArtifact> artifact, int_t<std::uint32_t> cacheObjectId) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[29]);
	auto __latency_local_0 = artifact->objects;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->cache_object_id), cast<int_t<>>(cacheObjectId)))) {
			return row;
		}
	}
	BackendObjectCacheDecisionRow empty = BackendObjectCacheDecisionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendLinkCacheDecisionRow __latency_fn_backend_object_link_cache_first_link(shared_p<BackendObjectLinkCacheArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::first_link", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[30]);
	if (static_cast<bool>((php::count(artifact->links) > static_cast<int_t<> >(0)))) {
		return artifact->links[static_cast<int_t<> >(0)];
	}
	BackendLinkCacheDecisionRow empty = BackendLinkCacheDecisionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_backend_object_link_cache_object_output_publication_row(int_t<std::uint32_t> ownerRunId, BackendObjectCacheDecisionRow objectRow) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_output_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[31]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(objectRow->cache_object_id), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(14000) + cast<int_t<>>(objectRow->cache_object_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_object_id(), objectRow->source_unit_id, objectRow->owner_symbol_id, objectRow->cache_object_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = objectRow->cache_object_id;
	row->local_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6700000) + cast<int_t<>>(objectRow->cache_object_id)));
	row->published_row_first_id = objectRow->cache_object_id;
	row->published_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->publication_model_id = __latency_fn_partition_readiness_publication_model_main_thread_coordinator_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_backend_object_link_cache_object_output_publication_artifact_from_cache(shared_p<BackendObjectLinkCacheArtifact> cache, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_output_publication_artifact_from_cache", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[32]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(cache->objects));
	auto __latency_local_0 = cache->objects;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto objectRow = __latency_local_1.value_copy();
		__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_backend_object_link_cache_object_output_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), objectRow));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_backend_object_link_cache_simulated_object_output_publication_artifact_from_cache(shared_p<BackendObjectLinkCacheArtifact> cache, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::simulated_object_output_publication_artifact_from_cache", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[33]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(cache->objects));
	int_t<> index = required_cast<int_t<>>((php::count(cache->objects) - static_cast<int_t<> >(1)));
	while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
		__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_backend_object_link_cache_object_output_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), cache->objects[index]));
		index = (index - static_cast<int_t<> >(1));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_object_link_cache_object_output_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_output_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[34]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
			total = (total + cast<int_t<>>(row->published_row_count));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<> __latency_fn_backend_object_link_cache_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[35]);
	return static_cast<int_t<> >(1000000007);
}

}
