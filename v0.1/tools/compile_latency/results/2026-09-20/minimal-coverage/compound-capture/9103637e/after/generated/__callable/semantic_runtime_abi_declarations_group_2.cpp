#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiHelperDeclarationRow.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_row_by_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_rows.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_row_by_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_status_declared_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_row_by_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_status_declared_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_to_string_declared.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_concat_assign_declared.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_echo_eval_declared.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_compare_declared.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_compare_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiHelperDeclarationRow> __latency_fn_semantic_runtime_abi_declarations_row_by_helper_id(int_t<std::uint16_t> helperId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::row_by_helper_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[26]);
	auto __latency_local_0 = __latency_fn_semantic_runtime_abi_declarations_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->helper_id), cast<int_t<>>(helperId)))) {
			return row;
		}
	}
	shared_p<SemanticRuntimeAbiHelperDeclarationRow> empty = create<SemanticRuntimeAbiHelperDeclarationRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id(int_t<std::uint16_t> helperId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::helper_declared_by_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[27]);
	shared_p<SemanticRuntimeAbiHelperDeclarationRow> row = __latency_fn_semantic_runtime_abi_declarations_row_by_helper_id(cast<int_t<std::uint16_t>>(helperId));
	return ((cast<int_t<>>(row->helper_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->stable_contract_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_declarations_status_declared_id())));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id(int_t<std::uint16_t> helperId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::generator_allows_helper_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[28]);
	shared_p<SemanticRuntimeAbiHelperDeclarationRow> row = __latency_fn_semantic_runtime_abi_declarations_row_by_helper_id(cast<int_t<std::uint16_t>>(helperId));
	return ((cast<int_t<>>(row->helper_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->generator_allowed_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_declarations_status_declared_id())));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_to_string_declared() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::to_string_declared", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[29]);
	return (__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id(__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id()) && __latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id(__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id()));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_concat_assign_declared() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::concat_assign_declared", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[30]);
	return (__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id(__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id()) && __latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id(__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id()));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_echo_eval_declared() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::echo_eval_declared", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[31]);
	return (__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id(__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id()) && __latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id(__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id()));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_semantic_runtime_abi_declarations_compare_declared() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_declarations::compare_declared", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_declarations.phs", __latency_lines_semantic_runtime_abi_declarations[32]);
	return (__latency_fn_semantic_runtime_abi_declarations_helper_declared_by_id(__latency_fn_semantic_runtime_abi_declarations_helper_compare_id()) && __latency_fn_semantic_runtime_abi_declarations_generator_allows_helper_id(__latency_fn_semantic_runtime_abi_declarations_helper_compare_id()));
}

}
