#pragma once

#include <exception>
#include <memory>
#include <stdexcept>
#include <string>

#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

class throwable : public std::exception {
public:
	throwable() = default;
	throwable(const throwable &) = default;
	throwable(throwable &&) noexcept = default;
	throwable &operator=(const throwable &) = default;
	throwable &operator=(throwable &&) noexcept = default;
	~throwable() override = default;

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
	explicit thrown_object(shared_p<throwable> value) noexcept
		: value_(std::move(value)) {}

	[[nodiscard]] const shared_p<throwable> &value() const noexcept { return value_; }
	[[nodiscard]] const char *what() const noexcept override {
		if (static_cast<bool>(value_)) {
			return value_->what();
		}
		return "Prism++ throwable";
	}

private:
	shared_p<throwable> value_;
};

template <typename T>
[[nodiscard]] thrown_object make_thrown(shared_p<T> value)
	requires std::is_base_of_v<throwable, T>
{
	return thrown_object(shared_p<throwable>(std::move(value)));
}

template <typename T>
[[nodiscard]] shared_p<T> catch_as(const thrown_object &thrown) noexcept
	requires std::is_base_of_v<throwable, T>
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
	Exception() = default;
	explicit Exception(string_t message)
		: ::scpp::php::exception(std::move(message)) {}
};

} // namespace scpp
