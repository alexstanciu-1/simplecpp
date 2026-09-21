#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentChangeEventRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/resident_change_events.hpp"
#include "__callable/__latency_fn_resident_change_events_append_event.hpp"
#include "__callable/__latency_fn_resident_change_events_event_family_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_family_symbol_definition_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_change_events_has_events_for_owner.hpp"
#include "__callable/__latency_fn_resident_change_events_append_event.hpp"
#include "__callable/__latency_fn_resident_change_events_append_from_owner_changes.hpp"
#include "__callable/__latency_fn_resident_change_events_has_events_for_owner.hpp"
#include "__callable/__latency_fn_resident_change_events_row_from_source_change.hpp"
#include "__callable/__latency_fn_resident_change_events_row_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_change_events_count_events.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_name.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_deleted_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_new_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_source_no_change_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_added_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_deleted_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_no_change_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_event_symbol_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_change_events_debug_string.hpp"
#include "__callable/__latency_fn_resident_change_events_event_kind_name.hpp"
namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
void __latency_fn_resident_change_events_append_event(shared_p<CompilerProjectRunReport>& report, ResidentChangeEventRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::append_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[24]);
	row->event_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_change_events));
	(void) report->resident_change_events.append(row);
	report->resident_change_event_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_change_events));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_family_id), cast<int_t<>>(__latency_fn_resident_change_events_event_family_source_unit_id())))) {
		report->resident_change_event_source_unit_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_change_event_source_unit_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_family_id), cast<int_t<>>(__latency_fn_resident_change_events_event_family_symbol_definition_id())))) {
		report->resident_change_event_symbol_definition_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_change_event_symbol_definition_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_body_changed_id())))) {
		report->resident_change_event_body_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_change_event_body_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_kind_id), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_public_surface_changed_id())))) {
		report->resident_change_event_public_surface_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_change_event_public_surface_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
bool_t __latency_fn_resident_change_events_has_events_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::has_events_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[25]);
	auto __latency_local_0 = report->resident_change_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto event = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(event->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
void __latency_fn_resident_change_events_append_from_owner_changes(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::append_from_owner_changes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[26]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_change_events_has_events_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_change_events_append_event(report, __latency_fn_resident_change_events_row_from_source_change(change));
		}
	}
	auto __latency_local_2 = report->resident_symbol_definition_changes;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto change = __latency_local_3.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_change_events_append_event(report, __latency_fn_resident_change_events_row_from_symbol_change(change));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_change_events_count_events(shared_p<CompilerProjectRunReport> report, int_t<std::uint16_t> eventKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::count_events", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[27]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_change_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto event = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(event->event_kind_id), cast<int_t<>>(eventKindId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
string_t __latency_fn_resident_change_events_event_kind_name(int_t<std::uint16_t> eventKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::event_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[28]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_source_new_id())))) {
		return string_t("source_new");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_source_no_change_id())))) {
		return string_t("source_no_change");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_source_content_changed_id())))) {
		return string_t("source_content_changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_source_deleted_id())))) {
		return string_t("source_deleted");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_added_id())))) {
		return string_t("symbol_added");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_no_change_id())))) {
		return string_t("symbol_no_change");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_public_surface_changed_id())))) {
		return string_t("symbol_public_surface_changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_body_changed_id())))) {
		return string_t("symbol_body_changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_value_changed_id())))) {
		return string_t("symbol_value_changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), cast<int_t<>>(__latency_fn_resident_change_events_event_symbol_deleted_id())))) {
		return string_t("symbol_deleted");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_change_events[]; }
namespace scpp {
string_t __latency_fn_resident_change_events_debug_string(ResidentChangeEventRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_change_events::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_change_events.phs", __latency_lines_resident_change_events[29]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("resident_change_event:") + cast<string_t>(cast<int_t<>>(row->event_id)) + string_t(":") + cast<string_t>(__latency_fn_resident_change_events_event_kind_name(row->event_kind_id)) + string_t(":source=") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":symbol=") + cast<string_t>(cast<int_t<>>(row->symbol_id)));
}

}
