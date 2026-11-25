#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadCarrierInstallRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInstallPreflightRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadReplacementMaterializerRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_row_from_preflight_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_rows_from_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_blocked_reason_production_payload_materializer_missing.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_destination_slot_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_destination_slots.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_install_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_install_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_installed_payload_source_coordinator.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_installed_payload_source_worker.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_preflight_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_production_carrier_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_production_carrier_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_published_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_required_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_worker_payload_ready_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_rows_from_preflight_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_replacement_materializer_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_row_from_materializer_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow> __latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_rows_from_preflight_rows(vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow>& preflightRows, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& coordinatorPayloadTables, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& workerPayloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_replacement_materializer_rows_from_preflight_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[197]);
	vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow> rows = {};
	php::vector_reserve(rows, php::count(preflightRows));
	int_t<> materializerRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = preflightRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto preflightRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_row_from_preflight_row(preflightRow, coordinatorPayloadTables, workerPayloadTables, materializerRowId);
		(void) rows.push_back(__latency_local_2);
		}
		materializerRowId = (materializerRowId + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_replacement_materializer_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<SourceUnitFrontendWorkerPayloadInstallPreflightRow>& preflightRows, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& coordinatorPayloadTables, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& workerPayloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_payload_replacement_materializer_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[198]);
	if (static_cast<bool>((php::count(preflightRows) <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow> materializerRows = required_cast<vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_replacement_materializer_rows_from_preflight_rows(preflightRows, coordinatorPayloadTables, workerPayloadTables));
	int_t<> requiredPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> publishedPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> preflightReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> destinationSlotReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> workerPayloadReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionCarrierReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionCarrierBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> materializerMissingBlockers = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedCoordinatorRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedWorkerRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> metadataCommitBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> payloadCopyBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = materializerRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto materializerRow = __latency_local_1.value_copy();
		requiredPayloadSegments = (requiredPayloadSegments + cast<int_t<>>(materializerRow->required_payload_segment_count));
		publishedPayloadSegments = (publishedPayloadSegments + cast<int_t<>>(materializerRow->published_payload_segment_count));
		metadataCommitBytes = (metadataCommitBytes + cast<int_t<>>(materializerRow->metadata_commit_bytes));
		payloadCopyBytes = (payloadCopyBytes + cast<int_t<>>(materializerRow->payload_copy_bytes));
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->preflight_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			preflightReadyRows = (preflightReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->destination_slot_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			destinationSlotReadyRows = (destinationSlotReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			workerPayloadReadyRows = (workerPayloadReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->production_carrier_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			productionCarrierReadyRows = (productionCarrierReadyRows + static_cast<int_t<> >(1));
		}
		else {
			productionCarrierBlockedRows = (productionCarrierBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->install_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			installReadyRows = (installReadyRows + static_cast<int_t<> >(1));
		}
		else {
			installBlockedRows = (installBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id())))) {
			materializerMissingBlockers = (materializerMissingBlockers + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id())))) {
			installedCoordinatorRows = (installedCoordinatorRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(materializerRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id())))) {
			installedWorkerRows = (installedWorkerRows + static_cast<int_t<> >(1));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(materializerRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_preflight_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(preflightRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_destination_slots(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(destinationSlotReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_required_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_published_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_preflight_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(preflightReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_destination_slot_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(destinationSlotReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_worker_payload_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerPayloadReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_production_carrier_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionCarrierReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_production_carrier_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionCarrierBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_install_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_install_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_blocked_reason_production_payload_materializer_missing(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(materializerMissingBlockers));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_installed_payload_source_coordinator(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedCoordinatorRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_installed_payload_source_worker(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedWorkerRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataCommitBytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_replacement_materializer_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(payloadCopyBytes));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitFrontendWorkerPayloadCarrierInstallRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_row_from_materializer_row(SourceUnitFrontendWorkerPayloadReplacementMaterializerRow materializerRow, int_t<> carrierInstallRowId, int_t<std::uint32_t> publishedRowFirstId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_carrier_install_row_from_materializer_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[199]);
	bool_t materializerReady = required_cast<bool_t>((((((php::identical(cast<int_t<>>(materializerRow->preflight_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) && php::identical(cast<int_t<>>(materializerRow->destination_slot_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && php::identical(cast<int_t<>>(materializerRow->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && (cast<int_t<>>(materializerRow->required_payload_segment_count) > static_cast<int_t<> >(0))) && (cast<int_t<>>(materializerRow->published_payload_segment_count) >= cast<int_t<>>(materializerRow->required_payload_segment_count))) && php::identical(cast<int_t<>>(materializerRow->payload_copy_bytes), static_cast<int_t<> >(0))));
	SourceUnitFrontendWorkerPayloadCarrierInstallRow row = SourceUnitFrontendWorkerPayloadCarrierInstallRow{};
	row->carrier_install_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(carrierInstallRowId);
	row->owner_run_id = materializerRow->owner_run_id;
	row->source_unit_id = materializerRow->source_unit_id;
	row->source_unit_key_id = materializerRow->source_unit_key_id;
	row->worker_id = materializerRow->worker_id;
	row->materializer_row_id = materializerRow->materializer_row_id;
	row->preflight_row_id = materializerRow->preflight_row_id;
	row->worker_payload_table_id = materializerRow->worker_payload_table_id;
	row->coordinator_payload_table_id = materializerRow->coordinator_payload_table_id;
	row->worker_token_list_id = materializerRow->worker_token_list_id;
	row->worker_frontend_node_list_id = materializerRow->worker_frontend_node_list_id;
	row->destination_token_list_id = materializerRow->destination_token_list_id;
	row->destination_frontend_node_list_id = materializerRow->destination_frontend_node_list_id;
	row->required_payload_segment_count = materializerRow->required_payload_segment_count;
	row->published_payload_segment_count = materializerRow->published_payload_segment_count;
	row->local_row_first_id = row->carrier_install_row_id;
	row->local_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7600000) + cast<int_t<>>(materializerRow->source_unit_id)));
	row->published_row_first_id = publishedRowFirstId;
	row->published_row_count = row->local_row_count;
	row->metadata_commit_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(sizeof(SourceUnitFrontendWorkerPayloadCarrierInstallRow)));
	row->payload_copy_bytes = materializerRow->payload_copy_bytes;
	if (static_cast<bool>(php::condition_truthy(materializerReady))) {
		row->materializer_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id();
	}
	else {
		row->materializer_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
		row->blocked_reason_id = materializerRow->blocked_reason_id;
	}
	row->destination_slot_status_id = materializerRow->destination_slot_status_id;
	row->worker_payload_status_id = materializerRow->worker_payload_status_id;
	row->token_carrier_contract_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->frontend_carrier_contract_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->production_swap_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->install_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
	return row;
}

}
