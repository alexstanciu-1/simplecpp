#include <scpp/lang/php.hpp>
#include "__types/SchedulerSessionRow.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_session__hashA.hpp"
#include "__callable/__latency_fn_scheduler_api__norm_stable_hash_mix_session__hashB.hpp"
#include "__callable/__latency_fn_scheduler_api_stable_hash_mix_session__exec.hpp"
#pragma once
namespace scpp {
struct SchedulerSessionRow;
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_scheduler_api_stable_hash_mix_session(T_hashA&& _hashA, T_hashB&& _hashB, SchedulerSessionRow session) {
	int_t<>& hashA = __latency_fn_scheduler_api__norm_stable_hash_mix_session__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_scheduler_api__norm_stable_hash_mix_session__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_scheduler_api_stable_hash_mix_session__exec(hashA, hashB, session);
	}

}
