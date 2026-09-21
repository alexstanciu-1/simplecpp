#include <scpp/lang/php.hpp>
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
#include "__callable/__latency_fn_scheduler_api_artifact_kind_scheduler_api_id.hpp"
#include "__callable/__latency_fn_scheduler_api_new_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_design_only_id.hpp"
#include "__callable/__latency_fn_scheduler_api_source_model_deterministic_work_order_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_new_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_new_one_thread_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_scheduler_api_new_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_new_worker_shadow_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_scheduler_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id.hpp"
#include "__callable/__latency_fn_scheduler_api_design_status_ready_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_simulated_workers_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_session.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_scheduler_api_design_status_ready_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_session.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_scheduler_api_design_status_ready_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_session.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_kind_logical_worker_id.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_kind_coordinator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_kind_logical_worker_id.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_task.hpp"
#include "__callable/__latency_fn_scheduler_api_task_kind_ordered_work_id.hpp"
namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_new_artifact(int_t<> sessionCapacity, int_t<> workerCapacity, int_t<> taskCapacity) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[16]);
	shared_p<SchedulerApiArtifact> artifact = create<SchedulerApiArtifact>();
	artifact->artifact_kind_id = __latency_fn_scheduler_api_artifact_kind_scheduler_api_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_scheduler_api_source_model_deterministic_work_order_id();
	artifact->scheduler_model_id = __latency_fn_scheduler_api_scheduler_model_design_only_id();
	php::vector_reserve(artifact->sessions, sessionCapacity);
	php::vector_reserve(artifact->workers, workerCapacity);
	php::vector_reserve(artifact->tasks, taskCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_new_one_thread_artifact(int_t<> taskCapacity) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::new_one_thread_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[17]);
	shared_p<SchedulerApiArtifact> artifact = __latency_fn_scheduler_api_new_artifact(static_cast<int_t<> >(1), static_cast<int_t<> >(1), taskCapacity);
	artifact->scheduler_model_id = __latency_fn_scheduler_api_scheduler_model_one_thread_coordinator_id();
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_new_worker_shadow_artifact(int_t<> workerCapacity, int_t<> taskCapacity) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::new_worker_shadow_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[18]);
	shared_p<SchedulerApiArtifact> artifact = __latency_fn_scheduler_api_new_artifact(static_cast<int_t<> >(1), workerCapacity, taskCapacity);
	artifact->scheduler_model_id = __latency_fn_scheduler_api_scheduler_model_worker_shadow_handoff_id();
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerSessionRow __latency_fn_scheduler_api_session(int_t<std::uint32_t> generation, int_t<std::uint32_t> requestedWorkerCount, int_t<std::uint32_t> taskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::session", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[19]);
	SchedulerSessionRow row = SchedulerSessionRow{};
	row->generation = generation;
	row->requested_worker_count = requestedWorkerCount;
	row->task_count = taskCount;
	row->execution_model_id = __latency_fn_scheduler_api_execution_model_simulated_workers_id();
	row->design_status_id = __latency_fn_scheduler_api_design_status_ready_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_blocked_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerSessionRow __latency_fn_scheduler_api_one_thread_session(int_t<std::uint32_t> generation, int_t<std::uint32_t> taskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::one_thread_session", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[20]);
	SchedulerSessionRow row = SchedulerSessionRow{};
	row->generation = generation;
	row->requested_worker_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->task_count = taskCount;
	row->execution_model_id = __latency_fn_scheduler_api_execution_model_one_thread_coordinator_id();
	row->design_status_id = __latency_fn_scheduler_api_design_status_ready_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_completed_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerSessionRow __latency_fn_scheduler_api_worker_shadow_session(int_t<std::uint32_t> generation, int_t<std::uint32_t> requestedWorkerCount, int_t<std::uint32_t> taskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_shadow_session", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[21]);
	SchedulerSessionRow row = SchedulerSessionRow{};
	row->generation = generation;
	row->requested_worker_count = requestedWorkerCount;
	row->task_count = taskCount;
	row->execution_model_id = __latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id();
	row->design_status_id = __latency_fn_scheduler_api_design_status_ready_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_completed_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerWorkerRow __latency_fn_scheduler_api_worker(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> workerIndex, int_t<std::uint32_t> assignedTaskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[22]);
	SchedulerWorkerRow row = SchedulerWorkerRow{};
	row->session_id = sessionId;
	row->worker_index = workerIndex;
	row->assigned_task_count = assignedTaskCount;
	row->worker_kind_id = __latency_fn_scheduler_api_worker_kind_logical_worker_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_blocked_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerWorkerRow __latency_fn_scheduler_api_one_thread_worker(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> assignedTaskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::one_thread_worker", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[23]);
	SchedulerWorkerRow row = SchedulerWorkerRow{};
	row->session_id = sessionId;
	row->worker_index = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->assigned_task_count = assignedTaskCount;
	row->worker_kind_id = __latency_fn_scheduler_api_worker_kind_coordinator_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_completed_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerWorkerRow __latency_fn_scheduler_api_worker_shadow_worker(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> workerIndex, int_t<std::uint32_t> assignedTaskCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_shadow_worker", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[24]);
	SchedulerWorkerRow row = SchedulerWorkerRow{};
	row->session_id = sessionId;
	row->worker_index = workerIndex;
	row->assigned_task_count = assignedTaskCount;
	row->worker_kind_id = __latency_fn_scheduler_api_worker_kind_logical_worker_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_completed_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerTaskRow __latency_fn_scheduler_api_task(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> workerId, int_t<std::uint32_t> workId, int_t<std::uint32_t> partitionId, int_t<std::uint32_t> outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::task", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[25]);
	SchedulerTaskRow row = SchedulerTaskRow{};
	row->session_id = sessionId;
	row->worker_id = workerId;
	row->work_id = workId;
	row->partition_id = partitionId;
	row->output_order_id = outputOrderId;
	row->task_kind_id = __latency_fn_scheduler_api_task_kind_ordered_work_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_blocked_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_worker_threads_deferred_id();
	return row;
}

}
