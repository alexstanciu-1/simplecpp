#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendRequestAuthorizationArtifact;
class LoweringPlan;
class EmissionLLVMCompositeTextSnapshot {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> mode_id = static_cast<int_t<> >(0);
	string_t target_llvm_function_name = string_t("");
	int_t<> target_source_unit_id = static_cast<int_t<> >(0);
	int_t<> target_symbol_id = static_cast<int_t<> >(0);
	int_t<> fixed_arg_right_literal_value = static_cast<int_t<> >(0);
	shared_p<BackendRequestAuthorizationArtifact> target_requests;
	shared_p<LoweringPlan> target_plan;
};
}
