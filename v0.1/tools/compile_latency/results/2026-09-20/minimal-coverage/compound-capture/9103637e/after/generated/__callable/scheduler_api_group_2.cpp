#include <scpp/lang/php.hpp>
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
#include "__callable/__latency_fn_scheduler_api_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_scheduler_api_completed_task.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_scheduler_api_task_kind_ordered_work_id.hpp"
#include "__callable/__latency_fn_scheduler_api_append_session.hpp"
#include "__callable/__latency_fn_scheduler_api_design_status_ready_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_append_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_append_task.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_blocked_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_status_completed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_first_task.hpp"
#include "__callable/__latency_fn_scheduler_api_task_by_output_order.hpp"
#include "__callable/__latency_fn_scheduler_api_tasks_by_output_order.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value__exec.hpp"
namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerTaskRow __latency_fn_scheduler_api_completed_task(int_t<std::uint32_t> sessionId, int_t<std::uint32_t> workerId, DeterministicWorkOrderRow work) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::completed_task", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[26]);
	SchedulerTaskRow row = SchedulerTaskRow{};
	row->session_id = sessionId;
	row->worker_id = workerId;
	row->work_id = work->work_id;
	row->partition_id = work->partition_id;
	row->output_order_id = work->output_order_id;
	row->task_kind_id = __latency_fn_scheduler_api_task_kind_ordered_work_id();
	row->execution_status_id = __latency_fn_scheduler_api_execution_status_completed_id();
	row->blocked_reason_id = __latency_fn_scheduler_api_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scheduler_api_append_session(shared_p<SchedulerApiArtifact>& artifact, SchedulerSessionRow row) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::append_session", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[27]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->session_id), static_cast<int_t<> >(0)))) {
		row->session_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->sessions));
	}
	(void) artifact->sessions.append(row);
	artifact->session_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->sessions));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->design_status_id), cast<int_t<>>(__latency_fn_scheduler_api_design_status_ready_id())))) {
		artifact->design_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->design_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_blocked_id())))) {
		artifact->execution_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_completed_id())))) {
		artifact->execution_completed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_completed_count) + static_cast<int_t<> >(1)));
	}
	return row->session_id;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scheduler_api_append_worker(shared_p<SchedulerApiArtifact>& artifact, SchedulerWorkerRow row) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::append_worker", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[28]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->worker_id), static_cast<int_t<> >(0)))) {
		row->worker_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->workers));
	}
	(void) artifact->workers.append(row);
	artifact->worker_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->workers));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_blocked_id())))) {
		artifact->execution_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_completed_id())))) {
		artifact->execution_completed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_completed_count) + static_cast<int_t<> >(1)));
	}
	return row->worker_id;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scheduler_api_append_task(shared_p<SchedulerApiArtifact>& artifact, SchedulerTaskRow row) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::append_task", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[29]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->task_id), static_cast<int_t<> >(0)))) {
		row->task_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->tasks));
	}
	(void) artifact->tasks.append(row);
	artifact->task_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->tasks));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_blocked_id())))) {
		artifact->execution_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_status_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_status_completed_id())))) {
		artifact->execution_completed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->execution_completed_count) + static_cast<int_t<> >(1)));
	}
	return row->task_id;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerTaskRow __latency_fn_scheduler_api_first_task(shared_p<SchedulerApiArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::first_task", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[30]);
	if (static_cast<bool>((php::count(artifact->tasks) > static_cast<int_t<> >(0)))) {
		return artifact->tasks[static_cast<int_t<> >(0)];
	}
	SchedulerTaskRow empty = SchedulerTaskRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
SchedulerTaskRow __latency_fn_scheduler_api_task_by_output_order(shared_p<SchedulerApiArtifact> artifact, int_t<std::uint32_t> outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::task_by_output_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[31]);
	auto __latency_local_0 = artifact->tasks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto task = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(task->output_order_id), cast<int_t<>>(outputOrderId)))) {
			return task;
		}
	}
	SchedulerTaskRow empty = SchedulerTaskRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
vector_t<SchedulerTaskRow> __latency_fn_scheduler_api_tasks_by_output_order(shared_p<SchedulerApiArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::tasks_by_output_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[32]);
	int_t<> rowCount = required_cast<int_t<>>(cast<int_t<>>(artifact->task_count));
	vector_t<SchedulerTaskRow> tasks = {};
	php::vector_reserve(tasks, rowCount);
	SchedulerTaskRow empty = SchedulerTaskRow{};
	while (static_cast<bool>((php::count(tasks) < rowCount))) {
		(void) tasks.push_back(empty);
	}
	auto __latency_local_0 = artifact->tasks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto task = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(task->output_order_id, rowCount)))) {
			tasks.at(__latency_fn_structure_row_ids_dense_index(task->output_order_id)) = task;
		}
	}
	return tasks;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<> __latency_fn_scheduler_api_stable_hash_mix(int_t<> hash, int_t<> value, int_t<> multiplier, int_t<> modulus) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::stable_hash_mix", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[33]);
	int_t<> normalized = required_cast<int_t<>>((value % modulus));
	return ((((hash * multiplier) + normalized) + static_cast<int_t<> >(17)) % modulus);
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
void __latency_fn_scheduler_api_stable_hash_mix_value__exec(int_t<>& hashA, int_t<>& hashB, int_t<> value) {
	hashA = __latency_fn_scheduler_api_stable_hash_mix(hashA, value, static_cast<int_t<> >(131), static_cast<int_t<> >(1000000007));
	hashB = __latency_fn_scheduler_api_stable_hash_mix(hashB, value, static_cast<int_t<> >(137), static_cast<int_t<> >(1000000009));
}

}
