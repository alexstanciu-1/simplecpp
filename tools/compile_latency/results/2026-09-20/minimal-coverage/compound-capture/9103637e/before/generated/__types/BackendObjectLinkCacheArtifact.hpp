#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/BackendLinkCacheDecisionRow.hpp"
#include "__types/BackendObjectCacheDecisionRow.hpp"
namespace scpp {
class BackendObjectLinkCacheArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> cache_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> object_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_compile_deferred_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_reuse_deferred_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> object_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> link_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> link_relink_deferred_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> link_reuse_deferred_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> link_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<BackendObjectCacheDecisionRow> objects = vector_t<BackendObjectCacheDecisionRow>{};
	vector_t<BackendLinkCacheDecisionRow> links = vector_t<BackendLinkCacheDecisionRow>{};
};
}
