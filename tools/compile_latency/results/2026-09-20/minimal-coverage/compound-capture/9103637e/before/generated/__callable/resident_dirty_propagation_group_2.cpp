#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentChangeEventRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__callable/__latency_fn_resident_change_events_event_family_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_reason_from_event.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_backend_refresh_mask.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_dependent_resolution_mask.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_local_lowering_mask.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_mask_from_reason.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_public_surface_publish_mask.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_source_reparse_mask.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_value_policy_mask.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_reason_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_mask_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_mask_from_reason.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_reason_strength.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_reason_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_reason_strength.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_row_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_stage_mask_from_event.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_status_queued_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_append_queue.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_entity_kind_symbol_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_dirty_propagation_reason_from_event(ResidentChangeEventRow event) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::reason_from_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[32]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_family_id), cast<int_t<>>(__latency_fn_resident_change_events_event_family_source_unit_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_added_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_body_changed_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_public_surface_changed_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_value_changed_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_deleted_id())))) {
		return __latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_stage_mask_from_reason(int_t<std::uint16_t> dirtyReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::stage_mask_from_reason", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[33]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id())))) {
		return __latency_fn_resident_dirty_propagation_stage_source_reparse_mask();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id())))) {
		return __latency_fn_structure_row_ids_uint32_from_int(((cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_public_surface_publish_mask()) + cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_local_lowering_mask())) + cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_backend_refresh_mask())));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id())))) {
		return __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_local_lowering_mask()) + cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_backend_refresh_mask())));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id())))) {
		return __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_public_surface_publish_mask()) + cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_dependent_resolution_mask())));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id())))) {
		return __latency_fn_resident_dirty_propagation_stage_value_policy_mask();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id())))) {
		return __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_public_surface_publish_mask()) + cast<int_t<>>(__latency_fn_resident_dirty_propagation_stage_backend_refresh_mask())));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_stage_mask_from_event(ResidentChangeEventRow event) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::stage_mask_from_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[34]);
	return __latency_fn_resident_dirty_propagation_stage_mask_from_reason(__latency_fn_resident_dirty_propagation_reason_from_event(event));
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_dirty_propagation_reason_strength(int_t<std::uint16_t> dirtyReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::reason_strength", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[35]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_source_unit_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_value_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_added_id())) || php::identical(cast<int_t<>>(dirtyReasonId), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_deleted_id()))))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
ResidentDirtyQueueRow __latency_fn_resident_dirty_propagation_row_from_event(ResidentChangeEventRow event) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::row_from_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[36]);
	int_t<std::uint16_t> reasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_dirty_propagation_reason_from_event(event));
	ResidentDirtyQueueRow row = ResidentDirtyQueueRow{};
	row->owner_run_id = event->owner_run_id;
	row->source_unit_id = event->source_unit_id;
	row->symbol_id = event->symbol_id;
	row->reason_event_id = event->event_id;
	row->reason_change_id = event->reason_change_id;
	row->stage_mask = __latency_fn_resident_dirty_propagation_stage_mask_from_event(event);
	row->entity_kind_id = __latency_fn_resident_dirty_propagation_entity_kind_from_event(event);
	row->dirty_reason_id = reasonId;
	row->reason_strength_id = __latency_fn_resident_dirty_propagation_reason_strength(cast<int_t<std::uint16_t>>(reasonId));
	row->depth = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0));
	row->status_id = __latency_fn_resident_dirty_propagation_status_queued_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_dirty_propagation[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_queue(shared_p<CompilerProjectRunReport>& report, ResidentDirtyQueueRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_dirty_propagation::append_queue", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_dirty_propagation.phs", __latency_lines_resident_dirty_propagation[37]);
	row->queue_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_dirty_queue_rows));
	(void) report->resident_dirty_queue_rows.append(row);
	report->resident_dirty_queue_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_dirty_queue_rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->entity_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_entity_kind_source_unit_id())))) {
		report->resident_dirty_queue_source_unit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_queue_source_unit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->entity_kind_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_entity_kind_symbol_id())))) {
		report->resident_dirty_queue_symbol_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_queue_symbol_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->dirty_reason_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_body_id())))) {
		report->resident_dirty_queue_body_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_queue_body_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->dirty_reason_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id())))) {
		report->resident_dirty_queue_public_surface_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_dirty_queue_public_surface_count) + static_cast<int_t<> >(1)));
	}
	return row->queue_id;
}

}
