#include <scpp/lang/php.hpp>
#include "__types/DeterministicWorkOrderArtifact.hpp"
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_task_output__exec.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_task_output.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_output_hash.hpp"
#include "__callable/__latency_fn_scheduler_api_tasks_by_output_order.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_output_rows_by_order.hpp"
#include "__callable/__latency_fn_scheduler_api_append_session.hpp"
#include "__callable/__latency_fn_scheduler_api_append_task.hpp"
#include "__callable/__latency_fn_scheduler_api_append_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_completed_task.hpp"
#include "__callable/__latency_fn_scheduler_api_finalize_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_new_one_thread_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_artifact_from_work_order.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_session.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_id_for_work.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_assignment_counts.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_id_for_work.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_output_rows_by_order.hpp"
#include "__callable/__latency_fn_scheduler_api_append_session.hpp"
#include "__callable/__latency_fn_scheduler_api_append_task.hpp"
#include "__callable/__latency_fn_scheduler_api_append_worker.hpp"
#include "__callable/__latency_fn_scheduler_api_completed_task.hpp"
#include "__callable/__latency_fn_scheduler_api_finalize_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_new_worker_shadow_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_assignment_counts.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_id_for_work.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_artifact_from_work_order.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_session.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_worker.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_session__exec.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_worker__exec.hpp"
namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
void __latency_fn_scheduler_api_stable_hash_mix_task_output__exec(int_t<>& hashA, int_t<>& hashB, SchedulerTaskRow task) {
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->output_order_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->work_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->execution_status_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->blocked_reason_id));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_scheduler_api_stable_output_hash(shared_p<SchedulerApiArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::stable_output_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[34]);
	int_t<> hashA = required_cast<int_t<>>(static_cast<int_t<> >(146959811));
	int_t<> hashB = required_cast<int_t<>>(static_cast<int_t<> >(216613626));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->task_count));
	vector_t<SchedulerTaskRow> orderedTasks = required_cast<vector_t<SchedulerTaskRow>>(__latency_fn_scheduler_api_tasks_by_output_order(artifact));
	auto& __latency_local_0 = orderedTasks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto task = __latency_local_1.value_copy();
		__latency_fn_scheduler_api_stable_hash_mix_task_output(hashA, hashB, task);
	}
	int_t<> combined = required_cast<int_t<>>(((hashA * static_cast<int_t<> >(1000000009)) + hashB));
	if (static_cast<bool>(php::identical(combined, static_cast<int_t<> >(0)))) {
		combined = static_cast<int_t<> >(1);
	}
	return __latency_fn_structure_row_ids_uint64_from_int(combined);
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_one_thread_artifact_from_work_order(shared_p<DeterministicWorkOrderArtifact> workOrder, int_t<std::uint32_t> generation) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::one_thread_artifact_from_work_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[35]);
	shared_p<SchedulerApiArtifact> artifact = __latency_fn_scheduler_api_new_one_thread_artifact(cast<int_t<>>(workOrder->output_row_count));
	int_t<std::uint32_t> sessionId = required_cast<int_t<std::uint32_t>>(__latency_fn_scheduler_api_append_session(artifact, __latency_fn_scheduler_api_one_thread_session(cast<int_t<std::uint32_t>>(generation), workOrder->output_row_count)));
	int_t<std::uint32_t> workerId = required_cast<int_t<std::uint32_t>>(__latency_fn_scheduler_api_append_worker(artifact, __latency_fn_scheduler_api_one_thread_worker(cast<int_t<std::uint32_t>>(sessionId), workOrder->output_row_count)));
	vector_t<DeterministicWorkOrderRow> orderedWorkRows = required_cast<vector_t<DeterministicWorkOrderRow>>(__latency_fn_deterministic_work_ordering_output_rows_by_order(workOrder));
	auto& __latency_local_0 = orderedWorkRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto work = __latency_local_1.value_copy();
		__latency_fn_scheduler_api_append_task(artifact, __latency_fn_scheduler_api_completed_task(cast<int_t<std::uint32_t>>(sessionId), cast<int_t<std::uint32_t>>(workerId), work));
	}
	__latency_fn_scheduler_api_finalize_artifact(artifact);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scheduler_api_worker_id_for_work(DeterministicWorkOrderRow work, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_id_for_work", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[36]);
	if (static_cast<bool>((workerCount <= static_cast<int_t<> >(1)))) {
		return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int((((cast<int_t<>>(work->partition_id) - static_cast<int_t<> >(1)) % workerCount) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
vector_t<int_t<std::uint32_t>> __latency_fn_scheduler_api_worker_assignment_counts(shared_p<DeterministicWorkOrderArtifact> workOrder, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_assignment_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[37]);
	vector_t<int_t<std::uint32_t>> counts = {};
	php::vector_reserve(counts, workerCount);
	while (static_cast<bool>((php::count(counts) < workerCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
		(void) counts.push_back(__latency_local_0);
		}
	}
	auto __latency_local_1 = workOrder->rows;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto work = __latency_local_2.value_copy();
		int_t<std::uint32_t> workerId = required_cast<int_t<std::uint32_t>>(__latency_fn_scheduler_api_worker_id_for_work(work, workerCount));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(workerId, workerCount)))) {
			int_t<> slot = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(workerId));
			counts.at(slot) = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(counts.at(slot)) + static_cast<int_t<> >(1)));
		}
	}
	return counts;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
