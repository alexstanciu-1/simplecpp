#include <scpp/lang/php.hpp>
#include "__types/SchedulerWorkerRow.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_worker__hashA.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_worker__hashB.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_worker__exec.hpp"
#pragma once
namespace scpp {
struct SchedulerWorkerRow;
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_scheduler_api_stable_hash_mix_worker(T_hashA&& _hashA, T_hashB&& _hashB, SchedulerWorkerRow worker) {
	int_t<>& hashA = __latency_fn_scheduler_api__norm_stable_hash_mix_worker__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_scheduler_api__norm_stable_hash_mix_worker__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_scheduler_api_stable_hash_mix_worker__exec(hashA, hashB, worker);
	}

}
