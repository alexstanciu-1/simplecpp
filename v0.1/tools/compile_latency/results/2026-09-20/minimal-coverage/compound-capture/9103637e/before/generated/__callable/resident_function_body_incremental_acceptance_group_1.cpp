#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/ResidentFunctionBodySummaryProjectReferenceRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentRecomputeTargetRow.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_logical_frontend_reference_candidates.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__types/resident_function_body_summary_project_references.hpp"
#include "__types/resident_recompute_targets.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_local_parse_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_stable_remap_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_view_by_source_unit.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_consumer_by_view.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_selected_target_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_ready_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_resolved_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count.hpp"
#include "__callable/__latency_fn_resident_function_body_incremental_acceptance_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyLocalParseProofRow __latency_fn_resident_function_body_incremental_acceptance_local_parse_by_decision(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::local_parse_by_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[16]);
	auto __latency_local_0 = report->resident_function_body_local_parse_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->function_body_work_decision_id), cast<int_t<>>(decision->decision_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyLocalParseProofRow empty = ResidentFunctionBodyLocalParseProofRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_incremental_acceptance_publish_result_by_decision(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::publish_result_by_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[17]);
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->function_body_work_decision_id), cast<int_t<>>(decision->decision_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyRowListPublishResultRow empty = ResidentFunctionBodyRowListPublishResultRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyStableNodeRemapProofRow __latency_fn_resident_function_body_incremental_acceptance_stable_remap_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> remapProofId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::stable_remap_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[18]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(remapProofId, php::count(report->resident_function_body_stable_node_remap_proofs))))) {
		ResidentFunctionBodyStableNodeRemapProofRow row = report->resident_function_body_stable_node_remap_proofs[__latency_fn_structure_row_ids_dense_index(remapProofId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->remap_proof_id), cast<int_t<>>(remapProofId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_function_body_stable_node_remap_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->remap_proof_id), cast<int_t<>>(remapProofId)))) {
			return row;
		}
	}
	ResidentFunctionBodyStableNodeRemapProofRow empty = ResidentFunctionBodyStableNodeRemapProofRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendViewRow __latency_fn_resident_function_body_incremental_acceptance_logical_view_by_source_unit(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::logical_view_by_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[19]);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(decision->source_unit_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendViewRow empty = ResidentFunctionBodyLogicalFrontendViewRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow __latency_fn_resident_function_body_incremental_acceptance_logical_consumer_by_view(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyLogicalFrontendViewRow view) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::logical_consumer_by_view", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[20]);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(view->owner_run_id)) && php::identical(cast<int_t<>>(row->logical_frontend_view_id), cast<int_t<>>(view->view_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow empty = ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_selected_target_count(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint16_t> targetKindId, bool_t matchSymbol) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::selected_target_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[21]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_recompute_targets;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto target = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::not_identical(cast<int_t<>>(target->owner_run_id), cast<int_t<>>(ownerRunId)) || php::not_identical(cast<int_t<>>(target->source_unit_id), cast<int_t<>>(sourceUnitId))) || php::not_identical(cast<int_t<>>(target->target_kind_id), cast<int_t<>>(targetKindId))) || php::not_identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id()))))) {
			continue;
		}
		if (static_cast<bool>((matchSymbol && php::not_identical(cast<int_t<>>(target->symbol_id), cast<int_t<>>(symbolId))))) {
			continue;
		}
		count = (count + static_cast<int_t<> >(1));
	}
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_count(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::summary_project_reference_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[22]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_summary_project_references;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(decision->source_unit_id))) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_summary_project_references_status_ready_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_summary_project_reference_resolved_count(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::summary_project_reference_resolved_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[23]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_summary_project_references;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(decision->source_unit_id))) && php::identical(cast<int_t<>>(row->reference_status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_incremental_acceptance_logical_reference_candidate_count(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_incremental_acceptance::logical_reference_candidate_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_incremental_acceptance.phs", __latency_lines_resident_function_body_incremental_acceptance[24]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_reference_candidates;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(decision->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(decision->source_unit_id))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_function_body_incremental_acceptance_uint32_from_int(count);
}

}
