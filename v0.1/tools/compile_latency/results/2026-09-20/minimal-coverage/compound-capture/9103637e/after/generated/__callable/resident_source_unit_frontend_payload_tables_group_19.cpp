#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInstallPreflightRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_kind_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_worker_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_coordinator_adopted_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_coordinator_adoption_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_max_source_unit_id_for_descriptor_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_row_from_descriptor_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_max_source_unit_id_for_descriptor_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_row_from_descriptor_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_rows_from_descriptor_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_kind_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows, int_t<std::uint16_t> segmentKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_kind_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[185]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->segment_kind_id), cast<int_t<>>(segmentKindId)))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_worker_payload_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_worker_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[186]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->materialization_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_coordinator_adopted_segment_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_coordinator_adopted_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[187]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->coordinator_adopted_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_coordinator_adoption_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_coordinator_adoption_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[188]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->coordinator_adoption_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[189]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_adoption_publication_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_adoption_publication_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[190]);
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
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_max_source_unit_id_for_descriptor_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::max_source_unit_id_for_descriptor_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[191]);
	int_t<> maxSourceUnitId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptorRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptorRow = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(descriptorRow->source_unit_id) > maxSourceUnitId))) {
			maxSourceUnitId = cast<int_t<>>(descriptorRow->source_unit_id);
		}
	}
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(maxSourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitFrontendWorkerPayloadInstallPreflightRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_row_from_descriptor_row(ResidentSourceUnitFrontendPayloadTableRow descriptorRow, int_t<> preflightRowId, int_t<> adoptionPublicationRowCount, int_t<> workerPayloadReadyRows, int_t<> coordinatorAdoptedSegments, int_t<> publishedPayloadSegments, int_t<> payloadCopyBytes) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_install_preflight_row_from_descriptor_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[192]);
	int_t<> requiredPayloadSegments = required_cast<int_t<>>((cast<int_t<>>(descriptorRow->token_segment_count) + cast<int_t<>>(descriptorRow->frontend_node_segment_count)));
	bool_t adoptionEvidenceReady = required_cast<bool_t>(((((((requiredPayloadSegments > static_cast<int_t<> >(0)) && (adoptionPublicationRowCount >= requiredPayloadSegments)) && php::identical(workerPayloadReadyRows, adoptionPublicationRowCount)) && (coordinatorAdoptedSegments >= requiredPayloadSegments)) && (publishedPayloadSegments >= requiredPayloadSegments)) && php::identical(payloadCopyBytes, static_cast<int_t<> >(0))));
	SourceUnitFrontendWorkerPayloadInstallPreflightRow row = SourceUnitFrontendWorkerPayloadInstallPreflightRow{};
	row->preflight_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(preflightRowId);
	row->owner_run_id = descriptorRow->owner_run_id;
	row->source_unit_id = descriptorRow->source_unit_id;
	row->source_unit_key_id = descriptorRow->source_unit_key_id;
	row->worker_id = descriptorRow->worker_id;
	row->payload_table_id = descriptorRow->payload_table_id;
	row->required_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredPayloadSegments);
	row->adoption_publication_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(adoptionPublicationRowCount);
	row->worker_payload_ready_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerPayloadReadyRows);
	row->coordinator_adopted_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(coordinatorAdoptedSegments);
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedPayloadSegments);
	row->metadata_commit_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(sizeof(SourceUnitFrontendWorkerPayloadInstallPreflightRow)));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(payloadCopyBytes);
	row->metadata_status_id = descriptorRow->status_id;
	if (static_cast<bool>(php::condition_truthy(adoptionEvidenceReady))) {
		row->worker_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
		row->coordinator_adoption_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id();
	}
	else {
		row->worker_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
		row->coordinator_adoption_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id();
	}
	row->production_replacement_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->install_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow> __latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_rows_from_descriptor_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows, vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& adoptionPublicationRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_install_preflight_rows_from_descriptor_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[193]);
	vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow> rows = {};
	php::vector_reserve(rows, php::count(descriptorRows));
	vector_t<int_t<std::uint32_t>> sourceUnitSlotIds = {};
	int_t<std::uint32_t> maxSourceUnitId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_max_source_unit_id_for_descriptor_rows(descriptorRows));
	php::vector_reserve(sourceUnitSlotIds, cast<int_t<>>(maxSourceUnitId));
	while (static_cast<bool>((php::count(sourceUnitSlotIds) < cast<int_t<>>(maxSourceUnitId)))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) sourceUnitSlotIds.push_back(__latency_local_0);
		}
	}
	vector_t<int_t<>> adoptionPublicationRowCounts = {};
	vector_t<int_t<>> workerPayloadReadyRowCounts = {};
	vector_t<int_t<>> coordinatorAdoptedSegmentCounts = {};
	vector_t<int_t<>> publishedPayloadSegmentCounts = {};
	vector_t<int_t<>> payloadCopyByteCounts = {};
	php::vector_reserve(adoptionPublicationRowCounts, php::count(descriptorRows));
	php::vector_reserve(workerPayloadReadyRowCounts, php::count(descriptorRows));
	php::vector_reserve(coordinatorAdoptedSegmentCounts, php::count(descriptorRows));
	php::vector_reserve(publishedPayloadSegmentCounts, php::count(descriptorRows));
	php::vector_reserve(payloadCopyByteCounts, php::count(descriptorRows));
	int_t<> descriptorIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_1 = descriptorRows;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto descriptorRow = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(descriptorRow->source_unit_id, php::count(sourceUnitSlotIds))))) {
			sourceUnitSlotIds.at(__latency_fn_structure_row_ids_dense_index(descriptorRow->source_unit_id)) = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((descriptorIndex + static_cast<int_t<> >(1)));
		}
		(void) adoptionPublicationRowCounts.push_back(static_cast<int_t<> >(0));
		(void) workerPayloadReadyRowCounts.push_back(static_cast<int_t<> >(0));
		(void) coordinatorAdoptedSegmentCounts.push_back(static_cast<int_t<> >(0));
		(void) publishedPayloadSegmentCounts.push_back(static_cast<int_t<> >(0));
		(void) payloadCopyByteCounts.push_back(static_cast<int_t<> >(0));
		descriptorIndex = (descriptorIndex + static_cast<int_t<> >(1));
	}
	auto& __latency_local_3 = adoptionPublicationRows;
	for (auto __latency_local_4 : foreach_range(__latency_local_3)) {
		auto adoptionPublicationRow = __latency_local_4.value_copy();
		if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(adoptionPublicationRow->source_unit_id, php::count(sourceUnitSlotIds))))) {
			continue;
		}
		int_t<std::uint32_t> slotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(sourceUnitSlotIds.at(__latency_fn_structure_row_ids_dense_index(adoptionPublicationRow->source_unit_id))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(slotId), static_cast<int_t<> >(0)))) {
			continue;
		}
		int_t<> slotIndex = required_cast<int_t<>>((cast<int_t<>>(slotId) - static_cast<int_t<> >(1)));
		adoptionPublicationRowCounts.at(slotIndex) = (adoptionPublicationRowCounts.at(slotIndex) + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(adoptionPublicationRow->materialization_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			workerPayloadReadyRowCounts.at(slotIndex) = (workerPayloadReadyRowCounts.at(slotIndex) + static_cast<int_t<> >(1));
		}
		coordinatorAdoptedSegmentCounts.at(slotIndex) = (coordinatorAdoptedSegmentCounts.at(slotIndex) + cast<int_t<>>(adoptionPublicationRow->coordinator_adopted_segment_count));
		publishedPayloadSegmentCounts.at(slotIndex) = (publishedPayloadSegmentCounts.at(slotIndex) + cast<int_t<>>(adoptionPublicationRow->published_payload_segment_count));
		payloadCopyByteCounts.at(slotIndex) = (payloadCopyByteCounts.at(slotIndex) + cast<int_t<>>(adoptionPublicationRow->payload_copy_bytes));
	}
	int_t<> preflightRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<> rowIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_5 = descriptorRows;
	for (auto __latency_local_6 : foreach_range(__latency_local_5)) {
		auto descriptorRow = __latency_local_6.value_copy();
		{
		auto __latency_local_7 = __latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_row_from_descriptor_row(descriptorRow, preflightRowId, adoptionPublicationRowCounts.at(rowIndex), workerPayloadReadyRowCounts.at(rowIndex), coordinatorAdoptedSegmentCounts.at(rowIndex), publishedPayloadSegmentCounts.at(rowIndex), payloadCopyByteCounts.at(rowIndex));
		(void) rows.push_back(__latency_local_7);
		}
		preflightRowId = (preflightRowId + static_cast<int_t<> >(1));
		rowIndex = (rowIndex + static_cast<int_t<> >(1));
	}
	return rows;
}

}
