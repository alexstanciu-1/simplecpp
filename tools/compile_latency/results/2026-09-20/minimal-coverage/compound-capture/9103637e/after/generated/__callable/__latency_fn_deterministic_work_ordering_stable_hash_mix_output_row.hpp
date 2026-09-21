#include <scpp/lang/php.hpp>
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering__norm_stable_hash_mix_output_row__hashA.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering__norm_stable_hash_mix_output_row__hashB.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_output_row__exec.hpp"
#pragma once
namespace scpp {
struct DeterministicWorkOrderRow;
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_deterministic_work_ordering_stable_hash_mix_output_row(T_hashA&& _hashA, T_hashB&& _hashB, DeterministicWorkOrderRow row) {
	int_t<>& hashA = __latency_fn_deterministic_work_ordering__norm_stable_hash_mix_output_row__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_deterministic_work_ordering__norm_stable_hash_mix_output_row__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_deterministic_work_ordering_stable_hash_mix_output_row__exec(hashA, hashB, row);
	}

}
