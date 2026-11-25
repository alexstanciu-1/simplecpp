#include <scpp/lang/php.hpp>
#include "__types/structure_row_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_compiler_builtin_min_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_compiler_builtin_max_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_runtime_generated_min_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_runtime_generated_max_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_project_generated_min_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_is_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_compiler_builtin_max_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_compiler_builtin_min_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_is_compiler_builtin_id.hpp"
namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t structure_row_ids::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == structure_row_ids::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_structure_row_ids_uint16_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::uint16_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[0]);
	return cast<int_t<std::uint16_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[1]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::int32_t> __latency_fn_structure_row_ids_int32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::int32_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[2]);
	return cast<int_t<std::int32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_structure_row_ids_uint64_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::uint64_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[3]);
	return cast<int_t<std::uint64_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_none_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::none_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[4]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_structure_row_ids_none_kind_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::none_kind_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_compiler_builtin_min_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::compiler_builtin_min_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[6]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_compiler_builtin_max_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::compiler_builtin_max_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[7]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(999));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_runtime_generated_min_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::runtime_generated_min_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[8]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1000));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_runtime_generated_max_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::runtime_generated_max_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[9]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(9999));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_project_generated_min_id() {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::project_generated_min_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[10]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(10000));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t __latency_fn_structure_row_ids_is_none_id(int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::is_none_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[11]);
	return bool_t(php::identical(cast<int_t<>>(rowId), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t __latency_fn_structure_row_ids_has_dense_id(int_t<std::uint32_t> rowId, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::has_dense_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[12]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(rowId));
	return ((id > static_cast<int_t<> >(0)) && (id <= rowCount));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<> __latency_fn_structure_row_ids_dense_index(int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::dense_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[13]);
	return (cast<int_t<>>(rowId) - static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_structure_row_ids_next_dense_id(int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::next_dense_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[14]);
	return __latency_fn_structure_row_ids_uint32_from_int((rowCount + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t __latency_fn_structure_row_ids_is_compiler_builtin_id(int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::is_compiler_builtin_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[15]);
	return ((cast<int_t<>>(rowId) >= cast<int_t<>>(__latency_fn_structure_row_ids_compiler_builtin_min_id())) && (cast<int_t<>>(rowId) <= cast<int_t<>>(__latency_fn_structure_row_ids_compiler_builtin_max_id())));
}

}
