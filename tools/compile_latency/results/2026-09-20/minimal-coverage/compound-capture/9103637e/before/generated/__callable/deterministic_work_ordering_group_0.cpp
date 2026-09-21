#include <scpp/lang/php.hpp>
#include "__types/DeterministicWorkOrderArtifact.hpp"
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__types/deterministic_work_ordering.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_artifact_kind_deterministic_work_order_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_source_model_logical_work_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_ordering_model_canonical_output_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_execution_model_sequential_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_execution_model_simulated_partitions_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_work_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_work_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_work_kind_backend_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_artifact_kind_deterministic_work_order_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_new_artifact.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_ordering_model_canonical_output_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_source_model_logical_work_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_row.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_status_ready_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_partition_seen.hpp"
namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
bool_t deterministic_work_ordering::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == deterministic_work_ordering::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_artifact_kind_deterministic_work_order_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::artifact_kind_deterministic_work_order_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_source_model_logical_work_list_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::source_model_logical_work_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_ordering_model_canonical_output_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::ordering_model_canonical_output_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_execution_model_sequential_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::execution_model_sequential_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_execution_model_simulated_partitions_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::execution_model_simulated_partitions_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_work_kind_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::work_kind_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_work_kind_symbol_body_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::work_kind_symbol_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_work_kind_backend_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::work_kind_backend_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_deterministic_work_ordering_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[10]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
shared_p<DeterministicWorkOrderArtifact> __latency_fn_deterministic_work_ordering_new_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[11]);
	shared_p<DeterministicWorkOrderArtifact> artifact = create<DeterministicWorkOrderArtifact>();
	artifact->artifact_kind_id = __latency_fn_deterministic_work_ordering_artifact_kind_deterministic_work_order_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_deterministic_work_ordering_source_model_logical_work_list_id();
	artifact->ordering_model_id = __latency_fn_deterministic_work_ordering_ordering_model_canonical_output_id();
	php::vector_reserve(artifact->rows, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
DeterministicWorkOrderRow __latency_fn_deterministic_work_ordering_row(int_t<std::uint32_t> workId, int_t<std::uint16_t> executionModelId, int_t<std::uint16_t> workKindId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> partitionId, int_t<std::uint32_t> partitionLocalOrderId, int_t<std::uint32_t> completionOrderId, int_t<std::uint32_t> outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[12]);
	DeterministicWorkOrderRow row = DeterministicWorkOrderRow{};
	row->work_id = workId;
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnitId;
	row->symbol_id = symbolId;
	row->partition_id = partitionId;
	row->partition_local_order_id = partitionLocalOrderId;
	row->completion_order_id = completionOrderId;
	row->output_order_id = outputOrderId;
	row->execution_model_id = executionModelId;
	row->work_kind_id = workKindId;
	row->status_id = __latency_fn_deterministic_work_ordering_status_ready_id();
	row->blocked_reason_id = __latency_fn_deterministic_work_ordering_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
bool_t __latency_fn_deterministic_work_ordering_partition_seen(shared_p<DeterministicWorkOrderArtifact> artifact, int_t<std::uint32_t> partitionId) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::partition_seen", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[13]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(existing->partition_id), cast<int_t<>>(partitionId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}
