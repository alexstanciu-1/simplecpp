#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ResidentProjectSnapshotRow.hpp"
#include "__types/resident_snapshots.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_current_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_no_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_no_change_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_reuse_unknown_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_reuse_blocked_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_mix_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_mix_hash.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_snapshots_change_no_previous_id.hpp"
#include "__callable/__latency_fn_resident_snapshots_from_compiler_project_run_row.hpp"
#include "__callable/__latency_fn_resident_snapshots_snapshot_hash.hpp"
#include "__callable/__latency_fn_resident_snapshots_status_current_id.hpp"
namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
bool_t resident_snapshots::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_snapshots::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_status_current_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::status_current_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_status_reuse_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::status_reuse_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_status_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::status_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_change_no_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::change_no_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_change_no_change_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::change_no_change_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_change_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::change_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_reuse_unknown_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::reuse_unknown_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_reuse_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::reuse_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_snapshots_reuse_blocked_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::reuse_blocked_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_snapshots_mix_hash(int_t<std::uint32_t> hash, int_t<std::uint32_t> value) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::mix_hash", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[9]);
	int_t<> mixed = required_cast<int_t<>>((((cast<int_t<>>(hash) * static_cast<int_t<> >(16777619)) + cast<int_t<>>(value)) % static_cast<int_t<> >(4294967295)));
	return __latency_fn_structure_row_ids_uint32_from_int(mixed);
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_snapshots_snapshot_hash(ResidentProjectSnapshotRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::snapshot_hash", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[10]);
	int_t<std::uint32_t> hash = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2166136261)));
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->source_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->source_byte_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->source_content_hash);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->token_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->frontend_node_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->frontend_declaration_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->symbol_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->reference_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->callable_contract_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->capability_readiness_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->backend_request_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->lowering_step_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->artifact_write_count);
	hash = __latency_fn_resident_snapshots_mix_hash(cast<int_t<std::uint32_t>>(hash), row->skipped_write_count);
	return cast<int_t<std::uint32_t>>(hash);
}

}

namespace scpp { extern const int __latency_lines_resident_snapshots[]; }
namespace scpp {
ResidentProjectSnapshotRow __latency_fn_resident_snapshots_from_compiler_project_run_row(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_snapshots::from_compiler_project_run_row", "/tmp/scpp-edit-latency-20260919/app/compile/support/resident_snapshots.phs", __latency_lines_resident_snapshots[11]);
	ResidentProjectSnapshotRow snapshot = ResidentProjectSnapshotRow{};
	snapshot->owner_run_id = row->run_id;
	snapshot->source_count = row->source_count;
	snapshot->source_byte_count = row->source_byte_count;
	snapshot->token_count = row->token_count;
	snapshot->frontend_node_count = row->frontend_node_count;
	snapshot->frontend_declaration_count = row->frontend_declaration_count;
	snapshot->symbol_count = row->symbol_count;
	snapshot->reference_count = row->reference_count;
	snapshot->callable_contract_count = row->callable_contract_count;
	snapshot->capability_readiness_count = row->capability_readiness_count;
	snapshot->backend_request_count = row->backend_request_count;
	snapshot->lowering_step_count = row->lowering_step_count;
	snapshot->artifact_write_count = row->artifact_write_count;
	snapshot->reused_write_count = row->reused_write_count;
	snapshot->skipped_write_count = row->skipped_write_count;
	snapshot->status_id = __latency_fn_resident_snapshots_status_current_id();
	snapshot->change_status_id = __latency_fn_resident_snapshots_change_no_previous_id();
	snapshot->snapshot_hash = __latency_fn_resident_snapshots_snapshot_hash(snapshot);
	return snapshot;
}

}
