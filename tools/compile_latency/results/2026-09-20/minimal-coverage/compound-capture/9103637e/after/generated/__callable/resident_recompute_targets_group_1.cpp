#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentRecomputeTargetRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/ResidentTransactionStepRow.hpp"
#include "__types/resident_recompute_targets.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_duplicate_target_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_step.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_commit_status_committed_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_commit_status_skipped_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_skip_reason_duplicate_target_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_skip_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_step.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_target.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_duplicate_target_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_skipped_duplicate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_new_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_reparse_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_target.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_source_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_source_unit_reparse_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_dependent_resolution_targets.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_dependent_resolution_targets_from_lookup_cursor.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_append_for_symbol_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_rerun_dependent_resolution_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_dependent_resolution_targets_from_lookup_cursor.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_target.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_dependent_resolution_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_matches_change.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_body_dependencies_append_targets_for_value_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_recompute_local_lowering_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_action_refresh_backend_owner_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_dependent_resolution_targets.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_target.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_append_targets_for_symbol_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_backend_refresh_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_local_lowering_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_public_surface_publish_id.hpp"
namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_recompute_targets_duplicate_target_id(shared_p<CompilerProjectRunReport> report, ResidentRecomputeTargetRow candidate) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::duplicate_target_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[16]);
	auto __latency_local_0 = report->resident_recompute_targets;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto target = __latency_local_1.value_copy();
		if (static_cast<bool>((((((php::identical(cast<int_t<>>(target->owner_run_id), cast<int_t<>>(candidate->owner_run_id)) && php::identical(cast<int_t<>>(target->source_unit_id), cast<int_t<>>(candidate->source_unit_id))) && php::identical(cast<int_t<>>(target->symbol_id), cast<int_t<>>(candidate->symbol_id))) && php::identical(cast<int_t<>>(target->target_kind_id), cast<int_t<>>(candidate->target_kind_id))) && php::identical(cast<int_t<>>(target->action_kind_id), cast<int_t<>>(candidate->action_kind_id))) && php::identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id()))))) {
			return target->target_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_step(shared_p<CompilerProjectRunReport>& report, ResidentRecomputeTargetRow target) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_step", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[17]);
	ResidentTransactionStepRow step = ResidentTransactionStepRow{};
	step->step_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_transaction_steps));
	step->owner_run_id = target->owner_run_id;
	step->generation = target->owner_run_id;
	step->target_id = target->target_id;
	step->action_kind_id = target->action_kind_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id())))) {
		step->commit_status_id = __latency_fn_resident_recompute_targets_commit_status_committed_id();
		step->skip_reason_id = __latency_fn_resident_recompute_targets_skip_reason_none_id();
	}
	else {
		step->commit_status_id = __latency_fn_resident_recompute_targets_commit_status_skipped_id();
		step->skip_reason_id = __latency_fn_resident_recompute_targets_skip_reason_duplicate_target_id();
	}
	(void) report->resident_transaction_steps.append(step);
	report->resident_transaction_step_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_transaction_steps));
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_target(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> reasonChangeId, int_t<std::uint16_t> targetKindId, int_t<std::uint16_t> actionKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_target", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[18]);
	ResidentRecomputeTargetRow target = ResidentRecomputeTargetRow{};
	target->target_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_recompute_targets));
	target->owner_run_id = ownerRunId;
	target->source_unit_id = sourceUnitId;
	target->symbol_id = symbolId;
	target->reason_change_id = reasonChangeId;
	target->target_kind_id = targetKindId;
	target->action_kind_id = actionKindId;
	int_t<std::uint32_t> duplicateId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_recompute_targets_duplicate_target_id(report, target));
	if (static_cast<bool>((cast<int_t<>>(duplicateId) > static_cast<int_t<> >(0)))) {
		target->duplicate_of_target_id = duplicateId;
		target->status_id = __latency_fn_resident_recompute_targets_status_skipped_duplicate_id();
		report->resident_recompute_skipped_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_recompute_skipped_count) + static_cast<int_t<> >(1)));
	}
	else {
		target->status_id = __latency_fn_resident_recompute_targets_status_selected_id();
		report->resident_recompute_selected_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_recompute_selected_count) + static_cast<int_t<> >(1)));
	}
	(void) report->resident_recompute_targets.append(target);
	report->resident_recompute_target_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_recompute_targets));
	__latency_fn_resident_recompute_targets_append_step(report, target);
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_targets_for_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_targets_for_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[19]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_new_id())) || php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_content_changed_id()))) || php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_deleted_id()))))) {
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, __latency_fn_structure_row_ids_none_id(), change->change_id, __latency_fn_resident_recompute_targets_target_source_unit_reparse_id(), __latency_fn_resident_recompute_targets_action_reparse_source_unit_id());
	}
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_dependent_resolution_targets(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_dependent_resolution_targets", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[20]);
	int_t<std::uint32_t> firstLookupId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_reverse_dependencies_append_for_symbol_change(report, change));
	__latency_fn_resident_recompute_targets_append_dependent_resolution_targets_from_lookup_cursor(report, change, cast<int_t<std::uint32_t>>(firstLookupId));
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_dependent_resolution_targets_from_lookup_cursor(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change, int_t<std::uint32_t> firstLookupId) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_dependent_resolution_targets_from_lookup_cursor", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[21]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(firstLookupId), static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> lookupId = required_cast<int_t<>>(cast<int_t<>>(firstLookupId));
	while (static_cast<bool>((lookupId <= php::count(report->resident_reverse_dependency_lookups)))) {
		ResidentReverseDependencyLookupRow lookup = report->resident_reverse_dependency_lookups[__latency_fn_structure_row_ids_dense_index(__latency_fn_structure_row_ids_uint32_from_int(lookupId))];
		if (static_cast<bool>((!__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(lookup, change)))) {
			return;
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_reverse_dependencies_lookup_matches_change(lookup, change)))) {
			__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, lookup->consumer_source_unit_id, lookup->consumer_symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_dependent_resolution_id(), __latency_fn_resident_recompute_targets_action_rerun_dependent_resolution_id());
		}
		lookupId = (lookupId + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_recompute_targets[]; }
