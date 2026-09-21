#include <scpp/lang/php.hpp>
#include "__types/SchedulerTaskRow.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_task_api__hashA.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_task_api__hashB.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_task_api__exec.hpp"
#pragma once
namespace scpp {
struct SchedulerTaskRow;
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_scheduler_api_stable_hash_mix_task_api(T_hashA&& _hashA, T_hashB&& _hashB, SchedulerTaskRow task) {
	int_t<>& hashA = __latency_fn_scheduler_api__norm_stable_hash_mix_task_api__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_scheduler_api__norm_stable_hash_mix_task_api__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_scheduler_api_stable_hash_mix_task_api__exec(hashA, hashB, task);
	}

}
