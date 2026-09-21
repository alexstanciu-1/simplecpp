#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadCarrierInstallRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadReplacementMaterializerRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_row_from_materializer_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_rows_from_materializer_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_partition_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_carrier_install_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_partition_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_carrier_install_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_publication_artifact.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_blocked_reason_production_payload_carrier_missing.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_destination_slot_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_frontend_carrier_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_frontend_carrier_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_install_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_install_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_installed_payload_source_coordinator.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_installed_payload_source_worker.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_materializer_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_materializer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_production_swap_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_production_swap_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_published_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_required_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_token_carrier_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_token_carrier_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_worker_payload_ready_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_rows_from_materializer_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_carrier_install_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<SourceUnitFrontendWorkerPayloadCarrierInstallRow> __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_rows_from_materializer_rows(vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow>& materializerRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_carrier_install_rows_from_materializer_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[200]);
	vector_t<SourceUnitFrontendWorkerPayloadCarrierInstallRow> rows = {};
	php::vector_reserve(rows, php::count(materializerRows));
	int_t<> carrierInstallRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<> publishedRowFirstId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = materializerRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto materializerRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_row_from_materializer_row(materializerRow, carrierInstallRowId, __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedRowFirstId));
		(void) rows.push_back(__latency_local_2);
		}
		carrierInstallRowId = (carrierInstallRowId + static_cast<int_t<> >(1));
		publishedRowFirstId = (publishedRowFirstId + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_partition_row(SourceUnitFrontendWorkerPayloadCarrierInstallRow carrierRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_carrier_install_partition_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[201]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>((((php::not_identical(cast<int_t<>>(carrierRow->materializer_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) || php::not_identical(cast<int_t<>>(carrierRow->destination_slot_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) || php::not_identical(cast<int_t<>>(carrierRow->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) || php::not_identical(cast<int_t<>>(carrierRow->payload_copy_bytes), static_cast<int_t<> >(0))))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = carrierRow->blocked_reason_id;
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(7700) + cast<int_t<>>(carrierRow->carrier_install_row_id))), carrierRow->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), carrierRow->source_unit_id, __latency_fn_structure_row_ids_none_id(), carrierRow->carrier_install_row_id, statusId, blockedReasonId);
	row->input_snapshot_generation = carrierRow->owner_run_id;
	row->local_row_first_id = carrierRow->local_row_first_id;
	row->local_row_count = carrierRow->local_row_count;
	row->merge_order_key = carrierRow->merge_order_key;
	row->published_row_first_id = carrierRow->published_row_first_id;
	row->published_row_count = carrierRow->published_row_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_append_payload_carrier_install_publication_row(shared_p<PartitionReadinessArtifact>& artifact, SourceUnitFrontendWorkerPayloadCarrierInstallRow carrierRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::append_payload_carrier_install_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[202]);
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_partition_row(carrierRow));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_publication_artifact(vector_t<SourceUnitFrontendWorkerPayloadCarrierInstallRow>& carrierRows, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_carrier_install_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[203]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(carrierRows));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(carrierRows) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_carrier_install_publication_row(artifact, carrierRows.at(index));
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto& __latency_local_0 = carrierRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto carrierRow = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_carrier_install_publication_row(artifact, carrierRow);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_carrier_install_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<SourceUnitFrontendWorkerPayloadReplacementMaterializerRow>& materializerRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_payload_carrier_install_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[204]);
	if (static_cast<bool>((php::count(materializerRows) <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<SourceUnitFrontendWorkerPayloadCarrierInstallRow> carrierRows = required_cast<vector_t<SourceUnitFrontendWorkerPayloadCarrierInstallRow>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_rows_from_materializer_rows(materializerRows));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_publication_artifact(carrierRows, bool_t(static_cast<bool_t>(false)));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_resident_source_unit_frontend_payload_tables_payload_carrier_install_publication_artifact(carrierRows, bool_t(static_cast<bool_t>(true)));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<> requiredPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> publishedPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> materializerReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> destinationSlotReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> workerPayloadReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> tokenCarrierReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> tokenCarrierBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> frontendCarrierReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> frontendCarrierBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionSwapReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionSwapBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> carrierMissingBlockers = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedCoordinatorRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedWorkerRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> metadataCommitBytes = required_cast<int_t<>>((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow))));
	int_t<> payloadCopyBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = carrierRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto carrierRow = __latency_local_1.value_copy();
		requiredPayloadSegments = (requiredPayloadSegments + cast<int_t<>>(carrierRow->required_payload_segment_count));
		publishedPayloadSegments = (publishedPayloadSegments + cast<int_t<>>(carrierRow->published_payload_segment_count));
		metadataCommitBytes = (metadataCommitBytes + cast<int_t<>>(carrierRow->metadata_commit_bytes));
		payloadCopyBytes = (payloadCopyBytes + cast<int_t<>>(carrierRow->payload_copy_bytes));
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->materializer_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			materializerReadyRows = (materializerReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->destination_slot_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			destinationSlotReadyRows = (destinationSlotReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			workerPayloadReadyRows = (workerPayloadReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->token_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			tokenCarrierReadyRows = (tokenCarrierReadyRows + static_cast<int_t<> >(1));
		}
		else {
			tokenCarrierBlockedRows = (tokenCarrierBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->frontend_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			frontendCarrierReadyRows = (frontendCarrierReadyRows + static_cast<int_t<> >(1));
		}
		else {
			frontendCarrierBlockedRows = (frontendCarrierBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->production_swap_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			productionSwapReadyRows = (productionSwapReadyRows + static_cast<int_t<> >(1));
		}
		else {
			productionSwapBlockedRows = (productionSwapBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->install_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			installReadyRows = (installReadyRows + static_cast<int_t<> >(1));
		}
		else {
			installBlockedRows = (installBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id())))) {
			carrierMissingBlockers = (carrierMissingBlockers + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id())))) {
			installedCoordinatorRows = (installedCoordinatorRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(carrierRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id())))) {
			installedWorkerRows = (installedWorkerRows + static_cast<int_t<> >(1));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(carrierRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_materializer_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(materializerRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_published_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total(publication)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_required_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_published_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_materializer_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(materializerReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_destination_slot_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(destinationSlotReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_worker_payload_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerPayloadReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_token_carrier_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tokenCarrierReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_token_carrier_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tokenCarrierBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_frontend_carrier_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(frontendCarrierReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_frontend_carrier_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(frontendCarrierBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_production_swap_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionSwapReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_production_swap_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionSwapBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_install_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_install_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_blocked_reason_production_payload_carrier_missing(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(carrierMissingBlockers));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_installed_payload_source_coordinator(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedCoordinatorRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_installed_payload_source_worker(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedWorkerRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataCommitBytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_carrier_install_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(payloadCopyBytes));
}

}
