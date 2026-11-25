#include <scpp/lang/php.hpp>
#include "__types/ScalarConditionOperand.hpp"
#include "__types/ScalarIfBranchOutcome.hpp"
#include "__types/ScalarLoopBodyStatementSequence.hpp"
namespace scpp {
	using namespace ::scpp;
bool_t ScalarLoopBodyStatementSequence::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ScalarLoopBodyStatementSequence::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t ScalarIfBranchOutcome::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ScalarIfBranchOutcome::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t ScalarConditionOperand::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ScalarConditionOperand::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}
