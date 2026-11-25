#include <scpp/lang/php.hpp>
#include "__types/scheduler_api.hpp"
#include "__callable/__latency_fn_scheduler_api_artifact_kind_scheduler_api_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_source_model_deterministic_work_order_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_design_only_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_simulated_workers_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_design_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_kind_logical_worker_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_kind_coordinator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_task_kind_ordered_work_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
bool_t scheduler_api::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == scheduler_api::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_artifact_kind_scheduler_api_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::artifact_kind_scheduler_api_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_source_model_deterministic_work_order_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::source_model_deterministic_work_order_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_scheduler_model_design_only_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::scheduler_model_design_only_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_scheduler_model_one_thread_coordinator_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::scheduler_model_one_thread_coordinator_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_scheduler_model_worker_shadow_handoff_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::scheduler_model_worker_shadow_handoff_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_execution_model_simulated_workers_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::execution_model_simulated_workers_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_execution_model_one_thread_coordinator_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::execution_model_one_thread_coordinator_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::execution_model_worker_shadow_handoff_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_design_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::design_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_execution_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::execution_status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_execution_status_completed_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::execution_status_completed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[11]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::blocked_reason_worker_threads_deferred_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_worker_kind_logical_worker_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_kind_logical_worker_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_worker_kind_coordinator_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_kind_coordinator_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_scheduler_api_task_kind_ordered_work_id() {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::task_kind_ordered_work_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}
