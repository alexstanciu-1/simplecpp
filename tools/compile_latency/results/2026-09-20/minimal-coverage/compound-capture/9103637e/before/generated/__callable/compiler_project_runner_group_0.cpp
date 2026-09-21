#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/compiler_project_runner.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_layer_artifact_writes_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_frontend_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_symbol_index_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_backend_text_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_missing_config_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_source_load_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_frontend_parse_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_entry_symbol_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_backend_text_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_artifact_write_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_empty_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_layer_artifact_writes_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_empty_row.hpp"
namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
bool_t compiler_project_runner::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_project_runner::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_completed_status_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::completed_status_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_status_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_status_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_completed_layer_artifact_writes_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::completed_layer_artifact_writes_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_layer_frontend_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_layer_frontend_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_layer_symbol_index_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_layer_symbol_index_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_layer_backend_text_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_layer_backend_text_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_layer_artifact_writes_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_missing_config_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_missing_config_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_source_load_failed_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_source_load_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_frontend_parse_failed_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_frontend_parse_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_entry_symbol_missing_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_entry_symbol_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_backend_text_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_backend_text_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_compiler_project_runner_blocked_reason_artifact_write_failed_id() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_reason_artifact_write_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
CompilerProjectRunRow __latency_fn_compiler_project_runner_empty_row() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::empty_row", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[13]);
	CompilerProjectRunRow empty = CompilerProjectRunRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
CompilerProjectRunRow __latency_fn_compiler_project_runner_completed_row(int_t<std::uint32_t> runId, const string_t& runLabel) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::completed_row", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[14]);
	CompilerProjectRunRow row = __latency_fn_compiler_project_runner_empty_row();
	row->run_id = runId;
	row->run_label_id = runId;
	row->status_id = __latency_fn_compiler_project_runner_completed_status_id();
	row->completed_layer_id = __latency_fn_compiler_project_runner_completed_layer_artifact_writes_id();
	return row;
}

}
