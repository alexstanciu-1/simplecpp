#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_replacement_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_should_emit_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_has_slice_candidates.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_should_emit_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_from_work_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_parse_slice_kind_replacement_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_whitespace_byte.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_is_token_start_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_alpha_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_digit_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_identifier_byte.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_scan_token_end.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_is_token_start_byte.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_scan_token_end.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_token_count_before_offset.hpp"
#include "__callable/__latency_fn_resident_function_body_parse_slices_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
bool_t resident_function_body_parse_slices::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_parse_slices::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_parse_slices_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_parse_slices_parse_slice_kind_replacement_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::parse_slice_kind_replacement_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::parse_slice_kind_new_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_parse_slices_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_parse_slices_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[4]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_parse_slices_should_emit_slice(ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::should_emit_slice", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[5]);
	return (php::identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id())) || php::identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id())));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_parse_slices_has_slice_candidates(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::has_slice_candidates", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[6]);
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_resident_function_body_parse_slices_should_emit_slice(decision)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_parse_slices_parse_slice_kind_from_work_decision(ResidentFunctionBodyWorkDecisionRow decision) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::parse_slice_kind_from_work_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[7]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id())))) {
		return __latency_fn_resident_function_body_parse_slices_parse_slice_kind_new_body_id();
	}
	return __latency_fn_resident_function_body_parse_slices_parse_slice_kind_replacement_body_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_parse_slices_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[8]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_function_body_snapshots))))) {
		ResidentFunctionBodySnapshotRow snapshot = report->resident_function_body_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_parse_slices_is_token_start_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::is_token_start_byte", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[9]);
	return (!__latency_fn_phs_tokenizer_is_whitespace_byte(byte));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_parse_slices_scan_token_end(const string_t& source, int_t<> offset, int_t<> endOffset) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::scan_token_end", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[10]);
	if (static_cast<bool>(php::condition_truthy((offset >= endOffset)))) {
		return offset;
	}
	int_t<> byte = required_cast<int_t<>>(php::string_byte_at(source, offset));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_is_alpha_byte(byte)))) {
		int_t<> index = required_cast<int_t<>>((offset + static_cast<int_t<> >(1)));
		while (static_cast<bool>(((index < endOffset) && __latency_fn_phs_tokenizer_is_identifier_byte(php::string_byte_at(source, index))))) {
			index = (index + static_cast<int_t<> >(1));
		}
		return index;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_is_digit_byte(byte)))) {
		int_t<> index = required_cast<int_t<>>((offset + static_cast<int_t<> >(1)));
		while (static_cast<bool>(((index < endOffset) && __latency_fn_phs_tokenizer_is_digit_byte(php::string_byte_at(source, index))))) {
			index = (index + static_cast<int_t<> >(1));
		}
		return index;
	}
	return (offset + static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_parse_slices[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_parse_slices_token_count_before_offset(const string_t& source, int_t<> targetOffset) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_parse_slices::token_count_before_offset", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_parse_slices.phs", __latency_lines_resident_function_body_parse_slices[11]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(source));
	if (static_cast<bool>((targetOffset > length))) {
		targetOffset = length;
	}
	while (static_cast<bool>((offset < targetOffset))) {
		int_t<> byte = required_cast<int_t<>>(php::string_byte_at(source, offset));
		if (static_cast<bool>((!__latency_fn_resident_function_body_parse_slices_is_token_start_byte(byte)))) {
			offset = (offset + static_cast<int_t<> >(1));
		}
		else {
			count = (count + static_cast<int_t<> >(1));
			offset = __latency_fn_resident_function_body_parse_slices_scan_token_end(source, offset, targetOffset);
		}
	}
	return __latency_fn_resident_function_body_parse_slices_uint32_from_int(count);
}

}