namespace scpp {
void __latency_fn_resident_recompute_targets_append_targets_for_symbol_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_recompute_targets::append_targets_for_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_recompute_targets.phs", __latency_lines_resident_recompute_targets[22]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_added_id())))) {
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_public_surface_publish_id(), __latency_fn_resident_recompute_targets_action_publish_public_surface_id());
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_local_lowering_id(), __latency_fn_resident_recompute_targets_action_recompute_local_lowering_id());
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_backend_refresh_id(), __latency_fn_resident_recompute_targets_action_refresh_backend_owner_id());
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_body_changed_id())))) {
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_local_lowering_id(), __latency_fn_resident_recompute_targets_action_recompute_local_lowering_id());
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_backend_refresh_id(), __latency_fn_resident_recompute_targets_action_refresh_backend_owner_id());
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id())))) {
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_public_surface_publish_id(), __latency_fn_resident_recompute_targets_action_publish_public_surface_id());
		__latency_fn_resident_recompute_targets_append_dependent_resolution_targets(report, change);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_value_changed_id())))) {
		__latency_fn_resident_body_dependencies_append_targets_for_value_change(report, change);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_deleted_id())))) {
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_public_surface_publish_id(), __latency_fn_resident_recompute_targets_action_publish_public_surface_id());
		__latency_fn_resident_recompute_targets_append_target(report, change->owner_run_id, change->source_unit_id, change->symbol_id, change->change_id, __latency_fn_resident_recompute_targets_target_backend_refresh_id(), __latency_fn_resident_recompute_targets_action_refresh_backend_owner_id());
	}
}

}
