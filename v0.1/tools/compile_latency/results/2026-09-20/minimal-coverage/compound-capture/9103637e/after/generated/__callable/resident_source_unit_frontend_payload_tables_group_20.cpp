#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInstallPreflightRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadReplacementMaterializerRow.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_adoption_publication_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_blocked_reason_production_payload_replacement_missing.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_coordinator_adopted_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_install_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_install_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_installed_payload_source_coordinator.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_installed_payload_source_worker.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_metadata_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_production_replacement_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_production_replacement_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_published_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_required_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_worker_payload_ready_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_rows_from_descriptor_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_install_preflight_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_table_for_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_row_from_preflight_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_table_for_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_install_preflight_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows, vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentAdoptionPublicationRow>& adoptionPublicationRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_payload_install_preflight_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[194]);
	if (static_cast<bool>((php::count(descriptorRows) <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow> preflightRows = required_cast<vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_rows_from_descriptor_rows(descriptorRows, adoptionPublicationRows));
	int_t<> requiredPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> adoptionPublicationRowCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> workerPayloadReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> coordinatorAdoptedSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> publishedPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> metadataReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionReplacementReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionReplacementBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> replacementMissingBlockers = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedCoordinatorRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedWorkerRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> metadataCommitBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> payloadCopyBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = preflightRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto preflightRow = __latency_local_1.value_copy();
		requiredPayloadSegments = (requiredPayloadSegments + cast<int_t<>>(preflightRow->required_payload_segment_count));
		adoptionPublicationRowCount = (adoptionPublicationRowCount + cast<int_t<>>(preflightRow->adoption_publication_row_count));
		workerPayloadReadyRows = (workerPayloadReadyRows + cast<int_t<>>(preflightRow->worker_payload_ready_row_count));
		coordinatorAdoptedSegments = (coordinatorAdoptedSegments + cast<int_t<>>(preflightRow->coordinator_adopted_segment_count));
		publishedPayloadSegments = (publishedPayloadSegments + cast<int_t<>>(preflightRow->published_payload_segment_count));
		metadataCommitBytes = (metadataCommitBytes + cast<int_t<>>(preflightRow->metadata_commit_bytes));
		payloadCopyBytes = (payloadCopyBytes + cast<int_t<>>(preflightRow->payload_copy_bytes));
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->metadata_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			metadataReadyRows = (metadataReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->production_replacement_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			productionReplacementReadyRows = (productionReplacementReadyRows + static_cast<int_t<> >(1));
		}
		else {
			productionReplacementBlockedRows = (productionReplacementBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->install_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			installReadyRows = (installReadyRows + static_cast<int_t<> >(1));
		}
		else {
			installBlockedRows = (installBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id())))) {
			replacementMissingBlockers = (replacementMissingBlockers + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id())))) {
			installedCoordinatorRows = (installedCoordinatorRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflightRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id())))) {
			installedWorkerRows = (installedWorkerRows + static_cast<int_t<> >(1));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(preflightRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_required_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_adoption_publication_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(adoptionPublicationRowCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_worker_payload_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerPayloadReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_coordinator_adopted_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(coordinatorAdoptedSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_published_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_metadata_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_production_replacement_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionReplacementReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_production_replacement_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionReplacementBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_install_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_install_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_blocked_reason_production_payload_replacement_missing(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(replacementMissingBlockers));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_installed_payload_source_coordinator(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedCoordinatorRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_installed_payload_source_worker(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedWorkerRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataCommitBytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_preflight_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(payloadCopyBytes));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_table_for_source_unit(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_table_for_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[195]);
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(payloadTable->source_unit_id), cast<int_t<>>(sourceUnitId)))) {
			return payloadTable;
		}
	}
	ResidentSourceUnitFrontendPayloadTableRow empty = ResidentSourceUnitFrontendPayloadTableRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitFrontendWorkerPayloadReplacementMaterializerRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_row_from_preflight_row(SourceUnitFrontendWorkerPayloadInstallPreflightRow preflightRow, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& coordinatorPayloadTables, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& workerPayloadTables, int_t<> materializerRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_replacement_materializer_row_from_preflight_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[196]);
	ResidentSourceUnitFrontendPayloadTableRow coordinatorPayloadTable = __latency_fn_resident_source_unit_frontend_payload_tables_payload_table_for_source_unit(coordinatorPayloadTables, preflightRow->source_unit_id);
	ResidentSourceUnitFrontendPayloadTableRow workerPayloadTable = __latency_fn_resident_source_unit_frontend_payload_tables_payload_table_for_source_unit(workerPayloadTables, preflightRow->source_unit_id);
	bool_t preflightReady = required_cast<bool_t>(((php::identical(cast<int_t<>>(preflightRow->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) && php::identical(cast<int_t<>>(preflightRow->coordinator_adoption_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && php::identical(cast<int_t<>>(preflightRow->payload_copy_bytes), static_cast<int_t<> >(0))));
	bool_t destinationSlotReady = required_cast<bool_t>((((cast<int_t<>>(coordinatorPayloadTable->payload_table_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(coordinatorPayloadTable->token_list_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(coordinatorPayloadTable->frontend_node_list_id) > static_cast<int_t<> >(0))));
	bool_t workerPayloadReady = required_cast<bool_t>((((((cast<int_t<>>(workerPayloadTable->payload_table_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(workerPayloadTable->token_list_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(workerPayloadTable->frontend_node_list_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(preflightRow->published_payload_segment_count) >= cast<int_t<>>(preflightRow->required_payload_segment_count))) && preflightReady));
	SourceUnitFrontendWorkerPayloadReplacementMaterializerRow row = SourceUnitFrontendWorkerPayloadReplacementMaterializerRow{};
	row->materializer_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(materializerRowId);
	row->owner_run_id = preflightRow->owner_run_id;
	row->source_unit_id = preflightRow->source_unit_id;
	row->source_unit_key_id = preflightRow->source_unit_key_id;
	row->worker_id = preflightRow->worker_id;
	row->preflight_row_id = preflightRow->preflight_row_id;
	row->worker_payload_table_id = workerPayloadTable->payload_table_id;
	row->coordinator_payload_table_id = coordinatorPayloadTable->payload_table_id;
	row->worker_token_list_id = workerPayloadTable->token_list_id;
	row->worker_frontend_node_list_id = workerPayloadTable->frontend_node_list_id;
	row->destination_token_list_id = coordinatorPayloadTable->token_list_id;
	row->destination_frontend_node_list_id = coordinatorPayloadTable->frontend_node_list_id;
	row->required_payload_segment_count = preflightRow->required_payload_segment_count;
	row->published_payload_segment_count = preflightRow->published_payload_segment_count;
	row->metadata_commit_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(sizeof(SourceUnitFrontendWorkerPayloadReplacementMaterializerRow)));
	row->payload_copy_bytes = preflightRow->payload_copy_bytes;
	if (static_cast<bool>(php::condition_truthy(preflightReady))) {
		row->preflight_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	}
	else {
		row->preflight_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	}
	if (static_cast<bool>(php::condition_truthy(destinationSlotReady))) {
		row->destination_slot_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	}
	else {
		row->destination_slot_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	}
	if (static_cast<bool>(php::condition_truthy(workerPayloadReady))) {
		row->worker_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id();
	}
	else {
		row->worker_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
		row->blocked_reason_id = preflightRow->blocked_reason_id;
	}
	row->production_carrier_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->install_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
	return row;
}

}
