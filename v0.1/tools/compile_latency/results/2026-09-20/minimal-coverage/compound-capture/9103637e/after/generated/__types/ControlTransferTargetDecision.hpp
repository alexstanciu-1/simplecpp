#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ControlTransferTargetDecision {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	bool_t ready = bool_t(static_cast<bool_t>(false));
	int_t<std::uint32_t> resolved_target_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> diagnostic_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> cleanup_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
