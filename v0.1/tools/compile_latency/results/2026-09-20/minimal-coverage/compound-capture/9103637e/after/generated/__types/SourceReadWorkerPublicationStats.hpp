#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SourceReadWorkerPublicationStats {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> publish_batches = static_cast<int_t<> >(0);
	int_t<> largest_publish_batch = static_cast<int_t<> >(0);
	int_t<> published_results = static_cast<int_t<> >(0);
};
}
