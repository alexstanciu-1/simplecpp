#include "scpp/support/dynamic_value.hpp"
#include "scpp/hash_t.hpp"
#include <stdexcept>
#include <type_traits>

namespace scpp::detail {

dynamic_value::dynamic_value(null_t) noexcept {}
dynamic_value::dynamic_value(bool_t value) noexcept : payload_(value) {}
dynamic_value::dynamic_value(int_t<> value) noexcept : payload_(value) {}
dynamic_value::dynamic_value(float_t value) noexcept : payload_(value) {}
dynamic_value::dynamic_value(const string_t &value)
	: payload_(std::make_unique<string_t>(value)) {}
dynamic_value::dynamic_value(std::shared_ptr<table_type> table) noexcept
	: payload_(std::move(table)) {}
dynamic_value::~dynamic_value() = default;

dynamic_value::payload_type dynamic_value::copy_payload(const payload_type &source) {
	return std::visit([](const auto &value) -> payload_type {
		using value_type = std::decay_t<decltype(value)>;
		if constexpr (std::is_same_v<value_type, std::unique_ptr<string_t>>) {
			return std::make_unique<string_t>(*value);
		} else {
			return value;
		}
	}, source);
}

dynamic_value::dynamic_value(const dynamic_value &other)
	: payload_(copy_payload(other.payload_)) {}

dynamic_value::dynamic_value(dynamic_value &&other) noexcept
	: payload_(std::move(other.payload_)) {
	other.payload_.emplace<std::monostate>();
}

dynamic_value &dynamic_value::operator=(const dynamic_value &other) {
	if (this != &other) {
		// Finish potentially throwing string allocation before replacing the destination.
		dynamic_value copy(other);
		*this = std::move(copy);
	}
	return *this;
}

dynamic_value &dynamic_value::operator=(dynamic_value &&other) noexcept {
	static_assert(std::is_nothrow_move_assignable_v<payload_type>);
	static_assert(std::is_nothrow_move_constructible_v<payload_type>);
	if (this != &other) {
		payload_ = std::move(other.payload_);
		other.payload_.emplace<std::monostate>();
	}
	return *this;
}

dynamic_value dynamic_value::make_container() {
	return dynamic_value(std::make_shared<table_type>());
}

dynamic_value::kind_t dynamic_value::kind() const noexcept {
	return static_cast<kind_t>(payload_.index());
}

bool_t dynamic_value::is_null() const noexcept {
	return bool_t{kind() == kind_t::null_v};
}

bool_t dynamic_value::get_bool() const {
	if (const auto *value = std::get_if<bool_t>(&payload_)) return *value;
	throw std::runtime_error("dynamic: exact bool access requires bool payload");
}

int_t<> dynamic_value::get_int() const {
	if (const auto *value = std::get_if<int_t<>>(&payload_)) return *value;
	throw std::runtime_error("dynamic: exact int access requires int payload");
}

float_t dynamic_value::get_float() const {
	if (const auto *value = std::get_if<float_t>(&payload_)) return *value;
	throw std::runtime_error("dynamic: exact float access requires float payload");
}

const string_t &dynamic_value::get_string() const {
	if (const auto *value = std::get_if<std::unique_ptr<string_t>>(&payload_)) return **value;
	throw std::runtime_error("dynamic: exact string access requires string payload");
}

const dynamic_value::table_type *dynamic_value::table_if() const noexcept {
	if (const auto *value = std::get_if<std::shared_ptr<table_type>>(&payload_)) return value->get();
	return nullptr;
}

const dynamic_value::table_type &dynamic_value::get_table() const {
	if (const auto *value = table_if()) return *value;
	throw std::runtime_error("dynamic: container access requires container payload");
}

dynamic_value::table_type &dynamic_value::get_table() {
	return const_cast<table_type &>(std::as_const(*this).get_table());
}

} // namespace scpp::detail
