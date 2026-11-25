#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/SourceUnitFrontendWorkerBuildResult.hpp"
#include "__types/SourceUnitFrontendWorkerFrontendPayloadCarrier.hpp"
#include "__types/SourceUnitFrontendWorkerLockedPublicationStats.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/TokenStream.hpp"
#include "__types/source_units.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f696e6372656d656e74616c2f7265736964656e745f736f757263655f756e69745f66726f6e74656e645f7061796c6f61645f7461626c6573[];
bool_t SourceUnitFrontendWorkerPayloadInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceUnitFrontendWorkerPayloadInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

SourceUnitFrontendWorkerPayloadInput::SourceUnitFrontendWorkerPayloadInput() {
	SCPP_CALL_DEPTH_GUARD("SourceUnitFrontendWorkerPayloadInput::__construct", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_partition_lines_636f6d70696c652f696e6372656d656e74616c2f7265736964656e745f736f757263655f756e69745f66726f6e74656e645f7061796c6f61645f7461626c6573[0]);
	this->source_units = create<SourceUnitTable>();
}

bool_t SourceUnitFrontendWorkerFrontendPayloadCarrier::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceUnitFrontendWorkerFrontendPayloadCarrier::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t SourceUnitFrontendWorkerBuildResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceUnitFrontendWorkerBuildResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

SourceUnitFrontendWorkerBuildResult::SourceUnitFrontendWorkerBuildResult() {
	SCPP_CALL_DEPTH_GUARD("SourceUnitFrontendWorkerBuildResult::__construct", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_partition_lines_636f6d70696c652f696e6372656d656e74616c2f7265736964656e745f736f757263655f756e69745f66726f6e74656e645f7061796c6f61645f7461626c6573[1]);
	this->frontend_carrier = create<SourceUnitFrontendWorkerFrontendPayloadCarrier>();
	this->tokens = create<TokenStream>();
	this->model = create<FrontendModel>();
	this->symbols = create<ProjectSymbolIndex>();
}

bool_t SourceUnitFrontendWorkerLockedPublicationStats::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceUnitFrontendWorkerLockedPublicationStats::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}