shared_p<SchedulerApiArtifact> __latency_fn_scheduler_api_worker_shadow_artifact_from_work_order(shared_p<DeterministicWorkOrderArtifact> workOrder, int_t<std::uint32_t> generation, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::worker_shadow_artifact_from_work_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[38]);
	if (static_cast<bool>((workerCount <= static_cast<int_t<> >(0)))) {
		workerCount = static_cast<int_t<> >(1);
	}
	shared_p<SchedulerApiArtifact> artifact = __latency_fn_scheduler_api_new_worker_shadow_artifact(workerCount, cast<int_t<>>(workOrder->output_row_count));
	int_t<std::uint32_t> sessionId = required_cast<int_t<std::uint32_t>>(__latency_fn_scheduler_api_append_session(artifact, __latency_fn_scheduler_api_worker_shadow_session(cast<int_t<std::uint32_t>>(generation), __latency_fn_structure_row_ids_uint32_from_int(workerCount), workOrder->output_row_count)));
	vector_t<int_t<std::uint32_t>> workerAssignmentCounts = required_cast<vector_t<int_t<std::uint32_t>>>(__latency_fn_scheduler_api_worker_assignment_counts(workOrder, workerCount));
	int_t<> workerIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((workerIndex <= workerCount))) {
		__latency_fn_scheduler_api_append_worker(artifact, __latency_fn_scheduler_api_worker_shadow_worker(cast<int_t<std::uint32_t>>(sessionId), __latency_fn_structure_row_ids_uint32_from_int(workerIndex), cast<int_t<std::uint32_t>>(workerAssignmentCounts.at((workerIndex - static_cast<int_t<> >(1))))));
		workerIndex = (workerIndex + static_cast<int_t<> >(1));
	}
	vector_t<DeterministicWorkOrderRow> orderedWorkRows = required_cast<vector_t<DeterministicWorkOrderRow>>(__latency_fn_deterministic_work_ordering_output_rows_by_order(workOrder));
	auto& __latency_local_0 = orderedWorkRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto work = __latency_local_1.value_copy();
		__latency_fn_scheduler_api_append_task(artifact, __latency_fn_scheduler_api_completed_task(cast<int_t<std::uint32_t>>(sessionId), __latency_fn_scheduler_api_worker_id_for_work(work, workerCount), work));
	}
	__latency_fn_scheduler_api_finalize_artifact(artifact);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
void __latency_fn_scheduler_api_stable_hash_mix_session__exec(int_t<>& hashA, int_t<>& hashB, SchedulerSessionRow session) {
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(session->session_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(session->requested_worker_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(session->task_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(session->execution_status_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(session->blocked_reason_id));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
void __latency_fn_scheduler_api_stable_hash_mix_worker__exec(int_t<>& hashA, int_t<>& hashB, SchedulerWorkerRow worker) {
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->worker_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->session_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->worker_index));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->assigned_task_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->execution_status_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(worker->blocked_reason_id));
}

}
