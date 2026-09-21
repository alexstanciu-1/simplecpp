#include <scpp/lang/php.hpp>
#include "__types/ResidentChangeEventRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_value_dependents_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_change_events_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_deleted_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_new_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_no_change_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_no_change_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_value_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_none_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_local_body_backend_owner_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_none_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_public_surface_and_dependents_id.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_value_dependents_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_family_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_row_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_family_symbol_definition_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_propagation_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_row_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_propagation_value_dependents_id() {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::propagation_value_dependents_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_event_kind_from_source_change(int_t<std::uint16_t> changeKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::event_kind_from_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_new_id())))) {
		return __latency_fn_resident_change_events_event_source_new_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_no_change_id())))) {
		return __latency_fn_resident_change_events_event_source_no_change_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_content_changed_id())))) {
		return __latency_fn_resident_change_events_event_source_content_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_deleted_id())))) {
		return __latency_fn_resident_change_events_event_source_deleted_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_event_kind_from_symbol_change(int_t<std::uint16_t> changeKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::event_kind_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_added_id())))) {
		return __latency_fn_resident_change_events_event_symbol_added_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_no_change_id())))) {
		return __latency_fn_resident_change_events_event_symbol_no_change_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id())))) {
		return __latency_fn_resident_change_events_event_symbol_public_surface_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_body_changed_id())))) {
		return __latency_fn_resident_change_events_event_symbol_body_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_value_changed_id())))) {
		return __latency_fn_resident_change_events_event_symbol_value_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_deleted_id())))) {
		return __latency_fn_resident_change_events_event_symbol_deleted_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_propagation_from_source_change(int_t<std::uint16_t> changeKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::propagation_from_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[20]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_no_change_id())))) {
		return __latency_fn_resident_change_events_propagation_none_id();
	}
	return __latency_fn_resident_change_events_propagation_source_unit_id();
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_change_events_propagation_from_symbol_change(int_t<std::uint16_t> changeKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::propagation_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[21]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_no_change_id())))) {
		return __latency_fn_resident_change_events_propagation_none_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_body_changed_id())))) {
		return __latency_fn_resident_change_events_propagation_local_body_backend_owner_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_value_changed_id())))) {
		return __latency_fn_resident_change_events_propagation_value_dependents_id();
	}
	return __latency_fn_resident_change_events_propagation_public_surface_and_dependents_id();
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
ResidentChangeEventRow __latency_fn_resident_change_events_row_from_source_change(ResidentSourceUnitChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::row_from_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[22]);
	ResidentChangeEventRow row = ResidentChangeEventRow{};
	row->owner_run_id = change->owner_run_id;
	row->source_unit_id = change->source_unit_id;
	row->symbol_id = __latency_fn_structure_row_ids_none_id();
	row->reason_change_id = change->change_id;
	row->event_family_id = __latency_fn_resident_change_events_event_family_source_unit_id();
	row->event_kind_id = __latency_fn_resident_change_events_event_kind_from_source_change(change->change_kind_id);
	row->propagation_scope_id = __latency_fn_resident_change_events_propagation_from_source_change(change->change_kind_id);
	row->dirty_scope_id = change->dirty_status_id;
	row->reuse_scope_id = change->reuse_status_id;
	row->status_id = __latency_fn_resident_change_events_status_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
ResidentChangeEventRow __latency_fn_resident_change_events_row_from_symbol_change(ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::row_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[23]);
	ResidentChangeEventRow row = ResidentChangeEventRow{};
	row->owner_run_id = change->owner_run_id;
	row->source_unit_id = change->source_unit_id;
	row->symbol_id = change->symbol_id;
	row->reason_change_id = change->change_id;
	row->event_family_id = __latency_fn_resident_change_events_event_family_symbol_definition_id();
	row->event_kind_id = __latency_fn_resident_change_events_event_kind_from_symbol_change(change->change_kind_id);
	row->propagation_scope_id = __latency_fn_resident_change_events_propagation_from_symbol_change(change->change_kind_id);
	row->dirty_scope_id = change->dirty_scope_id;
	row->reuse_scope_id = change->reuse_scope_id;
	row->status_id = __latency_fn_resident_change_events_status_ready_id();
	return row;
}

}
