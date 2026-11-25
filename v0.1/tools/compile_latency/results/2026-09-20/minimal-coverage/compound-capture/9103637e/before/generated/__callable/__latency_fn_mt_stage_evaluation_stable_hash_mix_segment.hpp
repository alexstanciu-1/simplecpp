#include <scpp/lang/php.hpp>
#include "__types/MtWorkerSegmentRow.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation__norm_stable_hash_mix_segment__hashA.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation__norm_stable_hash_mix_segment__hashB.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_segment__exec.hpp"
#pragma once
namespace scpp {
struct MtWorkerSegmentRow;
	template <typename T_hashA, typename T_hashB>
	void __latency_fn_mt_stage_evaluation_stable_hash_mix_segment(T_hashA&& _hashA, T_hashB&& _hashB, int_t<> selectedCount, MtWorkerSegmentRow segment) {
	int_t<>& hashA = __latency_fn_mt_stage_evaluation__norm_stable_hash_mix_segment__hashA(std::forward<T_hashA>(_hashA));
	int_t<>& hashB = __latency_fn_mt_stage_evaluation__norm_stable_hash_mix_segment__hashB(std::forward<T_hashB>(_hashB));
		__latency_fn_mt_stage_evaluation_stable_hash_mix_segment__exec(hashA, hashB, selectedCount, segment);
	}

}
