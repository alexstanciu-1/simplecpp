#include <scpp/lang/php.hpp>
#include "__types/MtStageEvaluationArtifact.hpp"
#include "__types/MtStageEvaluationRow.hpp"
#include "__types/MtWorkerSegmentRow.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_segment_less.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_segment_less.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_segment_selected.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_segment_count_for_stage.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_value__exec.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_segment__exec.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_segment_count_for_stage.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_segment.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_hash_mix_value.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_published_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_first_published_segment.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stage_row_by_kind.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_read_only_input_enforced_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_simulated_stage_row.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_simulated_only_id.hpp"
namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
bool_t __latency_fn_mt_stage_evaluation_segment_less(MtWorkerSegmentRow left, MtWorkerSegmentRow right) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::segment_less", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[44]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(right->segment_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(left->merge_order_key, right->merge_order_key)))) {
		return bool_t((left->merge_order_key < right->merge_order_key));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->owner_run_id), cast<int_t<>>(right->owner_run_id))))) {
		return bool_t((cast<int_t<>>(left->owner_run_id) < cast<int_t<>>(right->owner_run_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))))) {
		return bool_t((cast<int_t<>>(left->source_unit_id) < cast<int_t<>>(right->source_unit_id)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id))))) {
		return bool_t((cast<int_t<>>(left->symbol_id) < cast<int_t<>>(right->symbol_id)));
	}
	return bool_t((cast<int_t<>>(left->segment_id) < cast<int_t<>>(right->segment_id)));
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtWorkerSegmentRow __latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key(shared_p<MtStageEvaluationArtifact> artifact, vector_t<int_t<std::uint32_t>>& selectedSegments, int_t<std::uint16_t> stageKindId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::next_segment_by_stage_and_merge_key", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[45]);
	bool_t found = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	MtWorkerSegmentRow best = MtWorkerSegmentRow{};
	auto __latency_local_0 = artifact->segments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto segment = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(segment->stage_kind_id), cast<int_t<>>(stageKindId)) && (!__latency_fn_mt_stage_evaluation_segment_selected(selectedSegments, segment->segment_id))))) {
			if (static_cast<bool>(((!found) || __latency_fn_mt_stage_evaluation_segment_less(segment, best)))) {
				best = segment;
				found = bool_t(static_cast<bool_t>(true));
			}
		}
	}
	return best;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
int_t<> __latency_fn_mt_stage_evaluation_segment_count_for_stage(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::segment_count_for_stage", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[46]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->segments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto segment = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(segment->stage_kind_id), cast<int_t<>>(stageKindId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return count;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
int_t<> __latency_fn_mt_stage_evaluation_stable_hash_mix(int_t<> hash, int_t<> value, int_t<> multiplier, int_t<> modulus) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::stable_hash_mix", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[47]);
	int_t<> normalized = required_cast<int_t<>>((value % modulus));
	return ((((hash * multiplier) + normalized) + static_cast<int_t<> >(17)) % modulus);
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
void __latency_fn_mt_stage_evaluation_stable_hash_mix_value__exec(int_t<>& hashA, int_t<>& hashB, int_t<> value) {
	hashA = __latency_fn_mt_stage_evaluation_stable_hash_mix(hashA, value, static_cast<int_t<> >(131), static_cast<int_t<> >(1000000007));
	hashB = __latency_fn_mt_stage_evaluation_stable_hash_mix(hashB, value, static_cast<int_t<> >(137), static_cast<int_t<> >(1000000009));
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
void __latency_fn_mt_stage_evaluation_stable_hash_mix_segment__exec(int_t<>& hashA, int_t<>& hashB, int_t<> selectedCount, MtWorkerSegmentRow segment) {
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, selectedCount);
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->source_unit_id));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->symbol_id));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->local_row_count));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->merge_order_key));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->published_row_first_id));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->published_row_count));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(segment->status_id));
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_mt_stage_evaluation_stable_published_hash(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::stable_published_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[48]);
	int_t<> hashA = required_cast<int_t<>>(static_cast<int_t<> >(146959811));
	int_t<> hashB = required_cast<int_t<>>(static_cast<int_t<> >(216613626));
	__latency_fn_mt_stage_evaluation_stable_hash_mix_value(hashA, hashB, cast<int_t<>>(stageKindId));
	vector_t<int_t<std::uint32_t>> selectedSegments = {};
	int_t<> selectedCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> targetCount = required_cast<int_t<>>(__latency_fn_mt_stage_evaluation_segment_count_for_stage(artifact, cast<int_t<std::uint16_t>>(stageKindId)));
	php::vector_reserve(selectedSegments, targetCount);
	while (static_cast<bool>((selectedCount < targetCount))) {
		MtWorkerSegmentRow segment = __latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key(artifact, selectedSegments, cast<int_t<std::uint16_t>>(stageKindId));
		if (static_cast<bool>(php::identical(cast<int_t<>>(segment->segment_id), static_cast<int_t<> >(0)))) {
			break;
		}
		{
		auto __latency_local_0 = segment->segment_id;
		(void) selectedSegments.push_back(__latency_local_0);
		}
		selectedCount = (selectedCount + static_cast<int_t<> >(1));
		__latency_fn_mt_stage_evaluation_stable_hash_mix_segment(hashA, hashB, selectedCount, segment);
	}
	int_t<> combined = required_cast<int_t<>>(((hashA * static_cast<int_t<> >(1000000009)) + hashB));
	if (static_cast<bool>(php::identical(combined, static_cast<int_t<> >(0)))) {
		combined = static_cast<int_t<> >(1);
	}
	return __latency_fn_structure_row_ids_uint64_from_int(combined);
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtWorkerSegmentRow __latency_fn_mt_stage_evaluation_first_published_segment(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::first_published_segment", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[49]);
	vector_t<int_t<std::uint32_t>> selectedSegments = {};
	return __latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key(artifact, selectedSegments, cast<int_t<std::uint16_t>>(stageKindId));
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_stage_row_by_kind(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::stage_row_by_kind", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[50]);
	auto __latency_local_0 = artifact->stages;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto stage = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(stage->stage_kind_id), cast<int_t<>>(stageKindId)))) {
			return stage;
		}
	}
	MtStageEvaluationRow empty = MtStageEvaluationRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_simulated_stage_row(int_t<std::uint32_t> rowId, int_t<std::uint16_t> stageKindId, int_t<std::uint16_t> parallelUnitId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> previousRowCount, int_t<std::uint32_t> localRowCount, int_t<std::uint32_t> publishedRowCount) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::simulated_stage_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[51]);
	MtStageEvaluationRow row = MtStageEvaluationRow{};
	row->row_id = rowId;
	row->owner_run_id = ownerRunId;
	row->input_snapshot_generation = ownerRunId;
	row->previous_row_count = previousRowCount;
	row->local_row_count = localRowCount;
	row->published_row_count = publishedRowCount;
	row->worker_compute_cost_units = localRowCount;
	row->published_output_hash = php::stable_hash_string_u64((string_t("mt_stage_simulated:v1:") + cast<string_t>(cast<int_t<>>(stageKindId)) + string_t(":") + cast<string_t>(cast<int_t<>>(publishedRowCount))));
	row->stage_kind_id = stageKindId;
	row->parallel_unit_id = parallelUnitId;
	row->read_only_input_status_id = __latency_fn_mt_stage_evaluation_read_only_input_enforced_id();
	row->publication_model_id = __latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id();
	row->status_id = __latency_fn_mt_stage_evaluation_status_simulated_only_id();
	row->blocked_reason_id = __latency_fn_mt_stage_evaluation_blocked_reason_none_id();
	return row;
}

}
