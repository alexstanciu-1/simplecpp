#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_consumer_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_index_lookup_probe.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_readiness_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_by_feature_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_index_lookup_probe.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_publication_published_row_count.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_row.hpp"
#include "__callable/__latency_fn_type_capability_readiness_publication_published_row_count.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_row.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_consumer_by_feature_id(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_by_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[164]);
	__latency_fn_type_capability_readiness_build_consumer_lookup_index_if_recommended(artifact);
	int_t<std::uint32_t> featureDenseId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(featureId)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(featureDenseId, cast<int_t<>>(artifact->consumer_lookup_index_slot_count))))) {
		int_t<std::uint32_t> rowId = required_cast<int_t<std::uint32_t>>(artifact->consumer_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)]);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, cast<int_t<>>(artifact->consumer_count))))) {
			CapabilityConsumerRow row = artifact->consumers[__latency_fn_structure_row_ids_dense_index(rowId)];
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)))) {
				__latency_fn_type_capability_readiness_record_index_lookup_probe(artifact);
				return row;
			}
		}
	}
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->consumers;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)))) {
			__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return row;
		}
	}
	__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	CapabilityConsumerRow empty = CapabilityConsumerRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint16_t> featureId, int_t<std::uint32_t> sourceRowId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_by_feature_and_source_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[165]);
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->consumers;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(row->source_row_id), cast<int_t<>>(sourceRowId))))) {
			__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return row;
		}
	}
	__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	CapabilityConsumerRow empty = CapabilityConsumerRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityReadinessRow __latency_fn_type_capability_readiness_readiness_by_feature_id(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_by_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[166]);
	__latency_fn_type_capability_readiness_build_readiness_lookup_index_if_recommended(artifact);
	int_t<std::uint32_t> featureDenseId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(featureId)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(featureDenseId, cast<int_t<>>(artifact->readiness_lookup_index_slot_count))))) {
		int_t<std::uint32_t> rowId = required_cast<int_t<std::uint32_t>>(artifact->readiness_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)]);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, cast<int_t<>>(artifact->readiness_count))))) {
			CapabilityReadinessRow row = artifact->readiness[__latency_fn_structure_row_ids_dense_index(rowId)];
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->consumer_feature_id), cast<int_t<>>(featureId)))) {
				__latency_fn_type_capability_readiness_record_index_lookup_probe(artifact);
				return row;
			}
		}
	}
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->readiness;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->consumer_feature_id), cast<int_t<>>(featureId)))) {
			__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return row;
		}
	}
	__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	CapabilityReadinessRow empty = CapabilityReadinessRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityReadinessRow __latency_fn_type_capability_readiness_readiness_by_feature_and_source_row_id(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint16_t> featureId, int_t<std::uint32_t> sourceRowId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_by_feature_and_source_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[167]);
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->readiness;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->consumer_feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(row->source_row_id), cast<int_t<>>(sourceRowId))))) {
			__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return row;
		}
	}
	__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	CapabilityReadinessRow empty = CapabilityReadinessRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_publication_published_row_count(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::publication_published_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[168]);
	int_t<> total = required_cast<int_t<>>((((cast<int_t<>>(artifact->capability_count) + cast<int_t<>>(artifact->provider_count)) + cast<int_t<>>(artifact->consumer_count)) + cast<int_t<>>(artifact->readiness_count)));
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_type_capability_readiness_capability_readiness_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[169]);
	int_t<std::uint32_t> publishedRowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_publication_published_row_count(artifact));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishedRowCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(10000) + cast<int_t<>>(entrySymbol->symbol_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_symbol_body_id(), entrySymbol->source_unit_id, entrySymbol->symbol_id, entrySymbol->symbol_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = publishedRowCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6300000) + cast<int_t<>>(entrySymbol->symbol_id)));
	row->published_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->published_row_count = publishedRowCount;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_type_capability_readiness_capability_readiness_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<CapabilityCoverageArtifact> coverage) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[170]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_type_capability_readiness_capability_readiness_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, coverage));
	return artifact;
}

}
