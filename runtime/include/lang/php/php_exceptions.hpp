#pragma once

#include <exception>
#include <memory>
#include <stdexcept>
#include <string>

#include "lang/php/exception_payload.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

inline string_t exception_payload::message_string() const {
	string_t result;
	copy_message(&result, +[](void *target, const char *bytes, size_t length) {
		*static_cast<string_t *>(target) = string_t(std::string(bytes, length));
	});
	return result;
}

class throwable : public std::exception, public exception_payload {
public:
	throwable() = default;
	throwable(const throwable &) = default;
	throwable(throwable &&) noexcept = default;
	throwable &operator=(const throwable &) = default;
	throwable &operator=(throwable &&) noexcept = default;
	~throwable() override = default;

	[[nodiscard]] const char *exception_message() const noexcept override { return what(); }
	void copy_message(void *target, void (*write)(void *, const char *, size_t)) const override {
		const auto value = message_string();
		write(target, value.native_value().data(), value.native_value().size());
	}
	[[nodiscard]] virtual string_t message_string() const { return string_t("Throwable"); }
	[[nodiscard]] const char *what() const noexcept override {
		what_cache_ = message_string().native_value();
		return what_cache_.c_str();
	}

private:
	mutable std::string what_cache_;
};

class exception : public throwable {
public:
	exception() = default;
	explicit exception(string_t message) : message_(std::move(message)) {}

	[[nodiscard]] string_t message_string() const override { return message_; }

private:
	string_t message_ = string_t("Exception");
};

class thrown_object final : public std::exception {
public:
	explicit thrown_object(shared_p<exception_payload> value) noexcept
		: value_(std::move(value)) {}

	[[nodiscard]] const shared_p<exception_payload> &value() const noexcept { return value_; }
	[[nodiscard]] const char *what() const noexcept override {
		if (static_cast<bool>(value_)) {
			return value_->exception_message();
		}
		return "Prism++ throwable";
	}

private:
	shared_p<exception_payload> value_;
};

template <typename T>
[[nodiscard]] thrown_object make_thrown(shared_p<T> value)
	requires std::is_base_of_v<exception_payload, T>
{
	if (!static_cast<bool>(value)) {
		throw runtime_error("Cannot throw an absent Exception", "absent_exception", "exceptions", "throw");
	}
	return thrown_object(shared_p<exception_payload>(std::move(value)));
}

template <typename T>
[[nodiscard]] shared_p<T> catch_as(const thrown_object &thrown) noexcept
	requires std::is_base_of_v<exception_payload, T>
{
	if (!static_cast<bool>(thrown.value())) {
		return shared_p<T>(nullptr);
	}
	auto native = std::dynamic_pointer_cast<T>(thrown.value().native_value());
	if (!native) {
		return shared_p<T>(nullptr);
	}
	return shared_p<T>(std::move(native));
}

} // namespace scpp::php

namespace scpp {

using Throwable = ::scpp::php::throwable;

class Exception : public ::scpp::php::exception {
public:
	[[nodiscard]] static shared_p<Exception> make(const string_t &message) {
		return shared_p<Exception>(std::make_shared<Exception>(message));
	}

	Exception() = default;
	explicit Exception(string_t message)
		: ::scpp::php::exception(std::move(message)) {}
};

} // namespace scpp

namespace scpp::php {
// v0.2 names the template-free payload base; legacy Exception::make stays intact.
[[nodiscard]] inline shared_p<exception_payload> make_exception(const string_t &message) {
	return Exception::make(message);
}
} // namespace scpp::php
