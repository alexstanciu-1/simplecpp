#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_partition_merge_reductions__norm_stable_hash_mix_row_value__hashA.hpp"
#include "__callable/__latency_fn_partition_merge_reductions__norm_stable_hash_mix_row_value__hashB.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_stable_hash_mix_row_value__exec.hpp"
#pragma once
namespace scpp {
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_partition_merge_reductions_stable_hash_mix_row_value(T_hashA&& _hashA, T_hashB&& _hashB, int_t<> value) {
	int_t<>& hashA = __latency_fn_partition_merge_reductions__norm_stable_hash_mix_row_value__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_partition_merge_reductions__norm_stable_hash_mix_row_value__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_partition_merge_reductions_stable_hash_mix_row_value__exec(hashA, hashB, value);
	}

}
