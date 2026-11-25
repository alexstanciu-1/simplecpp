#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__types/source_unit_frontend_work_decisions.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_reuse_previous_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_build_replacement_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_cleanup_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_build_new_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_new_source_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_kind_from_early_skip.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_build_new_list_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_build_replacement_list_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_cleanup_list_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_reuse_previous_list_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
bool_t source_unit_frontend_work_decisions::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_unit_frontend_work_decisions::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_frontend_work_decisions_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::decision_reuse_previous_lists_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::decision_parse_replacement_lists_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::decision_cleanup_deleted_lists_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::decision_build_new_lists_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_action_reuse_previous_list_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::action_reuse_previous_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_action_build_replacement_list_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::action_build_replacement_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_action_cleanup_list_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::action_cleanup_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_action_build_new_list_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::action_build_new_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[10]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_decision_kind_from_early_skip(ResidentSourceUnitEarlySkipDecisionRow earlySkip) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::decision_kind_from_early_skip", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[11]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(earlySkip->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(earlySkip->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(earlySkip->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_new_source_reparse_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id();
	}
	return __latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id();
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_work_decisions_action_from_decision_kind(int_t<std::uint16_t> decisionKindId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::action_from_decision_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[12]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_action_reuse_previous_list_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_action_cleanup_list_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id())))) {
		return __latency_fn_source_unit_frontend_work_decisions_action_build_new_list_id();
	}
	return __latency_fn_source_unit_frontend_work_decisions_action_build_replacement_list_id();
}

}
