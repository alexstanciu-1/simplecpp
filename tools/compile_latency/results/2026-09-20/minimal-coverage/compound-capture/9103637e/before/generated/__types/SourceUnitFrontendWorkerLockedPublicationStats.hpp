#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SourceUnitFrontendWorkerLockedPublicationStats {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> published_results = static_cast<int_t<> >(0);
	int_t<> publish_batches = static_cast<int_t<> >(0);
	int_t<> largest_publish_batch = static_cast<int_t<> >(0);
	int_t<> locked_publish_elapsed_us = static_cast<int_t<> >(0);
	int_t<> frontend_publish_elapsed_us = static_cast<int_t<> >(0);
	int_t<> symbol_publish_elapsed_us = static_cast<int_t<> >(0);
	int_t<> tokenizer_worker_us = static_cast<int_t<> >(0);
	int_t<> parser_worker_us = static_cast<int_t<> >(0);
	int_t<> symbol_worker_us = static_cast<int_t<> >(0);
	int_t<> result_worker_us = static_cast<int_t<> >(0);
	int_t<> max_result_us = static_cast<int_t<> >(0);
	int_t<> max_result_parser_us = static_cast<int_t<> >(0);
	int_t<> max_result_source_bytes = static_cast<int_t<> >(0);
	int_t<> result_payload_copy_bytes = static_cast<int_t<> >(0);
};
}
