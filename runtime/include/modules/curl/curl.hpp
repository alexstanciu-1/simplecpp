#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#ifndef SCPP_HAS_CURL
#define SCPP_HAS_CURL 0
#endif

namespace scpp::curl {

class response final {
public:
	int_t<> status_code = int_t<>(0);
	vector_t<string_t> headers;
	string_t body = string_t("");
	string_t effective_url = string_t("");
	string_t content_type = string_t("");
	int_t<> total_time_ms = int_t<>(0);
	int_t<> header_size = int_t<>(0);
	int_t<> request_size = int_t<>(0);
	int_t<> redirect_count = int_t<>(0);
};

class handle final {
public:
	string_t url = string_t("");
	bool_t returntransfer = bool_t(true);
	vector_t<string_t> httpheader;
	bool_t post = bool_t(false);
	string_t postfields = string_t("");
	string_t customrequest = string_t("");
	int_t<> timeout = int_t<>(0);
	int_t<> connecttimeout = int_t<>(0);
	bool_t followlocation = bool_t(false);
	string_t useragent = string_t("simplecpp-curl/1.0");
	bool_t ssl_verifypeer = bool_t(true);
	int_t<> ssl_verifyhost = int_t<>(2);
	int_t<> errno_code = int_t<>(0);
	string_t error = string_t("");
	bool_t closed = bool_t(false);
	shared_p<response> last_response = null;

	void reset_state();
};

#if SCPP_HAS_CURL
[[nodiscard]] result<shared_p<handle>> init();
[[nodiscard]] result<shared_p<handle>> init(const string_t &url);
[[nodiscard]] result<bool_t> setopt(const shared_p<handle> &resource, const int_t<> &option, const string_t &value);
[[nodiscard]] result<bool_t> setopt(const shared_p<handle> &resource, const int_t<> &option, const int_t<> &value);
[[nodiscard]] result<bool_t> setopt(const shared_p<handle> &resource, const int_t<> &option, const bool_t &value);
[[nodiscard]] result<bool_t> setopt(const shared_p<handle> &resource, const int_t<> &option, const vector_t<string_t> &value);
[[nodiscard]] result<shared_p<response>> exec(const shared_p<handle> &resource);
[[nodiscard]] result<mixed_t> getinfo(const shared_p<handle> &resource, const int_t<> &info);
[[nodiscard]] int_t<> errno_code(const shared_p<handle> &resource);
[[nodiscard]] string_t error_string(const shared_p<handle> &resource);
[[nodiscard]] result<bool_t> reset(const shared_p<handle> &resource);
[[nodiscard]] result<bool_t> close(const shared_p<handle> &resource);
[[nodiscard]] string_t strerror(const int_t<> &code);
#else
inline result<shared_p<handle>> init() {
	return error_t(string_t("curl_init(): curl runtime module is not enabled in this build"));
}

inline result<shared_p<handle>> init(const string_t &) {
	return error_t(string_t("curl_init(): curl runtime module is not enabled in this build"));
}

inline result<bool_t> setopt(const shared_p<handle> &, const int_t<> &, const string_t &) {
	return error_t(string_t("curl_setopt(): curl runtime module is not enabled in this build"));
}

inline result<bool_t> setopt(const shared_p<handle> &, const int_t<> &, const int_t<> &) {
	return error_t(string_t("curl_setopt(): curl runtime module is not enabled in this build"));
}

inline result<bool_t> setopt(const shared_p<handle> &, const int_t<> &, const bool_t &) {
	return error_t(string_t("curl_setopt(): curl runtime module is not enabled in this build"));
}

inline result<bool_t> setopt(const shared_p<handle> &, const int_t<> &, const vector_t<string_t> &) {
	return error_t(string_t("curl_setopt(): curl runtime module is not enabled in this build"));
}

inline result<shared_p<response>> exec(const shared_p<handle> &) {
	return error_t(string_t("curl_exec(): curl runtime module is not enabled in this build"));
}

inline result<mixed_t> getinfo(const shared_p<handle> &, const int_t<> &) {
	return error_t(string_t("curl_getinfo(): curl runtime module is not enabled in this build"));
}

inline int_t<> errno_code(const shared_p<handle> &) {
	return int_t<>(0);
}

inline string_t error_string(const shared_p<handle> &) {
	return string_t("");
}

inline result<bool_t> reset(const shared_p<handle> &) {
	return error_t(string_t("curl_reset(): curl runtime module is not enabled in this build"));
}

inline result<bool_t> close(const shared_p<handle> &) {
	return error_t(string_t("curl_close(): curl runtime module is not enabled in this build"));
}

inline string_t strerror(const int_t<> &) {
	return string_t("curl runtime module is not enabled in this build");
}
#endif

} // namespace scpp::curl

namespace scpp {

using curl_handle = curl::handle;
using curl_response = curl::response;

inline const int_t<> CURLOPT_URL{1};
inline const int_t<> CURLOPT_RETURNTRANSFER{2};
inline const int_t<> CURLOPT_HTTPHEADER{3};
inline const int_t<> CURLOPT_POST{4};
inline const int_t<> CURLOPT_POSTFIELDS{5};
inline const int_t<> CURLOPT_CUSTOMREQUEST{6};
inline const int_t<> CURLOPT_TIMEOUT{7};
inline const int_t<> CURLOPT_CONNECTTIMEOUT{8};
inline const int_t<> CURLOPT_FOLLOWLOCATION{9};
inline const int_t<> CURLOPT_USERAGENT{10};
inline const int_t<> CURLOPT_SSL_VERIFYPEER{11};
inline const int_t<> CURLOPT_SSL_VERIFYHOST{12};

inline const int_t<> CURLINFO_RESPONSE_CODE{101};
inline const int_t<> CURLINFO_EFFECTIVE_URL{102};
inline const int_t<> CURLINFO_CONTENT_TYPE{103};
inline const int_t<> CURLINFO_TOTAL_TIME_MS{104};
inline const int_t<> CURLINFO_HEADER_SIZE{105};
inline const int_t<> CURLINFO_REQUEST_SIZE{106};
inline const int_t<> CURLINFO_REDIRECT_COUNT{107};

} // namespace scpp
