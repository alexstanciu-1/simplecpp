#include <scpp/lang/php.hpp>
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_task_api__exec.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_api_hash.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_session.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_task_api.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_worker.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_finalize_artifact.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_api_hash.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_output_hash.hpp"
namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
void __latency_fn_scheduler_api_stable_hash_mix_task_api__exec(int_t<>& hashA, int_t<>& hashB, SchedulerTaskRow task) {
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->task_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->session_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->worker_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->work_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->partition_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->output_order_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->execution_status_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(task->blocked_reason_id));
}

}

namespace scpp { extern const int __latency_lines_scheduler_api[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_scheduler_api_stable_api_hash(shared_p<SchedulerApiArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::stable_api_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[39]);
	int_t<> hashA = required_cast<int_t<>>(static_cast<int_t<> >(146959811));
	int_t<> hashB = required_cast<int_t<>>(static_cast<int_t<> >(216613626));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->artifact_kind_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->schema_version));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->source_model_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->scheduler_model_id));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->session_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->worker_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->task_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->design_ready_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->execution_blocked_count));
	__latency_fn_scheduler_api_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(artifact->execution_completed_count));
	auto __latency_local_0 = artifact->sessions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto session = __latency_local_1.value_copy();
		__latency_fn_scheduler_api_stable_hash_mix_session(hashA, hashB, session);
	}
	auto __latency_local_2 = artifact->workers;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto worker = __latency_local_3.value_copy();
		__latency_fn_scheduler_api_stable_hash_mix_worker(hashA, hashB, worker);
	}
	auto __latency_local_4 = artifact->tasks;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto task = __latency_local_5.value_copy();
		__latency_fn_scheduler_api_stable_hash_mix_task_api(hashA, hashB, task);
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
void __latency_fn_scheduler_api_finalize_artifact(shared_p<SchedulerApiArtifact>& artifact) {
	SCPP_CALL_DEPTH_GUARD("scheduler_api::finalize_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/scheduler_api.phs", __latency_lines_scheduler_api[40]);
	artifact->stable_output_hash = __latency_fn_scheduler_api_stable_output_hash(artifact);
	artifact->stable_api_hash = __latency_fn_scheduler_api_stable_api_hash(artifact);
}

}
