#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_frontend_node_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_frontend_nodes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_metadata_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_token_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_token_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_max_payload_table_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_max_payload_table_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_first_by_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_first_for_payload_table_from_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_first_for_payload_table_from_index.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_first_by_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_publication_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitFrontendPayloadTableRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_built_handle_metrics_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[46]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_ready(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_token_rows(), row->token_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_frontend_nodes(), row->frontend_node_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_token_segments(), row->token_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_frontend_node_segments(), row->frontend_node_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_metadata_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(sizeof(ResidentSourceUnitFrontendPayloadTableRow))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_max_payload_table_source_unit_id(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::max_payload_table_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[47]);
	int_t<> maxSourceUnitId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->source_unit_id) > maxSourceUnitId))) {
			maxSourceUnitId = cast<int_t<>>(row->source_unit_id);
		}
	}
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(maxSourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<int_t<std::uint32_t>> __latency_fn_resident_source_unit_frontend_payload_tables_published_first_by_source_unit_id(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::published_first_by_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[48]);
	int_t<std::uint32_t> maxSourceUnitId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_max_payload_table_source_unit_id(payloadTables));
	vector_t<int_t<std::uint32_t>> publishedFirstBySourceUnitId = {};
	php::vector_reserve(publishedFirstBySourceUnitId, cast<int_t<>>(maxSourceUnitId));
	while (static_cast<bool>((php::count(publishedFirstBySourceUnitId) < cast<int_t<>>(maxSourceUnitId)))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) publishedFirstBySourceUnitId.push_back(__latency_local_0);
		}
	}
	int_t<> publishedFirstId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_1 = payloadTables;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->source_unit_id, php::count(publishedFirstBySourceUnitId))))) {
			publishedFirstBySourceUnitId.at(__latency_fn_structure_row_ids_dense_index(row->source_unit_id)) = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedFirstId);
		}
		publishedFirstId = (publishedFirstId + static_cast<int_t<> >(1));
	}
	return publishedFirstBySourceUnitId;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_published_first_for_payload_table_from_index(vector_t<int_t<std::uint32_t>>& publishedFirstBySourceUnitId, ResidentSourceUnitFrontendPayloadTableRow payloadTable) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::published_first_for_payload_table_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[49]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadTable->source_unit_id, php::count(publishedFirstBySourceUnitId))))) {
		return cast<int_t<std::uint32_t>>(publishedFirstBySourceUnitId.at(__latency_fn_structure_row_ids_dense_index(payloadTable->source_unit_id)));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row(shared_p<PartitionReadinessArtifact>& artifact, vector_t<int_t<std::uint32_t>>& publishedFirstBySourceUnitId, ResidentSourceUnitFrontendPayloadTableRow payloadTable) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::append_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[50]);
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(4000) + cast<int_t<>>(payloadTable->source_unit_id))), payloadTable->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), payloadTable->source_unit_id, __latency_fn_structure_row_ids_none_id(), payloadTable->payload_table_id, __latency_fn_partition_readiness_status_ready_id(), __latency_fn_partition_readiness_blocked_reason_none_id());
	row->input_snapshot_generation = payloadTable->input_snapshot_generation;
	row->local_row_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(4000000) + cast<int_t<>>(payloadTable->source_unit_id)));
	row->published_row_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_published_first_for_payload_table_from_index(publishedFirstBySourceUnitId, payloadTable);
	row->published_row_count = row->local_row_count;
	__latency_fn_partition_readiness_append_artifact_row(artifact, row);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_frontend_payload_tables_publication_artifact(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[51]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(payloadTables));
	vector_t<int_t<std::uint32_t>> publishedFirstBySourceUnitId = required_cast<vector_t<int_t<std::uint32_t>>>(__latency_fn_resident_source_unit_frontend_payload_tables_published_first_by_source_unit_id(payloadTables));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(payloadTables) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			ResidentSourceUnitFrontendPayloadTableRow payloadTable = payloadTables.at(index);
			__latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row(artifact, publishedFirstBySourceUnitId, payloadTable);
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row(artifact, publishedFirstBySourceUnitId, payloadTable);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[52]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->published_row_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_publication_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_publication_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[53]);
	if (static_cast<bool>((php::count(payloadTables) <= static_cast<int_t<> >(0)))) {
		return;
	}
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_resident_source_unit_frontend_payload_tables_publication_artifact(payloadTables, bool_t(static_cast<bool_t>(false)));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_resident_source_unit_frontend_payload_tables_publication_artifact(payloadTables, bool_t(static_cast<bool_t>(true)));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulated = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_published_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total(publication)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_simulated_first_input_order_sum(), firstSimulated->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_payload_handle_publication_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
}

}
