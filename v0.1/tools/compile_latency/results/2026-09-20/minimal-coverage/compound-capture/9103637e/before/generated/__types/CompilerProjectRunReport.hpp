#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/CompilerProfileEventRow.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentBodyDependencyRow.hpp"
#include "__types/ResidentChangeEventRow.hpp"
#include "__types/ResidentDependencyEdgeSnapshotRow.hpp"
#include "__types/ResidentDirtyBailoutRow.hpp"
#include "__types/ResidentDirtyBudgetRow.hpp"
#include "__types/ResidentDirtyLimitCheckRow.hpp"
#include "__types/ResidentDirtyProcessedRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__types/ResidentFrontendNodeListPublishRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodyChangeRow.hpp"
#include "__types/ResidentFunctionBodyIncrementalAcceptanceRow.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodyParseSliceRow.hpp"
#include "__types/ResidentFunctionBodyPublishRepointPreflightRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodySourceUnitRepointProofRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/ResidentFunctionBodySummaryProjectReferenceRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentIncrementalFileChangeRow.hpp"
#include "__types/ResidentIncrementalTransactionRow.hpp"
#include "__types/ResidentProjectSnapshotDiffRow.hpp"
#include "__types/ResidentProjectSnapshotRow.hpp"
#include "__types/ResidentProjectSymbolNameLookupRow.hpp"
#include "__types/ResidentPublicSurfaceIncrementalAcceptanceRow.hpp"
#include "__types/ResidentRecomputeTargetRow.hpp"
#include "__types/ResidentReverseDependencyIndexRefRow.hpp"
#include "__types/ResidentReverseDependencyIndexRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/ResidentSourceUnitFrontendWorkDecisionRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__types/ResidentTokenListPublishRow.hpp"
#include "__types/ResidentTokenListSnapshotRow.hpp"
#include "__types/ResidentTransactionStepRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/proof_metrics.hpp"
#include "__types/resident_body_dependencies.hpp"
#include "__types/resident_change_events.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_logical_frontend_reference_candidates.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__types/resident_function_body_publish_repoint_preflights.hpp"
#include "__types/resident_function_body_row_list_publish_plans.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__types/resident_function_body_source_unit_repoint_proofs.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__types/resident_function_body_summary_project_references.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__types/resident_project_symbol_name_lookups.hpp"
#include "__types/resident_recompute_targets.hpp"
#include "__types/resident_reverse_dependency_indexes.hpp"
#include "__types/resident_snapshots.hpp"
#include "__types/resident_source_unit_frontend_states.hpp"
#include "__types/resident_source_unit_symbol_states.hpp"
#include "__types/resident_transactions.hpp"
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
class ProofMetricRow;
class CompilerProjectRunReport {
public:

