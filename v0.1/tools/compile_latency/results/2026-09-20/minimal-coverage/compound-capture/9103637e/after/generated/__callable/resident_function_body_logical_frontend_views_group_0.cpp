#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodySourceUnitRepointProofRow.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__types/resident_function_body_source_unit_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_repoint_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_missing_source_unit_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_empty_body_view_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_inconsistent_row_count_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_has_ready_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_empty_body_view_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_inconsistent_row_count_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_missing_source_unit_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_blocked_reason_repoint_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_row_from_repoint_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
bool_t resident_function_body_logical_frontend_views::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_logical_frontend_views::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_view_kind_replacement_only_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::view_kind_replacement_only_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::view_kind_mixed_reuse_and_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::view_kind_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[6]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_repoint_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::blocked_reason_repoint_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_missing_source_unit_list_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::blocked_reason_missing_source_unit_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_empty_body_view_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::blocked_reason_empty_body_view_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_inconsistent_row_count_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::blocked_reason_inconsistent_row_count_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_logical_frontend_views_has_ready_repoint_proofs(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::has_ready_repoint_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[11]);
	auto __latency_local_0 = report->resident_function_body_source_unit_repoint_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(proof->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(proof->status_id), cast<int_t<>>(__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendViewRow __latency_fn_resident_function_body_logical_frontend_views_row_from_repoint_proof(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodySourceUnitRepointProofRow proof) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::row_from_repoint_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[12]);
	ResidentFunctionBodyLogicalFrontendViewRow row = ResidentFunctionBodyLogicalFrontendViewRow{};
	row->owner_run_id = proof->owner_run_id;
	row->source_unit_id = proof->source_unit_id;
	row->source_unit_repoint_proof_id = proof->repoint_proof_id;
	row->current_frontend_state_id = proof->current_frontend_state_id;
	row->current_frontend_node_list_snapshot_id = proof->current_frontend_node_list_snapshot_id;
	row->stable_non_body_node_count = proof->stable_non_body_node_count;
	row->reused_body_count = proof->reused_body_count;
	row->replacement_body_count = proof->replacement_body_count;
	row->build_new_body_count = proof->build_new_body_count;
	row->retained_body_node_count = proof->retained_body_node_count;
	row->repointed_body_node_count = proof->repointed_body_node_count;
	row->logical_body_node_count = proof->assembled_body_node_count;
	row->current_full_source_rebuild_row_count = proof->current_full_source_rebuild_row_count;
	row->cleanup_released_body_row_bytes = proof->cleanup_released_body_row_bytes;
	row->published_segment_count = proof->published_segment_count;
	row->published_segmented_count = proof->published_segmented_count;
	row->logical_source_unit_row_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(row->stable_non_body_node_count) + cast<int_t<>>(row->logical_body_node_count)));
	row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_replacement_only_id();
	row->status_id = __latency_fn_resident_function_body_logical_frontend_views_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_none_id();
	ResidentFrontendNodeListSnapshotRow snapshot = __latency_fn_resident_frontend_node_lists_snapshot_by_id(report, proof->current_frontend_node_list_snapshot_id);
	if (static_cast<bool>((cast<int_t<>>(snapshot->snapshot_id) > static_cast<int_t<> >(0)))) {
		row->current_source_unit_list_id = snapshot->list_id;
		row->current_source_unit_generation_id = snapshot->generation_id;
		row->current_source_unit_storage_kind_id = snapshot->storage_kind_id;
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(proof->status_id), cast<int_t<>>(__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id()))))) {
		row->status_id = __latency_fn_resident_function_body_logical_frontend_views_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_repoint_not_ready_id();
		row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id();
	}
	else {
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->snapshot_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(row->current_source_unit_list_id), static_cast<int_t<> >(0))))) {
			row->status_id = __latency_fn_resident_function_body_logical_frontend_views_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_missing_source_unit_list_id();
			row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id();
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->logical_body_node_count), static_cast<int_t<> >(0)))) {
				row->status_id = __latency_fn_resident_function_body_logical_frontend_views_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_empty_body_view_id();
				row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id();
			}
			else {
				if (static_cast<bool>((php::not_identical(cast<int_t<>>(row->logical_source_unit_row_count), cast<int_t<>>(proof->source_unit_row_count)) || php::not_identical(cast<int_t<>>(row->logical_source_unit_row_count), cast<int_t<>>(row->current_full_source_rebuild_row_count))))) {
					row->status_id = __latency_fn_resident_function_body_logical_frontend_views_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_views_blocked_reason_inconsistent_row_count_id();
					row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_blocked_id();
				}
				else {
					if (static_cast<bool>(((cast<int_t<>>(row->retained_body_node_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->repointed_body_node_count) > static_cast<int_t<> >(0))))) {
						row->view_kind_id = __latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id();
					}
				}
			}
		}
	}
	return row;
}

}
