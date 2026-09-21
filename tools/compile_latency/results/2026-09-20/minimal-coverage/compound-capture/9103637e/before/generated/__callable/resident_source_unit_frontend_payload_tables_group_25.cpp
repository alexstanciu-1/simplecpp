#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadProductionInstallRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadProductionSwapRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_row_from_swap_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_row_from_swap_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_rows_from_swap_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_partition_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_production_install_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_partition_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_production_install_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_publication_artifact.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_blocked_reason_none.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_blocked_reason_production_payload_install_missing.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_carrier_contract_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_frontend_carrier_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_install_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_install_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_installed_payload_source_coordinator.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_installed_payload_source_worker.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_published_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_required_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_token_carrier_ready_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_install_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_rows_from_swap_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_production_install_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitFrontendWorkerPayloadProductionInstallRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_row_from_swap_row(SourceUnitFrontendWorkerPayloadProductionSwapRow swapRow, int_t<> productionInstallRowId, int_t<std::uint32_t> publishedRowFirstId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_production_install_row_from_swap_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[217]);
	bool_t installReady = required_cast<bool_t>(((((((php::identical(cast<int_t<>>(swapRow->carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) && php::identical(cast<int_t<>>(swapRow->token_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && php::identical(cast<int_t<>>(swapRow->frontend_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && php::identical(cast<int_t<>>(swapRow->production_swap_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) && (cast<int_t<>>(swapRow->required_payload_segment_count) > static_cast<int_t<> >(0))) && (cast<int_t<>>(swapRow->published_payload_segment_count) >= cast<int_t<>>(swapRow->required_payload_segment_count))) && php::identical(cast<int_t<>>(swapRow->payload_copy_bytes), static_cast<int_t<> >(0))));
	SourceUnitFrontendWorkerPayloadProductionInstallRow row = SourceUnitFrontendWorkerPayloadProductionInstallRow{};
	row->production_install_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionInstallRowId);
	row->owner_run_id = swapRow->owner_run_id;
	row->source_unit_id = swapRow->source_unit_id;
	row->source_unit_key_id = swapRow->source_unit_key_id;
	row->worker_id = swapRow->worker_id;
	row->production_swap_row_id = swapRow->production_swap_row_id;
	row->carrier_contract_row_id = swapRow->carrier_contract_row_id;
	row->carrier_install_row_id = swapRow->carrier_install_row_id;
	row->materializer_row_id = swapRow->materializer_row_id;
	row->preflight_row_id = swapRow->preflight_row_id;
	row->worker_payload_table_id = swapRow->worker_payload_table_id;
	row->coordinator_payload_table_id = swapRow->coordinator_payload_table_id;
	row->worker_token_list_id = swapRow->worker_token_list_id;
	row->worker_frontend_node_list_id = swapRow->worker_frontend_node_list_id;
	row->destination_token_list_id = swapRow->destination_token_list_id;
	row->destination_frontend_node_list_id = swapRow->destination_frontend_node_list_id;
	row->required_payload_segment_count = swapRow->required_payload_segment_count;
	row->published_payload_segment_count = swapRow->published_payload_segment_count;
	row->local_row_first_id = row->production_install_row_id;
	row->local_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(9000000) + cast<int_t<>>(swapRow->source_unit_id)));
	row->published_row_first_id = publishedRowFirstId;
	row->published_row_count = row->local_row_count;
	row->metadata_commit_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(sizeof(SourceUnitFrontendWorkerPayloadProductionInstallRow)));
	row->payload_copy_bytes = swapRow->payload_copy_bytes;
	row->carrier_contract_status_id = swapRow->carrier_contract_status_id;
	row->token_carrier_contract_status_id = swapRow->token_carrier_contract_status_id;
	row->frontend_carrier_contract_status_id = swapRow->frontend_carrier_contract_status_id;
	row->production_swap_status_id = swapRow->production_swap_status_id;
	if (static_cast<bool>(php::condition_truthy(installReady))) {
		row->install_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
		row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	}
	else {
		row->install_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
		row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
		row->blocked_reason_id = swapRow->blocked_reason_id;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<SourceUnitFrontendWorkerPayloadProductionInstallRow> __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_rows_from_swap_rows(vector_t<SourceUnitFrontendWorkerPayloadProductionSwapRow>& swapRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_production_install_rows_from_swap_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[218]);
	vector_t<SourceUnitFrontendWorkerPayloadProductionInstallRow> rows = {};
	php::vector_reserve(rows, php::count(swapRows));
	int_t<> productionInstallRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> publishedRowFirstId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	auto& __latency_local_0 = swapRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto swapRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_row_from_swap_row(swapRow, productionInstallRowId, cast<int_t<std::uint32_t>>(publishedRowFirstId));
		(void) rows.push_back(__latency_local_2);
		}
		productionInstallRowId = (productionInstallRowId + static_cast<int_t<> >(1));
		publishedRowFirstId = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((cast<int_t<>>(publishedRowFirstId) + static_cast<int_t<> >(1)));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_partition_row(SourceUnitFrontendWorkerPayloadProductionInstallRow installRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_production_install_partition_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[219]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>((((php::not_identical(cast<int_t<>>(installRow->production_swap_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) || php::not_identical(cast<int_t<>>(installRow->install_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id()))) || php::not_identical(cast<int_t<>>(installRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id()))) || php::not_identical(cast<int_t<>>(installRow->payload_copy_bytes), static_cast<int_t<> >(0))))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = installRow->blocked_reason_id;
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(8100) + cast<int_t<>>(installRow->production_install_row_id))), installRow->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), installRow->source_unit_id, __latency_fn_structure_row_ids_none_id(), installRow->production_install_row_id, statusId, blockedReasonId);
	row->input_snapshot_generation = installRow->owner_run_id;
	row->local_row_first_id = installRow->local_row_first_id;
	row->local_row_count = installRow->local_row_count;
	row->merge_order_key = installRow->merge_order_key;
	row->published_row_first_id = installRow->published_row_first_id;
	row->published_row_count = installRow->published_row_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_append_payload_production_install_publication_row(shared_p<PartitionReadinessArtifact>& artifact, SourceUnitFrontendWorkerPayloadProductionInstallRow installRow) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::append_payload_production_install_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[220]);
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_partition_row(installRow));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_publication_artifact(vector_t<SourceUnitFrontendWorkerPayloadProductionInstallRow>& installRows, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_production_install_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[221]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(installRows));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(installRows) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_production_install_publication_row(artifact, installRows.at(index));
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto& __latency_local_0 = installRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto installRow = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_frontend_payload_tables_append_payload_production_install_publication_row(artifact, installRow);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_production_install_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<SourceUnitFrontendWorkerPayloadProductionSwapRow>& swapRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_payload_production_install_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[222]);
	if (static_cast<bool>((php::count(swapRows) <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<SourceUnitFrontendWorkerPayloadProductionInstallRow> installRows = required_cast<vector_t<SourceUnitFrontendWorkerPayloadProductionInstallRow>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_rows_from_swap_rows(swapRows));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_publication_artifact(installRows, bool_t(static_cast<bool_t>(false)));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_resident_source_unit_frontend_payload_tables_payload_production_install_publication_artifact(installRows, bool_t(static_cast<bool_t>(true)));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<> requiredPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> publishedPayloadSegments = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> carrierContractReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> tokenCarrierReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> frontendCarrierReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionSwapReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> productionSwapBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installReadyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installBlockedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> blockedReasonNoneRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installMissingBlockers = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedCoordinatorRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> installedWorkerRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> metadataCommitBytes = required_cast<int_t<>>((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow))));
	int_t<> payloadCopyBytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = installRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto installRow = __latency_local_1.value_copy();
		requiredPayloadSegments = (requiredPayloadSegments + cast<int_t<>>(installRow->required_payload_segment_count));
		publishedPayloadSegments = (publishedPayloadSegments + cast<int_t<>>(installRow->published_payload_segment_count));
		metadataCommitBytes = (metadataCommitBytes + cast<int_t<>>(installRow->metadata_commit_bytes));
		payloadCopyBytes = (payloadCopyBytes + cast<int_t<>>(installRow->payload_copy_bytes));
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			carrierContractReadyRows = (carrierContractReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->token_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			tokenCarrierReadyRows = (tokenCarrierReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->frontend_carrier_contract_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			frontendCarrierReadyRows = (frontendCarrierReadyRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->production_swap_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			productionSwapReadyRows = (productionSwapReadyRows + static_cast<int_t<> >(1));
		}
		else {
			productionSwapBlockedRows = (productionSwapBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->install_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			installReadyRows = (installReadyRows + static_cast<int_t<> >(1));
		}
		else {
			installBlockedRows = (installBlockedRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id())))) {
			blockedReasonNoneRows = (blockedReasonNoneRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_install_missing_id())))) {
			installMissingBlockers = (installMissingBlockers + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id())))) {
			installedCoordinatorRows = (installedCoordinatorRows + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(installRow->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id())))) {
			installedWorkerRows = (installedWorkerRows + static_cast<int_t<> >(1));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(installRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(swapRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_published_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total(publication)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_required_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_published_payload_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedPayloadSegments));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_carrier_contract_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(carrierContractReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_token_carrier_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tokenCarrierReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_frontend_carrier_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(frontendCarrierReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionSwapReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_production_swap_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(productionSwapBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_install_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installReadyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_install_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installBlockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_blocked_reason_none(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(blockedReasonNoneRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_blocked_reason_production_payload_install_missing(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installMissingBlockers));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_installed_payload_source_coordinator(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedCoordinatorRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_installed_payload_source_worker(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(installedWorkerRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataCommitBytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_production_install_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(payloadCopyBytes));
}

}
