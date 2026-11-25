#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_coordinator_adoption_missing_blocker_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_row_from_materialization_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_row_from_materialization_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_rows_from_materialization_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_partition_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_task_payload_segment_adoption_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_partition_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_task_payload_segment_adoption_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_artifact.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_coordinator_adoption_missing_blocker_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_coordinator_adoption_missing_blocker_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[177]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[178]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->published_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[179]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->payload_copy_bytes));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_row_from_materialization_row(ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow materializationRow, int_t<> adoptionPublicationRowId, int_t<std::uint32_t> publishedRowFirstId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_row_from_materialization_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[180]);
	int_t<> orderComponent = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component(materializationRow->source_unit_id, materializationRow->segment_kind_id, cast<int_t<>>(materializationRow->segment_ordinal)));
	ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow row = ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow{};
	row->adoption_publication_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(adoptionPublicationRowId);
	row->owner_run_id = materializationRow->owner_run_id;
	row->source_unit_id = materializationRow->source_unit_id;
	row->source_unit_key_id = materializationRow->source_unit_key_id;
	row->worker_id = materializationRow->worker_id;
	row->payload_table_id = materializationRow->payload_table_id;
	row->arena_id = materializationRow->arena_id;
	row->source_handle_row_id = materializationRow->source_handle_row_id;
	row->request_row_id = materializationRow->request_row_id;
	row->reservation_row_id = materializationRow->reservation_row_id;
	row->materialization_row_id = materializationRow->materialization_row_id;
	row->segment_ordinal = materializationRow->segment_ordinal;
	row->reserved_handle_count = materializationRow->reserved_handle_count;
	row->worker_acquired_handle_count = materializationRow->worker_acquired_handle_count;
	row->task_owned_handle_id = materializationRow->task_owned_handle_id;
	row->worker_payload_segment_id = materializationRow->worker_payload_segment_id;
	row->worker_payload_segment_count = materializationRow->worker_payload_segment_count;
	row->coordinator_adopted_segment_count = materializationRow->worker_payload_segment_count;
	row->published_payload_segment_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(930000) + orderComponent));
	row->published_payload_segment_count = materializationRow->worker_payload_segment_count;
	row->local_row_first_id = materializationRow->worker_payload_segment_id;
	row->local_row_count = materializationRow->worker_payload_segment_count;
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7400000) + orderComponent));
	row->published_row_first_id = publishedRowFirstId;
	row->published_row_count = materializationRow->worker_payload_segment_count;
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->segment_kind_id = materializationRow->segment_kind_id;
	row->materialization_status_id = materializationRow->worker_payload_status_id;
	row->coordinator_adoption_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->publication_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_rows_from_materialization_rows(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& materializationRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_rows_from_materialization_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[181]);
	vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow> rows = {};
	php::vector_reserve(rows, php::count(materializationRows));
	int_t<> adoptionPublicationRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<> publishedRowFirstId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = materializationRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto materializationRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_row_from_materialization_row(materializationRow, adoptionPublicationRowId, __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedRowFirstId));
		(void) rows.push_back(__latency_local_2);
		}
		publishedRowFirstId = (publishedRowFirstId + cast<int_t<>>(materializationRow->worker_payload_segment_count));
		adoptionPublicationRowId = (adoptionPublicationRowId + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_partition_row(ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow adoptionPublicationRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_partition_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[182]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(adoptionPublicationRow->materialization_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) || php::not_identical(cast<int_t<>>(adoptionPublicationRow->coordinator_adoption_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) || php::not_identical(cast<int_t<>>(adoptionPublicationRow->publication_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = adoptionPublicationRow->blocked_reason_id;
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(6000) + cast<int_t<>>(adoptionPublicationRow->adoption_publication_row_id))), adoptionPublicationRow->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), adoptionPublicationRow->source_unit_id, __latency_fn_structure_row_ids_none_id(), adoptionPublicationRow->adoption_publication_row_id, statusId, blockedReasonId);
	row->input_snapshot_generation = adoptionPublicationRow->owner_run_id;
	row->local_row_first_id = adoptionPublicationRow->local_row_first_id;
	row->local_row_count = adoptionPublicationRow->local_row_count;
	row->merge_order_key = adoptionPublicationRow->merge_order_key;
	row->published_row_first_id = adoptionPublicationRow->published_row_first_id;
	row->published_row_count = adoptionPublicationRow->published_row_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_append_task_payload_segment_adoption_publication_row(shared_p<PartitionReadinessArtifact>& artifact, ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow adoptionPublicationRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::append_task_payload_segment_adoption_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[183]);
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_partition_row(adoptionPublicationRow));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_artifact(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& adoptionPublicationRows, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[184]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(adoptionPublicationRows));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(adoptionPublicationRows) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			__latency_fn_resident_source_unit_frontend_payload_tables_append_task_payload_segment_adoption_publication_row(artifact, adoptionPublicationRows.at(index));
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto& __latency_local_0 = adoptionPublicationRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto adoptionPublicationRow = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_frontend_payload_tables_append_task_payload_segment_adoption_publication_row(artifact, adoptionPublicationRow);
	}
	return artifact;
}

}
