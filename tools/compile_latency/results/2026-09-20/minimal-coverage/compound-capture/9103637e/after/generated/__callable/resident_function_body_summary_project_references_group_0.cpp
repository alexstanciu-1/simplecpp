#include <scpp/lang/php.hpp>
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/ResidentFunctionBodySummaryProjectReferenceRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/resident_function_body_summary_project_references.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_access_path_full_frontend_fallback_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_symbol_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_source_text_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_candidate_has_reference.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_source_units_from_config.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_source_text_for_candidate.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_callee_hash_from_candidate.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_blocked_row_from_candidate.hpp"
#include "__callable/__latency_fn_resident_function_body_summary_project_references_status_blocked_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
bool_t resident_function_body_summary_project_references::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_summary_project_references::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_summary_project_references_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::access_path_summary_source_range_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_access_path_full_frontend_fallback_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::access_path_full_frontend_fallback_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[5]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_symbol_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::blocked_reason_missing_symbol_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_summary_project_references_blocked_reason_missing_source_text_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::blocked_reason_missing_source_text_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_summary_project_references_candidate_has_reference(ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::candidate_has_reference", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[8]);
	return (((php::identical(cast<int_t<>>(candidate->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id())) && (cast<int_t<>>(candidate->candidate_node_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(candidate->reference_candidate_count) > static_cast<int_t<> >(0))) && (cast<int_t<>>(candidate->callee_length) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source(ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::candidate_uses_previous_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[9]);
	return bool_t(php::identical(cast<int_t<>>(candidate->body_span_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_body_span_kind_retained_previous_body_list_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
shared_p<SourceUnitTable> __latency_fn_resident_function_body_summary_project_references_source_units_from_config(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::source_units_from_config", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[10]);
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(config->project_dir);
	return __latency_fn_source_units_table_from_manifest(manifest);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
string_t __latency_fn_resident_function_body_summary_project_references_source_text_for_candidate(shared_p<SourceUnitTable> currentSourceUnits, shared_p<SourceUnitTable> previousSourceUnits, ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::source_text_for_candidate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[11]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_function_body_summary_project_references_candidate_uses_previous_source(candidate)))) {
		return __latency_fn_source_units_source_text_by_id(previousSourceUnits, candidate->source_unit_id);
	}
	return __latency_fn_source_units_source_text_by_id(currentSourceUnits, candidate->source_unit_id);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_summary_project_references_callee_hash_from_candidate(const string_t& sourceText, ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::callee_hash_from_candidate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[12]);
	return __latency_fn_source_buffers_content_hash32_slice(sourceText, candidate->callee_start_offset, candidate->callee_length);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_summary_project_references[]; }
namespace scpp {
ResidentFunctionBodySummaryProjectReferenceRow __latency_fn_resident_function_body_summary_project_references_blocked_row_from_candidate(ResidentFunctionBodyLogicalFrontendReferenceCandidateRow candidate, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_summary_project_references::blocked_row_from_candidate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_summary_project_references.phs", __latency_lines_resident_function_body_summary_project_references[13]);
	ResidentFunctionBodySummaryProjectReferenceRow row = ResidentFunctionBodySummaryProjectReferenceRow{};
	row->owner_run_id = candidate->owner_run_id;
	row->source_unit_id = candidate->source_unit_id;
	row->symbol_id = candidate->symbol_id;
	row->logical_reference_candidate_id = candidate->candidate_id;
	row->candidate_node_id = candidate->candidate_node_id;
	row->callee_start_offset = candidate->callee_start_offset;
	row->callee_length = candidate->callee_length;
	row->actual_arg_count = candidate->actual_arg_count;
	row->body_span_kind_id = candidate->body_span_kind_id;
	row->access_path_id = __latency_fn_resident_function_body_summary_project_references_access_path_summary_source_range_id();
	row->status_id = __latency_fn_resident_function_body_summary_project_references_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}