    CompilerProjectRunReport();
    ~CompilerProjectRunReport();
    CompilerProjectRunReport(const CompilerProjectRunReport&);
    CompilerProjectRunReport& operator=(const CompilerProjectRunReport&);
    CompilerProjectRunReport(CompilerProjectRunReport&&) noexcept;
    CompilerProjectRunReport& operator=(CompilerProjectRunReport&&) noexcept;
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> runner_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> completed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> project_symbol_sidecar_string_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> project_symbol_sidecar_string_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> project_symbol_sidecar_lookup_entry_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_reserved_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_slack_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_reserved_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_slack_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_declaration_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parser_error_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> dependency_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> dependency_edge_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> type_ref_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> capability_readiness_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_request_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_request_binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_request_local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_request_call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_request_control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_partition_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_partition_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_partition_link_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_plan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_step_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_work_ref_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_blocked_request_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lowering_control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_decision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_value_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_value_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_block_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> backend_emission_control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> llvm_sink_boundary_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> llvm_preflight_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> llvm_text_blob_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> llvm_text_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> artifact_write_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> reused_write_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> skipped_write_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> elapsed_us = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> profile_event_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> profile_event_elapsed_us = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> performance_memory_estimate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> performance_memory_estimated_hot_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_snapshot_reuse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_snapshot_changed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_snapshot_diff_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_transaction_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_transaction_reuse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_transaction_changed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_transaction_dirty_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_file_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_reuse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_snapshot_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_snapshot_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_snapshot_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_reuse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_surface_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_body_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_definition_value_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_snapshot_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_snapshot_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_symbol_snapshot_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_unique_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_duplicate_name_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_hash_collision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_hit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_miss_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_project_symbol_name_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_snapshot_reuse_previous_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_snapshot_built_current_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_reuse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_body_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_public_surface_change_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_new_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_deleted_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_decision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_reuse_previous_body_rows_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_parse_replacement_body_rows_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_publish_public_surface_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_cleanup_deleted_body_rows_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_build_new_body_rows_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_work_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_replacement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_build_new_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_token_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_parse_slice_source_unit_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_statement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_expression_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_local_parse_proof_diagnostic_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_requires_remap_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_deferred_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_statement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_publish_repoint_preflight_expression_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_stable_node_remap_proof_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_stable_node_remap_proof_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_stable_node_remap_proof_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_stable_node_remap_proof_window_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_stable_node_remap_proof_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_planned_publish_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_planned_cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_replacement_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_retained_old_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_plan_cleanup_released_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_published_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_replacement_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_retained_old_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_cleanup_released_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_row_list_publish_result_segmented_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_proof_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_proof_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_proof_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_mixed_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_reused_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_replacement_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_build_new_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_reused_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_replacement_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_assembled_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_published_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_mixed_frontend_assembly_published_segmented_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_proof_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_proof_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_proof_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_mixed_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_reused_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_replacement_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_build_new_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_source_unit_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_stable_non_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_retained_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_repointed_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_assembled_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_current_full_source_rebuild_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_cleanup_released_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_published_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_source_unit_repoint_published_segmented_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_mixed_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_reused_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_replacement_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_build_new_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_logical_source_unit_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_stable_non_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_retained_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_repointed_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_logical_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_current_full_source_rebuild_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_cleanup_released_body_row_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_published_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_published_segmented_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_lookup_resolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_view_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_mixed_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_body_span_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_retained_body_span_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_replacement_body_span_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_build_new_body_span_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_logical_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_published_body_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_copied_frontend_node_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_lookup_resolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_consumer_descriptor_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_body_span_descriptor_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_body_span_descriptor_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_body_span_descriptor_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_found_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_no_candidate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_summary_read_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_summary_fallback_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_body_node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_callee_range_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_logical_frontend_reference_candidate_argument_summary_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_resolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_missing_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_ambiguous_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_unresolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_skipped_no_candidate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_summary_project_reference_scratch_symbol_rebuild_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_source_reparse_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_local_lowering_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_backend_refresh_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_dependent_resolution_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_summary_reference_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_incremental_acceptance_summary_skipped_no_candidate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_reverse_index_lookup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_reverse_index_hit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_reverse_index_no_dependent_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_source_reparse_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_public_surface_publish_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_dependent_resolution_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_local_lowering_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_public_surface_incremental_acceptance_backend_refresh_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_change_event_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_change_event_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_change_event_symbol_definition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_change_event_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_change_event_public_surface_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_queue_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_queue_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_queue_symbol_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_queue_body_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_queue_public_surface_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_processed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_processed_skipped_duplicate_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_processed_upgraded_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_budget_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_ok_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_exceeded_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_queue_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_processed_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_fanout_ref_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_depth_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_scratch_byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_limit_check_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_queue_row_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_processed_row_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_fanout_ref_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_depth_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_scratch_byte_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_fallback_scan_limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_widen_source_unit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dirty_bailout_widen_project_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_decision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_skipped_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_reparse_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_early_skip_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_decision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_reuse_previous_list_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_parse_replacement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_cleanup_deleted_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_build_new_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_work_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_run_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_work_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_partition_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_task_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_completed_task_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_output_match_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_output_mismatch_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_frontend_scheduler_simulated_first_input_order_max = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> proof_metric_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_reuse_previous_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_rebuilt_replacement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_built_current_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_frontend_state_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_reuse_previous_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_rebuilt_replacement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_built_current_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_reuse_previous_symbol_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_rebuilt_replacement_symbol_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_built_current_symbol_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_source_unit_symbol_state_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_ownership_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_ownership_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_function_body_ownership_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_recompute_target_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_recompute_selected_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_recompute_skipped_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_transaction_step_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_dependency_edge_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_ref_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_lookup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_hit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_no_dependent_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_index_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_lookup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_hit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_reverse_dependency_no_dependent_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_body_dependency_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_publish_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_retained_old_generation_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_cleanup_released_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_publish_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_publish_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_token_list_publish_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_snapshot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_publish_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_retained_old_generation_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_cleanup_released_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_publish_lookup_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_publish_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resident_frontend_node_list_publish_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_source_owner_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_symbol_body_owner_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_lowering_owner_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_backend_owner_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_object_owner_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> partition_readiness_object_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> capability_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> capability_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> symbol_lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> symbol_lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<CompilerProjectRunRow> rows = vector_t<CompilerProjectRunRow>{};
	vector_t<string_t> run_labels = vector_t<string_t>{};
	vector_t<CompilerProfileEventRow> profile_events;
	vector_t<PerformanceMemoryEstimateRow> performance_memory_estimates = vector_t<PerformanceMemoryEstimateRow>{};
	vector_t<shared_p<ProofMetricRow>> proof_metrics = vector_t<shared_p<ProofMetricRow>>{};
	vector_t<ResidentProjectSnapshotRow> resident_snapshots = vector_t<ResidentProjectSnapshotRow>{};
	vector_t<ResidentProjectSnapshotDiffRow> resident_snapshot_diffs = vector_t<ResidentProjectSnapshotDiffRow>{};
	vector_t<ResidentIncrementalTransactionRow> resident_transactions = vector_t<ResidentIncrementalTransactionRow>{};
	vector_t<ResidentIncrementalFileChangeRow> resident_file_changes = vector_t<ResidentIncrementalFileChangeRow>{};
	vector_t<ResidentSourceUnitSnapshotRow> resident_source_unit_snapshots = vector_t<ResidentSourceUnitSnapshotRow>{};
	vector_t<ResidentSourceUnitChangeRow> resident_source_unit_changes = vector_t<ResidentSourceUnitChangeRow>{};
	vector_t<ResidentSymbolDefinitionSnapshotRow> resident_symbol_definition_snapshots = vector_t<ResidentSymbolDefinitionSnapshotRow>{};
	vector_t<ResidentSymbolDefinitionChangeRow> resident_symbol_definition_changes = vector_t<ResidentSymbolDefinitionChangeRow>{};
	vector_t<ResidentProjectSymbolNameLookupRow> resident_project_symbol_name_lookups = vector_t<ResidentProjectSymbolNameLookupRow>{};
	vector_t<ResidentFunctionBodySnapshotRow> resident_function_body_snapshots = vector_t<ResidentFunctionBodySnapshotRow>{};
	vector_t<ResidentFunctionBodyChangeRow> resident_function_body_changes = vector_t<ResidentFunctionBodyChangeRow>{};
	vector_t<ResidentFunctionBodyWorkDecisionRow> resident_function_body_work_decisions = vector_t<ResidentFunctionBodyWorkDecisionRow>{};
	vector_t<ResidentFunctionBodyParseSliceRow> resident_function_body_parse_slices = vector_t<ResidentFunctionBodyParseSliceRow>{};
	vector_t<ResidentFunctionBodyLocalParseProofRow> resident_function_body_local_parse_proofs = vector_t<ResidentFunctionBodyLocalParseProofRow>{};
	vector_t<ResidentFunctionBodyPublishRepointPreflightRow> resident_function_body_publish_repoint_preflights = vector_t<ResidentFunctionBodyPublishRepointPreflightRow>{};
	vector_t<ResidentFunctionBodyStableNodeRemapProofRow> resident_function_body_stable_node_remap_proofs = vector_t<ResidentFunctionBodyStableNodeRemapProofRow>{};
	vector_t<ResidentFunctionBodyRowListPublishPlanRow> resident_function_body_row_list_publish_plans = vector_t<ResidentFunctionBodyRowListPublishPlanRow>{};
	vector_t<ResidentFunctionBodyRowListPublishResultRow> resident_function_body_row_list_publish_results = vector_t<ResidentFunctionBodyRowListPublishResultRow>{};
	vector_t<ResidentFunctionBodyMixedFrontendAssemblyProofRow> resident_function_body_mixed_frontend_assembly_proofs = vector_t<ResidentFunctionBodyMixedFrontendAssemblyProofRow>{};
	vector_t<ResidentFunctionBodySourceUnitRepointProofRow> resident_function_body_source_unit_repoint_proofs = vector_t<ResidentFunctionBodySourceUnitRepointProofRow>{};
	vector_t<ResidentFunctionBodyLogicalFrontendViewRow> resident_function_body_logical_frontend_views = vector_t<ResidentFunctionBodyLogicalFrontendViewRow>{};
	vector_t<ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow> resident_function_body_logical_frontend_consumer_descriptors = vector_t<ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow>{};
	vector_t<ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow> resident_function_body_logical_frontend_body_span_descriptors = vector_t<ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow>{};
	vector_t<ResidentFunctionBodyLogicalFrontendReferenceCandidateRow> resident_function_body_logical_frontend_reference_candidates = vector_t<ResidentFunctionBodyLogicalFrontendReferenceCandidateRow>{};
	vector_t<ResidentFunctionBodySummaryProjectReferenceRow> resident_function_body_summary_project_references = vector_t<ResidentFunctionBodySummaryProjectReferenceRow>{};
	vector_t<ResidentFunctionBodyIncrementalAcceptanceRow> resident_function_body_incremental_acceptances = vector_t<ResidentFunctionBodyIncrementalAcceptanceRow>{};
	vector_t<ResidentPublicSurfaceIncrementalAcceptanceRow> resident_public_surface_incremental_acceptances = vector_t<ResidentPublicSurfaceIncrementalAcceptanceRow>{};
	vector_t<ResidentChangeEventRow> resident_change_events = vector_t<ResidentChangeEventRow>{};
	vector_t<ResidentDirtyQueueRow> resident_dirty_queue_rows = vector_t<ResidentDirtyQueueRow>{};
	vector_t<ResidentDirtyProcessedRow> resident_dirty_processed_rows = vector_t<ResidentDirtyProcessedRow>{};
	vector_t<ResidentDirtyBudgetRow> resident_dirty_budgets = vector_t<ResidentDirtyBudgetRow>{};
	vector_t<ResidentDirtyLimitCheckRow> resident_dirty_limit_checks = vector_t<ResidentDirtyLimitCheckRow>{};
	vector_t<ResidentDirtyBailoutRow> resident_dirty_bailouts = vector_t<ResidentDirtyBailoutRow>{};
	vector_t<ResidentSourceUnitFrontendWorkDecisionRow> resident_source_unit_frontend_work_decisions = vector_t<ResidentSourceUnitFrontendWorkDecisionRow>{};
	vector_t<ResidentSourceUnitFrontendStateRow> resident_source_unit_frontend_states = vector_t<ResidentSourceUnitFrontendStateRow>{};
	vector_t<shared_p<FrontendModel>> resident_source_unit_frontend_models = vector_t<shared_p<FrontendModel>>{};
	vector_t<ResidentSourceUnitSymbolStateRow> resident_source_unit_symbol_states = vector_t<ResidentSourceUnitSymbolStateRow>{};
	vector_t<shared_p<ProjectSymbolIndex>> resident_source_unit_symbol_indexes = vector_t<shared_p<ProjectSymbolIndex>>{};
	vector_t<ResidentRecomputeTargetRow> resident_recompute_targets = vector_t<ResidentRecomputeTargetRow>{};
	vector_t<ResidentTransactionStepRow> resident_transaction_steps = vector_t<ResidentTransactionStepRow>{};
	vector_t<ResidentDependencyEdgeSnapshotRow> resident_dependency_edge_snapshots = vector_t<ResidentDependencyEdgeSnapshotRow>{};
	vector_t<ResidentReverseDependencyIndexRow> resident_reverse_dependency_indexes = vector_t<ResidentReverseDependencyIndexRow>{};
	vector_t<ResidentReverseDependencyIndexRefRow> resident_reverse_dependency_index_refs = vector_t<ResidentReverseDependencyIndexRefRow>{};
	vector_t<ResidentReverseDependencyLookupRow> resident_reverse_dependency_lookups = vector_t<ResidentReverseDependencyLookupRow>{};
	vector_t<ResidentBodyDependencyRow> resident_body_dependencies = vector_t<ResidentBodyDependencyRow>{};
	vector_t<ResidentTokenListSnapshotRow> resident_token_list_snapshots = vector_t<ResidentTokenListSnapshotRow>{};
	vector_t<ResidentTokenListPublishRow> resident_token_list_publishes = vector_t<ResidentTokenListPublishRow>{};
	vector_t<ResidentFrontendNodeListSnapshotRow> resident_frontend_node_list_snapshots = vector_t<ResidentFrontendNodeListSnapshotRow>{};
	vector_t<ResidentFrontendNodeListPublishRow> resident_frontend_node_list_publishes = vector_t<ResidentFrontendNodeListPublishRow>{};
	vector_t<PartitionReadinessRow> partition_readiness_rows = vector_t<PartitionReadinessRow>{};
};
}
