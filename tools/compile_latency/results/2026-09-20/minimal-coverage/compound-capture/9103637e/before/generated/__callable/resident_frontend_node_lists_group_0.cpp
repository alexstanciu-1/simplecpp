#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNodeListRef.hpp"
#include "__types/ProjectFrontendModel.hpp"
#include "__types/ProjectFrontendSourceRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/resident_frontend_node_lists.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_new_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_should_publish_for_source_change.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_storage_kind_from_source_row.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_segmented_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_vector_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_status_current_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_from_source_row.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_storage_kind_from_source_row.hpp"
#include "__callable/__latency_fn_row_segment_policy_default_segment_capacity.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_list_ref.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_status_current_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_from_model.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_append_snapshots_from_project_frontend.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_from_source_row.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_max_source_unit_id_from_publishable_changes.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_should_publish_for_source_change.hpp"
namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
bool_t resident_frontend_node_lists::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_frontend_node_lists::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_frontend_node_lists_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_resident_frontend_node_lists_should_publish_for_source_change(ResidentSourceUnitChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::should_publish_for_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[1]);
	return ((php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_new_id())) || php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_content_changed_id()))) || php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_deleted_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_frontend_node_lists_storage_kind_from_source_row(ProjectFrontendSourceRow sourceRow) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::storage_kind_from_source_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[2]);
	if (static_cast<bool>((cast<int_t<>>(sourceRow->row_segment_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_row_segment_policy_storage_segmented_id();
	}
	return __latency_fn_row_segment_policy_storage_vector_id();
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_from_source_row(int_t<std::uint32_t> ownerRunId, ProjectFrontendSourceRow sourceRow) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::snapshot_from_source_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[3]);
	ResidentFrontendNodeListSnapshotRow row = ResidentFrontendNodeListSnapshotRow{};
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceRow->source_unit_id;
	row->list_id = __latency_fn_row_segment_policy_initial_list_id();
	row->generation_id = __latency_fn_row_segment_policy_initial_generation_id();
	row->row_count = sourceRow->row_count;
	row->segment_capacity = __latency_fn_row_segment_policy_default_segment_capacity();
	row->segment_count = sourceRow->row_segment_count;
	row->reserved_segment_bytes = sourceRow->row_segment_reserved_bytes;
	row->segment_slack_bytes = sourceRow->row_segment_slack_bytes;
	row->storage_kind_id = __latency_fn_resident_frontend_node_lists_storage_kind_from_source_row(sourceRow);
	row->status_id = __latency_fn_resident_definition_granularity_status_current_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_from_model(int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::snapshot_from_model", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[4]);
	FrontendNodeListRef ref = __latency_fn_frontend_model_tables_node_list_ref(model);
	ResidentFrontendNodeListSnapshotRow row = ResidentFrontendNodeListSnapshotRow{};
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnitId;
	row->list_id = ref->list_id;
	row->generation_id = __latency_fn_row_segment_policy_initial_generation_id();
	row->row_count = ref->row_count;
	row->segment_capacity = ref->segment_capacity;
	row->segment_count = ref->segment_count;
	row->reserved_segment_bytes = ref->reserved_segment_bytes;
	row->segment_slack_bytes = ref->segment_slack_bytes;
	row->storage_kind_id = ref->storage_kind_id;
	row->status_id = __latency_fn_resident_definition_granularity_status_current_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_resident_frontend_node_lists_append_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentFrontendNodeListSnapshotRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::append_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[5]);
	row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_frontend_node_list_snapshots));
	(void) report->resident_frontend_node_list_snapshots.append(row);
	report->resident_frontend_node_list_snapshot_count = __latency_fn_resident_frontend_node_lists_uint32_from_int(php::count(report->resident_frontend_node_list_snapshots));
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_resident_frontend_node_lists_append_snapshots_from_project_frontend(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectFrontendModel> project, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::append_snapshots_from_project_frontend", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[6]);
	auto __latency_local_0 = project->source_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRow = __latency_local_1.value_copy();
		__latency_fn_resident_frontend_node_lists_append_snapshot(report, __latency_fn_resident_frontend_node_lists_snapshot_from_source_row(cast<int_t<std::uint32_t>>(ownerRunId), sourceRow));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::snapshot_by_owner_and_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[7]);
	auto __latency_local_0 = report->resident_frontend_node_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[8]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_frontend_node_list_snapshots))))) {
		ResidentFrontendNodeListSnapshotRow row = report->resident_frontend_node_list_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_frontend_node_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_resident_frontend_node_lists_max_source_unit_id_from_publishable_changes(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_frontend_node_lists::max_source_unit_id_from_publishable_changes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_frontend_node_lists.phs", __latency_lines_resident_frontend_node_lists[9]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_resident_frontend_node_lists_should_publish_for_source_change(change)) && (cast<int_t<>>(change->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(change->source_unit_id);
		}
	}
	return maxId;
}

}
