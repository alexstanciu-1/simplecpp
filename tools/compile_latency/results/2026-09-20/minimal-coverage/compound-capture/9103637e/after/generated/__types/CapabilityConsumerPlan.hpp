#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/CapabilityConsumerRow.hpp"
namespace scpp {
class CapabilityConsumerPlan {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	vector_t<int_t<std::uint16_t>> registry_capability_ids = vector_t<int_t<std::uint16_t>>{};
	vector_t<CapabilityConsumerRow> consumers = vector_t<CapabilityConsumerRow>{};
};
}
