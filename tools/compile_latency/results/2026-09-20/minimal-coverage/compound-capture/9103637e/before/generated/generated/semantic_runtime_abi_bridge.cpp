#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/SemanticRuntimeAbiBridgeFamilyRow.hpp"
namespace scpp {
	using namespace ::scpp;
bool_t SemanticRuntimeAbiBridgeDescriptorRow::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SemanticRuntimeAbiBridgeDescriptorRow::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t SemanticRuntimeAbiBridgeFamilyRow::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SemanticRuntimeAbiBridgeFamilyRow::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}
