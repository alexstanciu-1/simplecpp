#include <scpp/lang/php.hpp>
#include "__types/type_capability_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_artifact_kind_capability_coverage_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_binary_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_unary_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_string_concat_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_artifact_kind_condition_truthiness_policy_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policy_strict_bool_id.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t type_capability_readiness::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == type_capability_readiness::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<> __latency_fn_type_capability_readiness_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[0]);
	return static_cast<int_t<> >(1000000007);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<> __latency_fn_type_capability_readiness_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[1]);
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % __latency_fn_type_capability_readiness_semantic_hash_modulus()));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		return (mixed + __latency_fn_type_capability_readiness_semantic_hash_modulus());
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_artifact_kind_capability_coverage_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::artifact_kind_capability_coverage_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_type_ref_known_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_type_ref_known_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_return_lowering_authorized_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_numeric_binary_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_scalar_local_storage_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_scalar_local_storage_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_scalar_echo_output_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_scalar_echo_output_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_control_flow_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_control_flow_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_scalar_comparison_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_bool_logical_operator_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_bool_logical_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_numeric_unary_operator_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_numeric_unary_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_string_concat_operator_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_string_concat_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_scalar_call_boundary_storage_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_artifact_kind_condition_truthiness_policy_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::artifact_kind_condition_truthiness_policy_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_condition_truthiness_policy_strict_bool_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::condition_truthiness_policy_strict_bool_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}
