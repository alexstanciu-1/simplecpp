#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__types/StorageLifetimeWorkerInput.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_ready_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_is_present.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_blocked_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_is_present.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_published_row_total.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_operation_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_publication_ready_request_count(StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::publication_ready_request_count", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[56]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((__latency_fn_storage_lifetime_readiness_request_is_present(readyRow) && php::identical(cast<int_t<>>(readyRow->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id()))))) {
		total = (total + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>((__latency_fn_storage_lifetime_readiness_request_is_present(blockedRow) && php::identical(cast<int_t<>>(blockedRow->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id()))))) {
		total = (total + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_publication_blocked_request_count(StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::publication_blocked_request_count", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[57]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((__latency_fn_storage_lifetime_readiness_request_is_present(readyRow) && php::identical(cast<int_t<>>(readyRow->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_blocked_id()))))) {
		total = (total + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>((__latency_fn_storage_lifetime_readiness_request_is_present(blockedRow) && php::identical(cast<int_t<>>(blockedRow->readiness_status_id), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_blocked_id()))))) {
		total = (total + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::storage_lifetime_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[58]);
	int_t<std::uint32_t> publishedRowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_request_count(readyRow, blockedRow));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishedRowCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(11000) + cast<int_t<>>(entrySymbol->symbol_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_symbol_body_id(), entrySymbol->source_unit_id, entrySymbol->symbol_id, entrySymbol->symbol_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = publishedRowCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6400000) + cast<int_t<>>(entrySymbol->symbol_id)));
	row->published_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->published_row_count = publishedRowCount;
	return row;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::storage_lifetime_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[59]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, readyRow, blockedRow));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::storage_lifetime_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[60]);
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

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
shared_p<StorageLifetimeWorkerInput> __latency_fn_storage_lifetime_readiness_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, OperationReadiness readyOperation, OperationReadiness blockedOperation, CapabilityConsumerRow readyConsumer, CapabilityConsumerRow blockedConsumer, CapabilityProviderRow provider) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::worker_input_from_rows", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[61]);
	shared_p<StorageLifetimeWorkerInput> input = create<StorageLifetimeWorkerInput>();
	input->owner_run_id = cast<int_t<>>(ownerRunId);
	input->source_unit_id = cast<int_t<>>(entrySymbol->source_unit_id);
	input->symbol_id = cast<int_t<>>(entrySymbol->symbol_id);
	input->ready_operation_contract_id = cast<int_t<>>(readyOperation->contract_id);
	input->ready_operation_lowering_adapter_id = cast<int_t<>>(readyOperation->lowering_adapter_id);
	input->ready_operation_source_row_id = cast<int_t<>>(readyOperation->source_row_id);
	input->ready_operation_kind_id = cast<int_t<>>(readyOperation->operation_kind_id);
	input->ready_operation_capability_id = cast<int_t<>>(readyOperation->capability_id);
	input->ready_operation_consumer_feature_id = cast<int_t<>>(readyOperation->consumer_feature_id);
	input->ready_operation_provider_type_ref_id = cast<int_t<>>(readyOperation->provider_type_ref_id);
	input->ready_operation_status_id = cast<int_t<>>(readyOperation->status_id);
	input->ready_operation_blocked_reason_id = cast<int_t<>>(readyOperation->blocked_reason_id);
	input->ready_operation_result_type_ref_id = cast<int_t<>>(readyOperation->result_type_ref_id);
	input->blocked_operation_contract_id = cast<int_t<>>(blockedOperation->contract_id);
	input->blocked_operation_lowering_adapter_id = cast<int_t<>>(blockedOperation->lowering_adapter_id);
	input->blocked_operation_source_row_id = cast<int_t<>>(blockedOperation->source_row_id);
	input->blocked_operation_kind_id = cast<int_t<>>(blockedOperation->operation_kind_id);
	input->blocked_operation_capability_id = cast<int_t<>>(blockedOperation->capability_id);
	input->blocked_operation_consumer_feature_id = cast<int_t<>>(blockedOperation->consumer_feature_id);
	input->blocked_operation_provider_type_ref_id = cast<int_t<>>(blockedOperation->provider_type_ref_id);
	input->blocked_operation_status_id = cast<int_t<>>(blockedOperation->status_id);
	input->blocked_operation_blocked_reason_id = cast<int_t<>>(blockedOperation->blocked_reason_id);
	input->blocked_operation_result_type_ref_id = cast<int_t<>>(blockedOperation->result_type_ref_id);
	input->ready_consumer_capability_id = cast<int_t<>>(readyConsumer->capability_id);
	input->ready_consumer_source_row_id = cast<int_t<>>(readyConsumer->source_row_id);
	input->ready_consumer_provider_source_row_id = cast<int_t<>>(readyConsumer->provider_source_row_id);
	input->ready_consumer_feature_id = cast<int_t<>>(readyConsumer->feature_id);
	input->ready_consumer_source_key_id = cast<int_t<>>(readyConsumer->source_key_id);
	input->ready_consumer_provider_source_key_id = cast<int_t<>>(readyConsumer->provider_source_key_id);
	input->ready_consumer_provider_type_ref_id = cast<int_t<>>(readyConsumer->provider_type_ref_id);
	input->ready_consumer_status_id = cast<int_t<>>(readyConsumer->status_id);
	input->ready_consumer_blocked_reason_id = cast<int_t<>>(readyConsumer->blocked_reason_id);
	input->blocked_consumer_capability_id = cast<int_t<>>(blockedConsumer->capability_id);
	input->blocked_consumer_source_row_id = cast<int_t<>>(blockedConsumer->source_row_id);
	input->blocked_consumer_provider_source_row_id = cast<int_t<>>(blockedConsumer->provider_source_row_id);
	input->blocked_consumer_feature_id = cast<int_t<>>(blockedConsumer->feature_id);
	input->blocked_consumer_source_key_id = cast<int_t<>>(blockedConsumer->source_key_id);
	input->blocked_consumer_provider_source_key_id = cast<int_t<>>(blockedConsumer->provider_source_key_id);
	input->blocked_consumer_provider_type_ref_id = cast<int_t<>>(blockedConsumer->provider_type_ref_id);
	input->blocked_consumer_status_id = cast<int_t<>>(blockedConsumer->status_id);
	input->blocked_consumer_blocked_reason_id = cast<int_t<>>(blockedConsumer->blocked_reason_id);
	input->provider_capability_id = cast<int_t<>>(provider->capability_id);
	input->provider_source_row_id = cast<int_t<>>(provider->source_row_id);
	input->provider_type_ref_id = cast<int_t<>>(provider->type_ref_id);
	input->provider_source_key_id = cast<int_t<>>(provider->source_key_id);
	input->provider_status_id = cast<int_t<>>(provider->status_id);
	input->provider_adapter_id = cast<int_t<>>(provider->adapter_id);
	input->provider_evidence_id = cast<int_t<>>(provider->evidence_id);
	input->provider_blocked_reason_id = cast<int_t<>>(provider->blocked_reason_id);
	return input;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
OperationReadiness __latency_fn_storage_lifetime_readiness_operation_from_worker_input(shared_p<StorageLifetimeWorkerInput> input, bool_t ready) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::operation_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[62]);
	OperationReadiness row = OperationReadiness{};
	if (static_cast<bool>(php::condition_truthy(ready))) {
		row->contract_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_contract_id);
		row->lowering_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_lowering_adapter_id);
		row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_operation_source_row_id);
		row->operation_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_kind_id);
		row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_capability_id);
		row->consumer_feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_consumer_feature_id);
		row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_operation_provider_type_ref_id);
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_status_id);
		row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_operation_blocked_reason_id);
		row->result_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_operation_result_type_ref_id);
		return row;
	}
	row->contract_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_contract_id);
	row->lowering_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_lowering_adapter_id);
	row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_operation_source_row_id);
	row->operation_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_kind_id);
	row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_capability_id);
	row->consumer_feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_consumer_feature_id);
	row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_operation_provider_type_ref_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_status_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_operation_blocked_reason_id);
	row->result_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_operation_result_type_ref_id);
	return row;
}

}
