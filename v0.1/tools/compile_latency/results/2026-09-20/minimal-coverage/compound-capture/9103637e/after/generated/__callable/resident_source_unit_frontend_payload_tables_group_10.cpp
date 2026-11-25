#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadAdoptionRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadArenaContractRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_rows_from_descriptor_install_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_partition_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_for_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_partition_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_adoption_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_adoption_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_required_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_adoptable_payload_segment_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadAdoptionRow __latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_from_descriptor_install_row(ResidentSourceUnitFrontendPayloadTableRow payloadTable, ResidentSourceUnitFrontendPayloadArenaContractRow contractRow, int_t<> adoptionRowId, int_t<std::uint32_t> publishedRowFirstId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_row_from_descriptor_install_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[104]);
	ResidentSourceUnitFrontendPayloadAdoptionRow row = ResidentSourceUnitFrontendPayloadAdoptionRow{};
	row->adoption_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(adoptionRowId);
	row->owner_run_id = payloadTable->owner_run_id;
	row->source_unit_id = payloadTable->source_unit_id;
	row->source_unit_key_id = payloadTable->source_unit_key_id;
	row->worker_id = payloadTable->worker_id;
	row->payload_table_id = payloadTable->payload_table_id;
	row->token_segment_count = payloadTable->token_segment_count;
	row->frontend_node_segment_count = payloadTable->frontend_node_segment_count;
	row->required_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((cast<int_t<>>(payloadTable->token_segment_count) + cast<int_t<>>(payloadTable->frontend_node_segment_count)));
	row->adopted_payload_segment_count = contractRow->adopted_payload_segment_count;
	row->coordinator_handle_input_count = contractRow->coordinator_handle_input_count;
	row->payload_copy_bytes = contractRow->payload_copy_bytes;
	row->local_row_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = row->adopted_payload_segment_count;
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(5000000) + cast<int_t<>>(payloadTable->source_unit_id)));
	row->published_row_first_id = publishedRowFirstId;
	row->published_row_count = row->local_row_count;
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->payload_status_id = contractRow->payload_status_id;
	row->blocked_reason_id = contractRow->blocked_reason_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_rows_from_descriptor_install_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows, vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& contractRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_rows_from_descriptor_install_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[105]);
	vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow> rows = {};
	php::vector_reserve(rows, php::count(descriptorRows));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> publishedRowFirstId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = descriptorRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		ResidentSourceUnitFrontendPayloadArenaContractRow contractRow = ResidentSourceUnitFrontendPayloadArenaContractRow{};
		if (static_cast<bool>((index < php::count(contractRows)))) {
			contractRow = contractRows.at(index);
		}
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_from_descriptor_install_row(payloadTable, contractRow, (index + static_cast<int_t<> >(1)), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedRowFirstId));
		(void) rows.push_back(__latency_local_2);
		}
		publishedRowFirstId = ((publishedRowFirstId + cast<int_t<>>(payloadTable->token_segment_count)) + cast<int_t<>>(payloadTable->frontend_node_segment_count));
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_resident_source_unit_frontend_payload_tables_adoption_partition_row(ResidentSourceUnitFrontendPayloadAdoptionRow adoptionRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_partition_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[106]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(adoptionRow->metadata_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) || php::not_identical(cast<int_t<>>(adoptionRow->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = adoptionRow->blocked_reason_id;
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(5000) + cast<int_t<>>(adoptionRow->adoption_row_id))), adoptionRow->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), adoptionRow->source_unit_id, __latency_fn_structure_row_ids_none_id(), adoptionRow->adoption_row_id, statusId, blockedReasonId);
	row->input_snapshot_generation = adoptionRow->owner_run_id;
	row->local_row_first_id = adoptionRow->local_row_first_id;
	row->local_row_count = adoptionRow->local_row_count;
	row->merge_order_key = adoptionRow->merge_order_key;
	row->published_row_first_id = adoptionRow->published_row_first_id;
	row->published_row_count = adoptionRow->published_row_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadAdoptionRow __latency_fn_resident_source_unit_frontend_payload_tables_adoption_row_for_source_unit_id(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& adoptionRows, int_t<> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_row_for_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[107]);
	auto& __latency_local_0 = adoptionRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_unit_id), sourceUnitId))) {
			return row;
		}
	}
	ResidentSourceUnitFrontendPayloadAdoptionRow empty = ResidentSourceUnitFrontendPayloadAdoptionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_append_adoption_publication_row(shared_p<PartitionReadinessArtifact>& artifact, ResidentSourceUnitFrontendPayloadAdoptionRow adoptionRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::append_adoption_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[108]);
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_resident_source_unit_frontend_payload_tables_adoption_partition_row(adoptionRow));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_publication_artifact(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& adoptionRows, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[109]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(adoptionRows));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(adoptionRows) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			__latency_fn_resident_source_unit_frontend_payload_tables_append_adoption_publication_row(artifact, adoptionRows.at(index));
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto& __latency_local_0 = adoptionRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto adoptionRow = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_frontend_payload_tables_append_adoption_publication_row(artifact, adoptionRow);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_required_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_required_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[110]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->required_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_adoptable_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_adoptable_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[111]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->adoptable_payload_segment_count));
	}
	return total;
}

}
