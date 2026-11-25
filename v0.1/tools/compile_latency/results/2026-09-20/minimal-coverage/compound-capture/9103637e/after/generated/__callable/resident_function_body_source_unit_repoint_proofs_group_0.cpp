#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodySourceUnitRepointProofRow.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__types/resident_function_body_source_unit_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_assembly_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_missing_source_unit_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_no_repointed_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_body_exceeds_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_has_ready_mixed_assembly_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_cleanup_released_body_row_bytes_for_source.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_assembly_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_body_exceeds_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_missing_source_unit_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_no_repointed_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_cleanup_released_body_row_bytes_for_source.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_row_from_mixed_assembly.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
bool_t resident_function_body_source_unit_repoint_proofs::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_source_unit_repoint_proofs::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_replacement_only_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::repoint_kind_replacement_only_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::repoint_kind_mixed_reuse_and_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::repoint_kind_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[6]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_assembly_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::blocked_reason_assembly_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_missing_source_unit_list_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::blocked_reason_missing_source_unit_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_no_repointed_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::blocked_reason_no_repointed_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_body_exceeds_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::blocked_reason_body_exceeds_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_source_unit_repoint_proofs_has_ready_mixed_assembly_proofs(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::has_ready_mixed_assembly_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[11]);
	auto __latency_local_0 = report->resident_function_body_mixed_frontend_assembly_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto assembly = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(assembly->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(assembly->status_id), cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_source_unit_repoint_proofs_cleanup_released_body_row_bytes_for_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::cleanup_released_body_row_bytes_for_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[12]);
	int_t<> bytes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(result->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			bytes = (bytes + cast<int_t<>>(result->cleanup_released_body_row_bytes));
		}
	}
	return __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int(bytes);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_source_unit_repoint_proofs[]; }
namespace scpp {
ResidentFunctionBodySourceUnitRepointProofRow __latency_fn_resident_function_body_source_unit_repoint_proofs_row_from_mixed_assembly(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyMixedFrontendAssemblyProofRow assembly) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_source_unit_repoint_proofs::row_from_mixed_assembly", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_source_unit_repoint_proofs.phs", __latency_lines_resident_function_body_source_unit_repoint_proofs[13]);
	ResidentFunctionBodySourceUnitRepointProofRow row = ResidentFunctionBodySourceUnitRepointProofRow{};
	row->owner_run_id = assembly->owner_run_id;
	row->source_unit_id = assembly->source_unit_id;
	row->mixed_frontend_assembly_proof_id = assembly->assembly_proof_id;
	row->current_frontend_state_id = assembly->current_frontend_state_id;
	row->current_frontend_node_list_snapshot_id = assembly->current_frontend_node_list_snapshot_id;
	row->reused_body_count = assembly->reused_body_count;
	row->replacement_body_count = assembly->replacement_body_count;
	row->build_new_body_count = assembly->build_new_body_count;
	row->retained_body_node_count = assembly->reused_body_node_count;
	row->repointed_body_node_count = assembly->replacement_body_node_count;
	row->assembled_body_node_count = assembly->assembled_body_node_count;
	row->cleanup_released_body_row_bytes = __latency_fn_resident_function_body_source_unit_repoint_proofs_cleanup_released_body_row_bytes_for_source(report, assembly->owner_run_id, assembly->source_unit_id);
	row->published_segment_count = assembly->published_segment_count;
	row->published_segmented_count = assembly->published_segmented_count;
	row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_replacement_only_id();
	row->status_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_none_id();
	ResidentFrontendNodeListSnapshotRow snapshot = __latency_fn_resident_frontend_node_lists_snapshot_by_id(report, assembly->current_frontend_node_list_snapshot_id);
	if (static_cast<bool>((cast<int_t<>>(snapshot->snapshot_id) > static_cast<int_t<> >(0)))) {
		row->source_unit_row_count = snapshot->row_count;
		row->current_full_source_rebuild_row_count = snapshot->row_count;
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(assembly->status_id), cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id()))))) {
		row->status_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_assembly_not_ready_id();
		row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id();
	}
	else {
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->snapshot_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(row->source_unit_row_count), static_cast<int_t<> >(0))))) {
			row->status_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_missing_source_unit_list_id();
			row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id();
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->repointed_body_node_count), static_cast<int_t<> >(0)))) {
				row->status_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_no_repointed_body_id();
				row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id();
			}
			else {
				if (static_cast<bool>((cast<int_t<>>(row->assembled_body_node_count) > cast<int_t<>>(row->source_unit_row_count)))) {
					row->status_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_blocked_reason_body_exceeds_source_unit_id();
					row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_blocked_id();
				}
				else {
					row->stable_non_body_node_count = __latency_fn_resident_function_body_source_unit_repoint_proofs_uint32_from_int((cast<int_t<>>(row->source_unit_row_count) - cast<int_t<>>(row->assembled_body_node_count)));
					if (static_cast<bool>(((cast<int_t<>>(row->retained_body_node_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->repointed_body_node_count) > static_cast<int_t<> >(0))))) {
						row->repoint_kind_id = __latency_fn_resident_function_body_source_unit_repoint_proofs_repoint_kind_mixed_reuse_and_replacement_id();
					}
				}
			}
		}
	}
	return row;
}

}
