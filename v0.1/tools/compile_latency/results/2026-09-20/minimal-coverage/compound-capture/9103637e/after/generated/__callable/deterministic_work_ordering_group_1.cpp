#include <scpp/lang/php.hpp>
#include "__types/DeterministicWorkOrderArtifact.hpp"
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_append_row.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_partition_seen.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_row_by_output_order.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_output_rows_by_order.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value__exec.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_output_row__exec.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_output_rows_by_order.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_output_row.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_output_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_finalize_artifact.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_stable_output_hash.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_first_stored_row.hpp"
namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
void __latency_fn_deterministic_work_ordering_append_row(shared_p<DeterministicWorkOrderArtifact>& artifact, DeterministicWorkOrderRow row) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[14]);
	bool_t partitionAlreadySeen = required_cast<bool_t>(__latency_fn_deterministic_work_ordering_partition_seen(artifact, row->partition_id));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		row->row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	}
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>((!partitionAlreadySeen))) {
		artifact->partition_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->partition_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_deterministic_work_ordering_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((cast<int_t<>>(row->output_order_id) > static_cast<int_t<> >(0)))) {
		artifact->output_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->output_row_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
DeterministicWorkOrderRow __latency_fn_deterministic_work_ordering_row_by_output_order(shared_p<DeterministicWorkOrderArtifact> artifact, int_t<std::uint32_t> outputOrderId) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::row_by_output_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[15]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->output_order_id), cast<int_t<>>(outputOrderId)))) {
			return row;
		}
	}
	DeterministicWorkOrderRow empty = DeterministicWorkOrderRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
vector_t<DeterministicWorkOrderRow> __latency_fn_deterministic_work_ordering_output_rows_by_order(shared_p<DeterministicWorkOrderArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::output_rows_by_order", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[16]);
	int_t<> rowCount = required_cast<int_t<>>(cast<int_t<>>(artifact->output_row_count));
	vector_t<DeterministicWorkOrderRow> rows = {};
	php::vector_reserve(rows, rowCount);
	DeterministicWorkOrderRow empty = DeterministicWorkOrderRow{};
	while (static_cast<bool>((php::count(rows) < rowCount))) {
		(void) rows.push_back(empty);
	}
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->output_order_id, rowCount)))) {
			rows.at(__latency_fn_structure_row_ids_dense_index(row->output_order_id)) = row;
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<> __latency_fn_deterministic_work_ordering_stable_hash_mix(int_t<> hash, int_t<> value, int_t<> multiplier, int_t<> modulus) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::stable_hash_mix", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[17]);
	int_t<> normalized = required_cast<int_t<>>((value % modulus));
	return ((((hash * multiplier) + normalized) + static_cast<int_t<> >(17)) % modulus);
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
void __latency_fn_deterministic_work_ordering_stable_hash_mix_row_value__exec(int_t<>& hashA, int_t<>& hashB, int_t<> value) {
	hashA = __latency_fn_deterministic_work_ordering_stable_hash_mix(hashA, value, static_cast<int_t<> >(131), static_cast<int_t<> >(1000000007));
	hashB = __latency_fn_deterministic_work_ordering_stable_hash_mix(hashB, value, static_cast<int_t<> >(137), static_cast<int_t<> >(1000000009));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
void __latency_fn_deterministic_work_ordering_stable_hash_mix_output_row__exec(int_t<>& hashA, int_t<>& hashB, DeterministicWorkOrderRow row) {
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->output_order_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->work_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->owner_run_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->source_unit_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->symbol_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->work_kind_id));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(row->status_id));
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_deterministic_work_ordering_stable_output_hash(shared_p<DeterministicWorkOrderArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::stable_output_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[18]);
	int_t<> hashA = required_cast<int_t<>>(static_cast<int_t<> >(146959811));
	int_t<> hashB = required_cast<int_t<>>(static_cast<int_t<> >(216613626));
	__latency_fn_deterministic_work_ordering_stable_hash_mix_row_value(hashA, hashB, cast<int_t<>>(artifact->output_row_count));
	vector_t<DeterministicWorkOrderRow> orderedRows = required_cast<vector_t<DeterministicWorkOrderRow>>(__latency_fn_deterministic_work_ordering_output_rows_by_order(artifact));
	auto& __latency_local_0 = orderedRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_deterministic_work_ordering_stable_hash_mix_output_row(hashA, hashB, row);
	}
	int_t<> combined = required_cast<int_t<>>(((hashA * static_cast<int_t<> >(1000000009)) + hashB));
	if (static_cast<bool>(php::identical(combined, static_cast<int_t<> >(0)))) {
		combined = static_cast<int_t<> >(1);
	}
	return __latency_fn_structure_row_ids_uint64_from_int(combined);
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
void __latency_fn_deterministic_work_ordering_finalize_artifact(shared_p<DeterministicWorkOrderArtifact>& artifact) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::finalize_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[19]);
	artifact->stable_output_hash = __latency_fn_deterministic_work_ordering_stable_output_hash(artifact);
}

}

namespace scpp { extern const int __latency_lines_deterministic_work_ordering[]; }
namespace scpp {
DeterministicWorkOrderRow __latency_fn_deterministic_work_ordering_first_stored_row(shared_p<DeterministicWorkOrderArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("deterministic_work_ordering::first_stored_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/deterministic_work_ordering.phs", __latency_lines_deterministic_work_ordering[20]);
	if (static_cast<bool>((php::count(artifact->rows) > static_cast<int_t<> >(0)))) {
		return artifact->rows[static_cast<int_t<> >(0)];
	}
	DeterministicWorkOrderRow empty = DeterministicWorkOrderRow{};
	return empty;
}

}
