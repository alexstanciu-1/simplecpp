#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_content_changed_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_kind_name.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_new_source_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_cleanup_deleted_source_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_name.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_run_tokenize_parse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_skip_tokenize_parse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_status_blocked_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_status_name.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_snapshots_match.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_content_changed_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_kind_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_new_source_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_snapshots_match.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_cleanup_deleted_source_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_run_tokenize_parse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_skip_tokenize_parse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_source_unit_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_new_source_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_dirty_status_from_decision_kind.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_blocked_dirty_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_reuse_status_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_owner_run_id_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_unit_id_from_snapshots.hpp"
namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
string_t __latency_fn_source_unit_early_skip_decision_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::decision_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[15]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		return string_t("no_change_skip");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_content_changed_reparse_id())))) {
		return string_t("content_changed_reparse");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_new_source_reparse_id())))) {
		return string_t("new_source_reparse");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id())))) {
		return string_t("deleted_source_cleanup");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
string_t __latency_fn_source_unit_early_skip_action_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::action_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_action_skip_tokenize_parse_id())))) {
		return string_t("skip_tokenize_parse");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_action_run_tokenize_parse_id())))) {
		return string_t("run_tokenize_parse");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_action_cleanup_deleted_source_id())))) {
		return string_t("cleanup_deleted_source");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
string_t __latency_fn_source_unit_early_skip_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_source_unit_early_skip_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
bool_t __latency_fn_source_unit_early_skip_snapshots_match(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::snapshots_match", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[18]);
	return (((((cast<int_t<>>(previous->snapshot_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(current->snapshot_id) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(previous->content_hash), cast<int_t<>>(current->content_hash))) && php::identical(cast<int_t<>>(previous->source_length), cast<int_t<>>(current->source_length))) && php::identical(cast<int_t<>>(previous->line_count), cast<int_t<>>(current->line_count)));
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_early_skip_decision_kind_from_snapshots(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::decision_kind_from_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[19]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(current->snapshot_id), static_cast<int_t<> >(0)) && (cast<int_t<>>(previous->snapshot_id) > static_cast<int_t<> >(0))))) {
		return __latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_source_unit_early_skip_decision_new_source_reparse_id();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_source_unit_early_skip_snapshots_match(current, previous)))) {
		return __latency_fn_source_unit_early_skip_decision_no_change_skip_id();
	}
	return __latency_fn_source_unit_early_skip_decision_content_changed_reparse_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_early_skip_action_from_decision_kind(int_t<std::uint16_t> decisionKindId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::action_from_decision_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[20]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		return __latency_fn_source_unit_early_skip_action_skip_tokenize_parse_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id())))) {
		return __latency_fn_source_unit_early_skip_action_cleanup_deleted_source_id();
	}
	return __latency_fn_source_unit_early_skip_action_run_tokenize_parse_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_early_skip_dirty_status_from_decision_kind(int_t<std::uint16_t> decisionKindId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::dirty_status_from_decision_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[21]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		return __latency_fn_resident_definition_granularity_dirty_none_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_new_source_reparse_id())))) {
		return __latency_fn_resident_definition_granularity_dirty_new_id();
	}
	return __latency_fn_resident_definition_granularity_dirty_source_unit_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_early_skip_reuse_status_from_decision_kind(int_t<std::uint16_t> decisionKindId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::reuse_status_from_decision_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[22]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		return __latency_fn_resident_definition_granularity_reuse_ready_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id())))) {
		return __latency_fn_resident_definition_granularity_reuse_none_id();
	}
	return __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_early_skip_owner_run_id_from_snapshots(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> fallbackOwnerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::owner_run_id_from_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[23]);
	if (static_cast<bool>((cast<int_t<>>(current->owner_run_id) > static_cast<int_t<> >(0)))) {
		return current->owner_run_id;
	}
	if (static_cast<bool>((cast<int_t<>>(previous->owner_run_id) > static_cast<int_t<> >(0)))) {
		return previous->owner_run_id;
	}
	return cast<int_t<std::uint32_t>>(fallbackOwnerRunId);
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_early_skip_source_unit_id_from_snapshots(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::source_unit_id_from_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[24]);
	if (static_cast<bool>((cast<int_t<>>(current->source_unit_id) > static_cast<int_t<> >(0)))) {
		return current->source_unit_id;
	}
	return previous->source_unit_id;
}

}
