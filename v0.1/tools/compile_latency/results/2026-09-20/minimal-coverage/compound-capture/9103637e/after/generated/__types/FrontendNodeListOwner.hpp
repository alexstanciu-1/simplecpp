#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class FrontendNodeList;
class FrontendNodeListOwner {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint32_t> owner_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> current_list_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> current_generation_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> retained_old_generation_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> publish_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> cleanup_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	shared_p<FrontendNodeList> current_rows;
	shared_p<FrontendNodeList> retained_rows;
	FrontendNodeListOwner();
};
}
