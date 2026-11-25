#include <scpp/lang/php.hpp>
#include "__types/semantic_runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_schema_version.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lowering_strategy_runtime_call_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
bool_t semantic_runtime_abi_bridge::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == semantic_runtime_abi_bridge::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_schema_version() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::schema_version", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_lowering_strategy_runtime_call_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::lowering_strategy_runtime_call_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_opaque_runtime_string_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_primitive_i64_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_c_string_bytes_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_void_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_opaque_runtime_string_mut_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_opaque_runtime_vector_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::abi_carrier_primitive_i32_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::ownership_owned_return_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::ownership_borrowed_arguments_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::ownership_mutates_lhs_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::ownership_consumes_owned_argument_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::lifetime_explicit_cleanup_required_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::lifetime_caller_retains_arguments_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::lifetime_in_place_mutation_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}
