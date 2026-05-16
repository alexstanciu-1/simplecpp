#pragma once

#include "modules/curl/curl.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php::detail {

[[nodiscard]] inline mixed_t curl_info_array(const curl::response &reply) {
	auto info = unique<hash_t<mixed_t>>();
	info->set(string_t("http_code"), mixed_t(reply.status_code));
	info->set(string_t("response_code"), mixed_t(reply.status_code));
	info->set(string_t("effective_url"), mixed_t(reply.effective_url));
	info->set(string_t("content_type"), mixed_t(reply.content_type));
	info->set(string_t("total_time_ms"), mixed_t(reply.total_time_ms));
	info->set(string_t("header_size"), mixed_t(reply.header_size));
	info->set(string_t("request_size"), mixed_t(reply.request_size));
	info->set(string_t("redirect_count"), mixed_t(reply.redirect_count));
	return mixed_t(std::move(info));
}

} // namespace scpp::php::detail

namespace scpp::php {

[[nodiscard]] inline result_or_false<shared_p<curl::handle>> curl_init() {
	const auto created = scpp::curl::init();
	if (!created.has_value().native_value()) {
		return false_sentinel;
	}
	return created.value();
}

[[nodiscard]] inline result_or_false<shared_p<curl::handle>> curl_init(const string_t &url) {
	const auto created = scpp::curl::init(url);
	if (!created.has_value().native_value()) {
		return false_sentinel;
	}
	return created.value();
}

[[nodiscard]] inline bool_t curl_setopt(const shared_p<curl::handle> &resource, const int_t &option, const string_t &value) {
	return scpp::curl::setopt(resource, option, value).has_value();
}

[[nodiscard]] inline bool_t curl_setopt(const shared_p<curl::handle> &resource, const int_t &option, const int_t &value) {
	return scpp::curl::setopt(resource, option, value).has_value();
}

[[nodiscard]] inline bool_t curl_setopt(const shared_p<curl::handle> &resource, const int_t &option, const bool_t &value) {
	return scpp::curl::setopt(resource, option, value).has_value();
}

[[nodiscard]] inline bool_t curl_setopt(const shared_p<curl::handle> &resource, const int_t &option, const vector_t<string_t> &value) {
	return scpp::curl::setopt(resource, option, value).has_value();
}

[[nodiscard]] inline bool_t curl_setopt(const shared_p<curl::handle> &resource, const int_t &option, const mixed_t &value) {
	if (const auto *string_value = value.try_get_string(); string_value != nullptr) {
		return curl_setopt(resource, option, *string_value);
	}
	if (const auto *int_value = value.try_get_int(); int_value != nullptr) {
		return curl_setopt(resource, option, *int_value);
	}
	if (const auto *bool_value = value.try_get_bool(); bool_value != nullptr) {
		return curl_setopt(resource, option, *bool_value);
	}
	if (const auto *table = value.try_get_hash(); table != nullptr) {
		vector_t<string_t> items;
		for (std::size_t index = 0; index < table->size(); ++index) {
			items.append(static_cast<string_t>((*table)[static_cast<std::int64_t>(index)]));
		}
		return curl_setopt(resource, option, items);
	}
	return bool_t(false);
}

[[nodiscard]] inline result_or_false<string_t> curl_exec(const shared_p<curl::handle> &resource) {
	const auto executed = scpp::curl::exec(resource);
	if (!executed.has_value().native_value()) {
		return false_sentinel;
	}
	const auto &reply = *executed.value();
	return reply.body;
}

[[nodiscard]] inline mixed_t curl_getinfo(const shared_p<curl::handle> &resource) {
	if (static_cast<bool>(resource == null) || static_cast<bool>(resource->last_response == null)) {
		return mixed_t(bool_t(false));
	}
	return detail::curl_info_array(*resource->last_response);
}

[[nodiscard]] inline mixed_t curl_getinfo(const shared_p<curl::handle> &resource, const int_t &selector) {
	const auto info = scpp::curl::getinfo(resource, selector);
	if (!info.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return info.value();
}

[[nodiscard]] inline int_t curl_errno(const shared_p<curl::handle> &resource) {
	return scpp::curl::errno_code(resource);
}

[[nodiscard]] inline string_t curl_error(const shared_p<curl::handle> &resource) {
	return scpp::curl::error_string(resource);
}

[[nodiscard]] inline bool_t curl_reset(const shared_p<curl::handle> &resource) {
	return scpp::curl::reset(resource).has_value();
}

[[nodiscard]] inline bool_t curl_close(const shared_p<curl::handle> &resource) {
	return scpp::curl::close(resource).has_value();
}

[[nodiscard]] inline string_t curl_strerror(const int_t &code) {
	return scpp::curl::strerror(code);
}

} // namespace scpp::php
