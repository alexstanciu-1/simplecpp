#include "scpp/mixed_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/cast.hpp"
#include "scpp/memory.hpp"
#include "operators/conditional/condition_truthiness.hpp"
#include "operators/empty/empty.hpp"
#include "operators/isset/isset.hpp"

#include <sstream>
#include <cctype>
#include <cerrno>
#include <cmath>
#include <cstdlib>
#include <utility>

namespace scpp {
namespace {

[[nodiscard]] const char *kind_name(const mixed_t::kind_t kind) noexcept {
	switch (kind) {
		case mixed_t::kind_t::null_v:         return "null_t";
		case mixed_t::kind_t::bool_v:         return "bool_t";
		case mixed_t::kind_t::int_v:          return "int_t";
		case mixed_t::kind_t::float_v:        return "float_t";
		case mixed_t::kind_t::string_v:       return "string_t";
		case mixed_t::kind_t::table_v:        return "hash_t";
		case mixed_t::kind_t::shared_table_v: return "shared_hash_t";
		case mixed_t::kind_t::dynamic_v:      return "dynamic_t";
		case mixed_t::kind_t::weak_table_v:   return "weak_hash_t";
	}
	return "unknown";
}

[[nodiscard]] std::runtime_error exact_accessor_error(const char *accessor, const mixed_t &value, const char *target_type) {
	std::ostringstream out;
	out << "mixed_t::" << accessor << " failed: stored type is "
	    << kind_name(value.kind()) << " but requested " << target_type
	    << ". This extraction is not allowed in Prism++.";
	return std::runtime_error(out.str());
}

[[nodiscard]] std::runtime_error runtime_error_unary(const char *operation, const mixed_t &value) {
	std::ostringstream out;
	out << "scpp::mixed_t runtime error: unary operation '" << operation
	    << "' is not supported for operand kind '" << kind_name(value.kind()) << "'. "
	    << "Requirement: this operation needs a supported semantic family for that active mixed_t kind.";
	return std::runtime_error(out.str());
}

[[nodiscard]] std::runtime_error runtime_error_binary(const char *operation, const mixed_t &left, const mixed_t &right) {
	std::ostringstream out;
	out << "scpp::mixed_t runtime error: binary operation '" << operation
	    << "' is not supported for operand kinds '"
	    << kind_name(left.kind()) << "' and '" << kind_name(right.kind()) << "'. "
	    << "Requirement: this operator must be defined for the active mixed_t runtime-kind pair.";
	return std::runtime_error(out.str());
}

[[nodiscard]] bool_t compare_shared_to_weak_identity(
	const shared_p<hash_t<mixed_t>> &shared_value,
	const weak_p<hash_t<mixed_t>>   &weak_value
) {
	auto locked = weak_value.lock();
	if (!static_cast<bool>(locked)) return bool_t{false};
	return shared_value == locked;
}

[[nodiscard]] bool_t compare_weak_to_weak_identity(
	const weak_p<hash_t<mixed_t>> &left,
	const weak_p<hash_t<mixed_t>> &right
) {
	auto left_locked  = left.lock();
	auto right_locked = right.lock();
	if (!static_cast<bool>(left_locked) || !static_cast<bool>(right_locked)) return bool_t{false};
	return left_locked == right_locked;
}

[[nodiscard]] mixed_t &apply_compound_assignment(mixed_t &left, const mixed_t &right, const char *operation) {
	mixed_t result;
	if (std::string_view(operation) == "+=") {
		result = left + right;
	} else if (std::string_view(operation) == "-=") {
		result = left - right;
	} else if (std::string_view(operation) == "*=") {
		result = left * right;
	} else if (std::string_view(operation) == "/=") {
		result = left / right;
	} else if (std::string_view(operation) == "%=") {
		result = left % right;
	} else if (std::string_view(operation) == "&=") {
		result = left & right;
	} else if (std::string_view(operation) == "|=") {
		result = left | right;
	} else if (std::string_view(operation) == "^=") {
		result = left ^ right;
	} else if (std::string_view(operation) == "<<=") {
		result = left << right;
	} else if (std::string_view(operation) == ">>=") {
		result = left >> right;
	} else {
		throw std::runtime_error(
			"scpp::mixed_t runtime error: unknown compound-assignment operator. "
			"Requirement: use one of the supported operators +=, -=, *=, /=, %=, &=, |=, ^=, <<=, >>="
		);
	}
	left = std::move(result);
	return left;
}

[[nodiscard]] bool_t compare_with_null_eq(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return bool_t{true};
		case mixed_t::kind_t::bool_v:
			return bool_t(false) == value.bool_value();
		case mixed_t::kind_t::int_v:
			return bool_t(false) == cast<bool_t>(value.int_value());
		case mixed_t::kind_t::float_v:
			return bool_t(false) == cast<bool_t>(value.float_value());
		case mixed_t::kind_t::string_v:
			return cast<string_t>(null_t{}) == *value.string_if();
		case mixed_t::kind_t::table_v:
			return bool_t{value.table_if() == nullptr};
		case mixed_t::kind_t::shared_table_v:
			return *value.shared_table_if() == null_t{};
		case mixed_t::kind_t::dynamic_v:
			return *value.dynamic_if() == null_t{};
		case mixed_t::kind_t::weak_table_v:
			return *value.weak_table_if() == null_t{};
	}
	throw runtime_error_binary("==", mixed_t{null_t{}}, value);
}

[[noreturn]] void deep_compare_not_implemented() {
	throw std::runtime_error(
		"scpp::mixed_t runtime error: table-vs-table equality currently has no deep-compare implementation. "
		"Requirement: use identity-bearing table carriers where identity comparison is defined, or add explicit deep table comparison semantics."
	);
}

[[nodiscard]] bool parse_double_string_loose(const std::string &value, double &out) {
	const char *begin = value.c_str();
	char *parse_end = nullptr;
	errno = 0;
	const double parsed = std::strtod(begin, &parse_end);
	if (parse_end == begin) {
		return false;
	}
	while (*parse_end != '\0') {
		const unsigned char ch = static_cast<unsigned char>(*parse_end);
		if (std::isspace(ch) == 0) {
			return false;
		}
		++parse_end;
	}
	if (errno == ERANGE || !std::isfinite(parsed)) {
		return false;
	}
	out = parsed;
	return true;
}

[[nodiscard]] bool_t compare_string_to_bool_eq(const string_t &left, const bool_t &right) {
	const std::string value = left.native_value();
	const bool left_truthy = !(value.empty() || value == "0");
	return bool_t(left_truthy) == right;
}

[[nodiscard]] bool_t compare_numeric_to_string_eq(const double left, const string_t &right) {
	double right_numeric = 0.0;
	if (!parse_double_string_loose(right.native_value(), right_numeric)) {
		return bool_t(false);
	}
	return bool_t(left == right_numeric);
}

[[nodiscard]] bool_t compare_scalar_boolish_eq(const mixed_t &left, const mixed_t &right) {
	switch (left.kind()) {
		case mixed_t::kind_t::null_v:
			switch (right.kind()) {
				case mixed_t::kind_t::bool_v:
					return bool_t(false) == right.bool_value();
				case mixed_t::kind_t::int_v:
					return bool_t(false) == cast<bool_t>(right.int_value());
				case mixed_t::kind_t::float_v:
					return bool_t(false) == cast<bool_t>(right.float_value());
				case mixed_t::kind_t::string_v:
					return compare_string_to_bool_eq(*right.string_if(), bool_t(false));
				default:
					break;
			}
			break;
		case mixed_t::kind_t::bool_v:
			switch (right.kind()) {
				case mixed_t::kind_t::null_v:
					return left.bool_value() == bool_t(false);
				case mixed_t::kind_t::bool_v:
					return left.bool_value() == right.bool_value();
				case mixed_t::kind_t::int_v:
					return left.bool_value() == cast<bool_t>(right.int_value());
				case mixed_t::kind_t::float_v:
					return left.bool_value() == cast<bool_t>(right.float_value());
				case mixed_t::kind_t::string_v:
					return left.bool_value() == compare_string_to_bool_eq(*right.string_if(), bool_t(true));
				default:
					break;
			}
			break;
		case mixed_t::kind_t::int_v:
			switch (right.kind()) {
				case mixed_t::kind_t::null_v:
					return cast<bool_t>(left.int_value()) == bool_t(false);
				case mixed_t::kind_t::bool_v:
					return cast<bool_t>(left.int_value()) == right.bool_value();
				default:
					break;
			}
			break;
		case mixed_t::kind_t::float_v:
			switch (right.kind()) {
				case mixed_t::kind_t::null_v:
					return cast<bool_t>(left.float_value()) == bool_t(false);
				case mixed_t::kind_t::bool_v:
					return cast<bool_t>(left.float_value()) == right.bool_value();
				default:
					break;
			}
			break;
		case mixed_t::kind_t::string_v:
			switch (right.kind()) {
				case mixed_t::kind_t::null_v:
					return compare_string_to_bool_eq(*left.string_if(), bool_t(false));
				case mixed_t::kind_t::bool_v:
					return compare_string_to_bool_eq(*left.string_if(), right.bool_value());
				default:
					break;
			}
			break;
		default:
			break;
	}
	throw runtime_error_binary("==", left, right);
}

} // namespace


void mixed_t::assert_kind_change_allowed(const kind_t target_kind) const noexcept {
	if (type_ == target_kind) {
		return;
	}
	assert_not_borrowed("kind-change");
}

void mixed_t::assert_not_borrowed(const char *operation) const noexcept {
#ifndef NDEBUG
	assert(borrow_count_ == 0 && "mixed_t borrow violation: attempted kind-changing operation while a scalar_ref is alive");
#else
	(void) operation;
#endif
	(void) operation;
}

void mixed_t::acquire_scalar_borrow() const noexcept {
#ifndef NDEBUG
	assert(borrow_count_ != UINT16_MAX && "mixed_t borrow violation: borrow counter overflow");
	++borrow_count_;
#endif
}

void mixed_t::release_scalar_borrow() const noexcept {
#ifndef NDEBUG
	assert(borrow_count_ > 0 && "mixed_t borrow violation: release without matching borrow");
	--borrow_count_;
#endif
}

// ============================================================
// Constructors
// ============================================================

mixed_t::mixed_t() noexcept : type_(kind_t::null_v) {}
mixed_t::mixed_t(null_t)    noexcept : type_(kind_t::null_v) {}
mixed_t::mixed_t(nullopt_t) noexcept : type_(kind_t::null_v) {}
mixed_t::mixed_t(nullptr_t) noexcept : type_(kind_t::null_v) {}

mixed_t::mixed_t(const bool_t  &value) noexcept : type_(kind_t::bool_v),  bool_value_(value)  {}
mixed_t::mixed_t(const int_t   &value) noexcept : type_(kind_t::int_v),   int_value_(value)   {}
mixed_t::mixed_t(const float_t &value) noexcept : type_(kind_t::float_v), float_value_(value) {}

mixed_t::mixed_t(const string_t &value) : type_(kind_t::string_v) {
	new (&string_value_) unique_p<string_t>(unique<string_t>(value));
}
mixed_t::mixed_t(const char *value) : mixed_t(string_t{value}) {}

mixed_t::mixed_t(bool value)           noexcept : type_(kind_t::bool_v),  bool_value_(bool_t{value})    {}
mixed_t::mixed_t(std::int64_t value)   noexcept : type_(kind_t::int_v),   int_value_(int_t{value})      {}
mixed_t::mixed_t(double value)         noexcept : type_(kind_t::float_v), float_value_(float_t{value})  {}

mixed_t::mixed_t(unique_p<hash_t<mixed_t>> value) noexcept : type_(kind_t::table_v) {
	new (&table_value_) unique_p<hash_t<mixed_t>>(std::move(value));
}
mixed_t::mixed_t(shared_p<hash_t<mixed_t>> value) noexcept : type_(kind_t::shared_table_v) {
	new (&shared_table_value_) shared_p<hash_t<mixed_t>>(std::move(value));
}
mixed_t::mixed_t(dynamic_init_t value) noexcept : type_(kind_t::dynamic_v) {
	new (&dynamic_value_) dynamic_t(std::move(value.value));
}
mixed_t::mixed_t(weak_p<hash_t<mixed_t>> value) noexcept : type_(kind_t::weak_table_v) {
	new (&weak_table_value_) weak_p<hash_t<mixed_t>>(std::move(value));
}
mixed_t::mixed_t(std::unique_ptr<hash_t<mixed_t>> value) noexcept
	: mixed_t(unique_p<hash_t<mixed_t>>(std::move(value))) {}
mixed_t::mixed_t(std::shared_ptr<hash_t<mixed_t>> value) noexcept
	: mixed_t(shared_p<hash_t<mixed_t>>(std::move(value))) {}
mixed_t::mixed_t(std::weak_ptr<hash_t<mixed_t>> value) noexcept
	: mixed_t(weak_p<hash_t<mixed_t>>(std::move(value))) {}

mixed_t::~mixed_t() { destroy(); }

mixed_t::mixed_t(const mixed_t &other) : type_(kind_t::null_v) { *this = other.clone(); }
mixed_t::mixed_t(mixed_t &&other)      noexcept : type_(kind_t::null_v) { move_construct(std::move(other)); }

// ============================================================
// Assignment operators
// ============================================================

mixed_t &mixed_t::operator=(const mixed_t &other) {
	if (this == &other) return *this;
	assert_kind_change_allowed(other.kind());
	destroy();
	*this = other.clone();
	return *this;
}

mixed_t &mixed_t::operator=(mixed_t &&other) noexcept {
	if (this == &other) return *this;
	assert_kind_change_allowed(other.kind());
	other.assert_not_borrowed("move-source");
	destroy();
	move_construct(std::move(other));
	return *this;
}

mixed_t &mixed_t::operator=(null_t)    noexcept { assert_kind_change_allowed(kind_t::null_v); destroy(); type_ = kind_t::null_v; return *this; }
mixed_t &mixed_t::operator=(nullopt_t) noexcept { assert_kind_change_allowed(kind_t::null_v); destroy(); type_ = kind_t::null_v; return *this; }
mixed_t &mixed_t::operator=(nullptr_t) noexcept { assert_kind_change_allowed(kind_t::null_v); destroy(); type_ = kind_t::null_v; return *this; }

mixed_t &mixed_t::operator=(const bool_t &value) noexcept {
	if (type_ == kind_t::bool_v) { bool_value_ = value; return *this; }
	assert_kind_change_allowed(kind_t::bool_v);
	destroy(); type_ = kind_t::bool_v; new (&bool_value_) bool_t(value); return *this;
}
mixed_t &mixed_t::operator=(const int_t &value) noexcept {
	if (type_ == kind_t::int_v) { int_value_ = value; return *this; }
	assert_kind_change_allowed(kind_t::int_v);
	destroy(); type_ = kind_t::int_v; new (&int_value_) int_t(value); return *this;
}
mixed_t &mixed_t::operator=(const float_t &value) noexcept {
	if (type_ == kind_t::float_v) { float_value_ = value; return *this; }
	assert_kind_change_allowed(kind_t::float_v);
	destroy(); type_ = kind_t::float_v; new (&float_value_) float_t(value); return *this;
}
mixed_t &mixed_t::operator=(const string_t &value) {
	if (type_ == kind_t::string_v) {
		*string_value_ = value;
		return *this;
	}
	assert_kind_change_allowed(kind_t::string_v);
	destroy(); type_ = kind_t::string_v;
	new (&string_value_) unique_p<string_t>(unique<string_t>(value));
	return *this;
}
mixed_t &mixed_t::operator=(const char *value) { return (*this = string_t{value}); }
mixed_t &mixed_t::operator=(bool value)           noexcept { return (*this = bool_t{value});  }
mixed_t &mixed_t::operator=(std::int64_t value)   noexcept { return (*this = int_t{value});   }
mixed_t &mixed_t::operator=(double value)          noexcept { return (*this = float_t{value}); }

mixed_t &mixed_t::operator=(unique_p<hash_t<mixed_t>> value) noexcept {
	assert_kind_change_allowed(kind_t::table_v);
	destroy(); type_ = kind_t::table_v;
	new (&table_value_) unique_p<hash_t<mixed_t>>(std::move(value));
	return *this;
}
mixed_t &mixed_t::operator=(shared_p<hash_t<mixed_t>> value) noexcept {
	assert_kind_change_allowed(kind_t::shared_table_v);
	destroy(); type_ = kind_t::shared_table_v;
	new (&shared_table_value_) shared_p<hash_t<mixed_t>>(std::move(value));
	return *this;
}
mixed_t &mixed_t::operator=(weak_p<hash_t<mixed_t>> value) noexcept {
	assert_kind_change_allowed(kind_t::weak_table_v);
	destroy(); type_ = kind_t::weak_table_v;
	new (&weak_table_value_) weak_p<hash_t<mixed_t>>(std::move(value));
	return *this;
}
mixed_t &mixed_t::operator=(std::unique_ptr<hash_t<mixed_t>> value) noexcept {
	return (*this = unique_p<hash_t<mixed_t>>(std::move(value)));
}
mixed_t &mixed_t::operator=(std::shared_ptr<hash_t<mixed_t>> value) noexcept {
	return (*this = shared_p<hash_t<mixed_t>>(std::move(value)));
}
mixed_t &mixed_t::operator=(std::weak_ptr<hash_t<mixed_t>> value) noexcept {
	return (*this = weak_p<hash_t<mixed_t>>(std::move(value)));
}

// ============================================================
// clone
// ============================================================

mixed_t mixed_t::clone() const {
	switch (type_) {
		case kind_t::null_v:         return mixed_t{null_t{}};
		case kind_t::bool_v:         return mixed_t{bool_value_};
		case kind_t::int_v:          return mixed_t{int_value_};
		case kind_t::float_v:        return mixed_t{float_value_};
		case kind_t::string_v:       return mixed_t{*string_value_};
		case kind_t::table_v:        return mixed_t{unique<hash_t<mixed_t>>(*table_value_)};
		case kind_t::shared_table_v: return mixed_t{shared_table_value_};
		case kind_t::dynamic_v:      return mixed_t{dynamic_box(dynamic_value_)};
		case kind_t::weak_table_v:   return mixed_t{weak_table_value_};
	}
	return mixed_t{null_t{}};
}

// ============================================================
// Observers
// ============================================================

mixed_t::mixed_type mixed_t::type() const noexcept {
	switch (type_) {
		case kind_t::null_v:         return mixed_type::null_v;
		case kind_t::bool_v:         return mixed_type::bool_v;
		case kind_t::int_v:          return mixed_type::int_v;
		case kind_t::float_v:        return mixed_type::float_v;
		case kind_t::string_v:       return mixed_type::string_v;
		case kind_t::table_v:        return mixed_type::hash_v;
		case kind_t::shared_table_v: return mixed_type::shared_hash_v;
		case kind_t::dynamic_v:      return mixed_type::dynamic_v;
		case kind_t::weak_table_v:   return mixed_type::weak_hash_v;
	}
	return mixed_type::null_v;
}

mixed_t::kind_t mixed_t::kind() const noexcept { return type_; }
bool_t mixed_t::is_null() const noexcept { return bool_t{type_ == kind_t::null_v}; }
bool_t mixed_t::is_bool() const noexcept { return bool_t{type_ == kind_t::bool_v}; }
bool_t mixed_t::is_int() const noexcept { return bool_t{type_ == kind_t::int_v}; }
bool_t mixed_t::is_float() const noexcept { return bool_t{type_ == kind_t::float_v}; }
bool_t mixed_t::is_string() const noexcept { return bool_t{type_ == kind_t::string_v}; }
bool_t mixed_t::is_hash() const noexcept { return bool_t{type_ == kind_t::table_v || type_ == kind_t::shared_table_v || type_ == kind_t::dynamic_v}; }

const bool_t *mixed_t::try_get_bool() const noexcept { return type_ == kind_t::bool_v ? &bool_value_ : nullptr; }
bool_t *mixed_t::try_get_bool() noexcept { return type_ == kind_t::bool_v ? &bool_value_ : nullptr; }
const int_t *mixed_t::try_get_int() const noexcept { return type_ == kind_t::int_v ? &int_value_ : nullptr; }
int_t *mixed_t::try_get_int() noexcept { return type_ == kind_t::int_v ? &int_value_ : nullptr; }
const float_t *mixed_t::try_get_float() const noexcept { return type_ == kind_t::float_v ? &float_value_ : nullptr; }
float_t *mixed_t::try_get_float() noexcept { return type_ == kind_t::float_v ? &float_value_ : nullptr; }
const string_t *mixed_t::try_get_string() const noexcept { return string_if(); }
string_t *mixed_t::try_get_string() noexcept { return string_if(); }
const hash_t<mixed_t> *mixed_t::try_get_hash() const noexcept { return table_if(); }
hash_t<mixed_t> *mixed_t::try_get_hash() noexcept { return table_if(); }

bool_t mixed_t::get_bool() const {
	if (const auto *value = try_get_bool()) {
		return *value;
	}
	throw exact_accessor_error("get_bool", *this, "bool_t");
}

int_t mixed_t::get_int() const {
	if (const auto *value = try_get_int()) {
		return *value;
	}
	throw exact_accessor_error("get_int", *this, "int_t");
}

float_t mixed_t::get_float() const {
	if (const auto *value = try_get_float()) {
		return *value;
	}
	throw exact_accessor_error("get_float", *this, "float_t");
}

const string_t &mixed_t::get_string() const {
	if (const auto *value = try_get_string()) {
		return *value;
	}
	throw exact_accessor_error("get_string", *this, "string_t");
}

string_t &mixed_t::get_string() {
	if (auto *value = try_get_string()) {
		return *value;
	}
	throw exact_accessor_error("get_string", *this, "string_t");
}

const hash_t<mixed_t> &mixed_t::get_hash() const {
	if (const auto *value = try_get_hash()) {
		return *value;
	}
	throw exact_accessor_error("get_hash", *this, "hash_t");
}

hash_t<mixed_t> &mixed_t::get_hash() {
	if (auto *value = try_get_hash()) {
		return *value;
	}
	throw exact_accessor_error("get_hash", *this, "hash_t");
}

bool_t mixed_t::bool_value() const { return get_bool(); }
int_t mixed_t::int_value() const { return get_int(); }
float_t mixed_t::float_value() const { return get_float(); }

string_t *mixed_t::string_if() noexcept {
	return (type_ == kind_t::string_v) ? string_value_.get() : nullptr;
}
const string_t *mixed_t::string_if() const noexcept {
	return (type_ == kind_t::string_v) ? string_value_.get() : nullptr;
}

hash_t<mixed_t> *mixed_t::table_if() noexcept {
	if (type_ == kind_t::table_v)        return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	if (type_ == kind_t::dynamic_v)      return dynamic_value_.get();
	return nullptr;
}
const hash_t<mixed_t> *mixed_t::table_if() const noexcept {
	if (type_ == kind_t::table_v)        return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	if (type_ == kind_t::dynamic_v)      return dynamic_value_.get();
	return nullptr;
}

shared_p<hash_t<mixed_t>> *mixed_t::shared_table_if() noexcept {
	return (type_ == kind_t::shared_table_v) ? &shared_table_value_ : nullptr;
}
const shared_p<hash_t<mixed_t>> *mixed_t::shared_table_if() const noexcept {
	return (type_ == kind_t::shared_table_v) ? &shared_table_value_ : nullptr;
}
dynamic_t *mixed_t::dynamic_if() noexcept {
	return (type_ == kind_t::dynamic_v) ? &dynamic_value_ : nullptr;
}
const dynamic_t *mixed_t::dynamic_if() const noexcept {
	return (type_ == kind_t::dynamic_v) ? &dynamic_value_ : nullptr;
}

weak_p<hash_t<mixed_t>> *mixed_t::weak_table_if() noexcept {
	return (type_ == kind_t::weak_table_v) ? &weak_table_value_ : nullptr;
}
const weak_p<hash_t<mixed_t>> *mixed_t::weak_table_if() const noexcept {
	return (type_ == kind_t::weak_table_v) ? &weak_table_value_ : nullptr;
}

// ============================================================
// as_*_ref  — disabled legacy bridges in the current safe subset
// ============================================================

namespace {
[[noreturn]] void throw_disabled_native_ref_bridge(const char *name) {
	throw std::runtime_error(std::string("mixed_t::") + name + " is disabled in the current safe subset: native references to dynamic/interior storage are not supported");
}
}

int_t &mixed_t::as_int_ref() {
	throw_disabled_native_ref_bridge("as_int_ref");
}

float_t &mixed_t::as_float_ref() {
	throw_disabled_native_ref_bridge("as_float_ref");
}

bool_t &mixed_t::as_bool_ref() {
	throw_disabled_native_ref_bridge("as_bool_ref");
}

string_t &mixed_t::as_string_ref() {
	throw_disabled_native_ref_bridge("as_string_ref");
}

hash_t<mixed_t> &mixed_t::as_table_ref() {
	throw_disabled_native_ref_bridge("as_table_ref");
}

// ============================================================
// Conversion operators
// ============================================================
//
// These operators are intentionally kept in v1. They are not a statement that broad implicit
// mixed_t -> native conversion is the long-term runtime ideal. They exist because
// specs/dynamic_types.md sections 1.2 (Visible Intention) and 1.3 (Technical Compromises to
// Achieve Visible Intention in v1) currently take priority for user-visible behavior until the
// generator can reliably inject explicit cast<T>(...) bridges at all approved typed destinations.

mixed_t::operator bool_t()  const { return cast<bool_t>(*this);  }
mixed_t::operator int_t()   const { return cast<int_t>(*this);   }
mixed_t::operator float_t() const { return cast<float_t>(*this); }
mixed_t::operator string_t() const { return cast<string_t>(*this); }


mixed_t::operator bool() const { return cast<bool>(*this); }

mixed_t::operator shared_p<hash_t<mixed_t>>() const {
	return cast<shared_p<hash_t<mixed_t>>>(*this);
}
mixed_t::operator weak_p<hash_t<mixed_t>>() const {
	return cast<weak_p<hash_t<mixed_t>>>(*this);
}

// ============================================================
// Increment / Decrement
// ============================================================

mixed_t &mixed_t::operator++() {
	switch (type_) {
		case kind_t::int_v:   ++int_value_;   return *this;
		case kind_t::float_v: ++float_value_; return *this;
		default: throw runtime_error_unary("++", *this);
	}
}
mixed_t mixed_t::operator++(int) { mixed_t snap = clone(); ++(*this); return snap; }

mixed_t &mixed_t::operator--() {
	switch (type_) {
		case kind_t::int_v:   --int_value_;   return *this;
		case kind_t::float_v: --float_value_; return *this;
		default: throw runtime_error_unary("--", *this);
	}
}
mixed_t mixed_t::operator--(int) { mixed_t snap = clone(); --(*this); return snap; }

// ============================================================
// Compound assignment operators
// ============================================================
//
// Contract: compound assignment follows the derived rule
//
// 	lhs op= rhs  <=>  lhs = lhs op rhs
//
// for mixed_t. The binary operator establishes legality and result kind. Assignment back then
// stores the resulting mixed_t value. This keeps compound assignment behavior aligned with the
// corresponding binary operator instead of maintaining a second independent dispatch matrix.

mixed_t &mixed_t::operator+=(const mixed_t &right) { return apply_compound_assignment(*this, right, "+="); }
mixed_t &mixed_t::operator-=(const mixed_t &right) { return apply_compound_assignment(*this, right, "-="); }
mixed_t &mixed_t::operator*=(const mixed_t &right) { return apply_compound_assignment(*this, right, "*="); }
mixed_t &mixed_t::operator/=(const mixed_t &right) { return apply_compound_assignment(*this, right, "/="); }
mixed_t &mixed_t::operator%=(const mixed_t &right) { return apply_compound_assignment(*this, right, "%="); }
mixed_t &mixed_t::operator&=(const mixed_t &right) { return apply_compound_assignment(*this, right, "&="); }
mixed_t &mixed_t::operator|=(const mixed_t &right) { return apply_compound_assignment(*this, right, "|="); }
mixed_t &mixed_t::operator^=(const mixed_t &right) { return apply_compound_assignment(*this, right, "^="); }
mixed_t &mixed_t::operator<<=(const mixed_t &right) { return apply_compound_assignment(*this, right, "<<="); }
mixed_t &mixed_t::operator>>=(const mixed_t &right) { return apply_compound_assignment(*this, right, ">>="); }

// ============================================================
// Fat-variable operator[]   autovivifying table access
// ============================================================

// Helper: resolve table pointer for the three ownership variants.
// For mutable access, also autovivifies null -> unique table.
namespace {
void ensure_unique_table_storage(mixed_t &self) {
	if (self.kind() != mixed_t::kind_t::shared_table_v) {
		return;
	}
	auto *shared_value = self.shared_table_if();
	if (shared_value == nullptr) {
		return;
	}
	if (shared_value->use_count() <= 1) {
		return;
	}
	auto detached = shared_p<hash_t<mixed_t>>(std::make_shared<hash_t<mixed_t>>(**shared_value));
	self = std::move(detached);
}

hash_t<mixed_t> *resolve_table_mut(mixed_t &self) {
	if (self.kind() == mixed_t::kind_t::null_v) {
		self = shared<hash_t<mixed_t>>();
	}
	if (self.kind() == mixed_t::kind_t::dynamic_v) {
		auto *dynamic_value = self.dynamic_if();
		return dynamic_value == nullptr ? nullptr : dynamic_value->get();
	}
	ensure_unique_table_storage(self);
	return self.table_if();
}
const hash_t<mixed_t> *resolve_table_const(const mixed_t &self) noexcept {
	if (self.kind() == mixed_t::kind_t::weak_table_v) {
		// weak_table_if() returns pointer to the weak_p  we cannot dereference
		// without locking, and we cannot store a locked copy here.
		// Return nullptr so const operator[] safely returns the static null.
		return nullptr;
	}
	return self.table_if();
}
} // namespace

mixed_t &mixed_t::operator[](const int_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}
mixed_t &mixed_t::operator[](const string_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}
mixed_t &mixed_t::operator[](const mixed_t &key) {
	if (key.kind() == kind_t::int_v)    return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	throw runtime_error_unary("operator[]", *this);
}
mixed_t &mixed_t::operator[](const char *key) {
	return operator[](string_t{key});
}
mixed_t &mixed_t::operator[](int native_key) {
	return operator[](int_t{static_cast<std::int64_t>(native_key)});
}

const mixed_t &mixed_t::operator[](const int_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return (*t)[key];
	// weak_table: lock and look up
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return (*locked)[key];
	}
	static const mixed_t null_val{null_t{}};
	return null_val;
}
const mixed_t &mixed_t::operator[](const string_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return (*t)[key];
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return (*locked)[key];
	}
	static const mixed_t null_val{null_t{}};
	return null_val;
}
const mixed_t &mixed_t::operator[](const mixed_t &key) const {
	if (key.kind() == kind_t::int_v)    return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	static const mixed_t null_val{null_t{}};
	return null_val;
}
const mixed_t &mixed_t::operator[](const char *key) const {
	return operator[](string_t{key});
}
const mixed_t &mixed_t::operator[](int native_key) const {
	return operator[](int_t{static_cast<std::int64_t>(native_key)});
}

// ============================================================
// get (non-autovivifying read-by-value)
// ============================================================

mixed_t mixed_t::get(const int_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return t->_find_val(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->_find_val(key);
	}
	return mixed_t{null_t{}};
}
mixed_t mixed_t::get(const string_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return t->_find_val(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->_find_val(key);
	}
	return mixed_t{null_t{}};
}
mixed_t mixed_t::get(const mixed_t &key) const {
	if (key.kind() == kind_t::int_v) return get(key.int_value());
	if (key.kind() == kind_t::string_v) return get(*key.string_if());
	return mixed_t{null_t{}};
}
mixed_t mixed_t::get(const char *key) const {
	return get(string_t{key});
}
mixed_t mixed_t::get(int native_key) const {
	return get(int_t{static_cast<std::int64_t>(native_key)});
}

// ============================================================
// append
// ============================================================

void mixed_t::append(const mixed_t &val) {
	auto *t = resolve_table_mut(*this);
	if (t) { (void)t->append(val); return; }
	throw runtime_error_unary("append", *this);
}

bool mixed_t::remove(const int_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t != nullptr) {
		return t->remove(key);
	}
	throw runtime_error_unary("remove", *this);
}

bool mixed_t::remove(const string_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t != nullptr) {
		return t->remove(key);
	}
	throw runtime_error_unary("remove", *this);
}

bool mixed_t::remove(const mixed_t &key) {
	if (key.kind() == kind_t::int_v) {
		return remove(key.int_value());
	}
	if (key.kind() == kind_t::string_v) {
		return remove(*key.string_if());
	}
	throw runtime_error_unary("remove", *this);
}

int_t mixed_t::size() const {
	if (const auto *t = resolve_table_const(*this)) {
		return int_t{static_cast<std::int64_t>(t->size())};
	}
	throw runtime_error_unary("size", *this);
}

bool mixed_t::empty() const {
	return static_cast<bool>(::scpp::empty(*this));
}

mixed_t &mixed_t::at(const int_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t != nullptr) {
		return t->at(key);
	}
	throw runtime_error_unary("at", *this);
}

const mixed_t &mixed_t::at(const int_t &key) const {
	if (const auto *t = resolve_table_const(*this)) {
		return t->at(key);
	}
	throw runtime_error_unary("at", *this);
}

mixed_t &mixed_t::at(const string_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t != nullptr) {
		return t->at(key);
	}
	throw runtime_error_unary("at", *this);
}

const mixed_t &mixed_t::at(const string_t &key) const {
	if (const auto *t = resolve_table_const(*this)) {
		return t->at(key);
	}
	throw runtime_error_unary("at", *this);
}

// ============================================================
// isset
// ============================================================

bool_t mixed_t::isset(const mixed_t &key) const {
	return ::scpp::isset(*this, key);
}
bool_t mixed_t::isset(const int_t &key) const {
	return ::scpp::isset(*this, key);
}
bool_t mixed_t::isset(const string_t &key) const {
	return ::scpp::isset(*this, key);
}
bool_t mixed_t::isset(const char *key) const {
	return ::scpp::isset(*this, key);
}
bool_t mixed_t::isset(int native_key) const {
	return ::scpp::isset(*this, native_key);
}

// ============================================================
// Free (binary / unary) operators
// ============================================================

mixed_t operator+(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::int_v:   return mixed_t{+value.int_value_};
		case mixed_t::kind_t::float_v: return mixed_t{+value.float_value_};
		default: throw runtime_error_unary("+", value);
	}
}
mixed_t operator-(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::int_v:   return mixed_t{-value.int_value_};
		case mixed_t::kind_t::float_v: return mixed_t{-value.float_value_};
		default: throw runtime_error_unary("-", value);
	}
}
bool_t operator!(const mixed_t &value) {
	return bool_t(!static_cast<bool>(::scpp::condition_truthy(value)));
}
mixed_t operator~(const mixed_t &value) {
	if (value.kind() == mixed_t::kind_t::int_v) return mixed_t{~value.int_value_};
	throw runtime_error_unary("~", value);
}

mixed_t operator+(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ + right.int_value_};
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.int_value_ + right.float_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.float_value_ + right.int_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.float_value_ + right.float_value_};
	if (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::string_v)
		return mixed_t{*left.string_value_ + *right.string_value_};
	throw runtime_error_binary("+", left, right);
}
mixed_t operator-(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ - right.int_value_};
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.int_value_ - right.float_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.float_value_ - right.int_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.float_value_ - right.float_value_};
	throw runtime_error_binary("-", left, right);
}
mixed_t operator*(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ * right.int_value_};
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.int_value_ * right.float_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.float_value_ * right.int_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.float_value_ * right.float_value_};
	throw runtime_error_binary("*", left, right);
}
mixed_t operator/(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ / right.int_value_};
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.int_value_ / right.float_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.float_value_ / right.int_value_};
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return mixed_t{left.float_value_ / right.float_value_};
	throw runtime_error_binary("/", left, right);
}
mixed_t operator%(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ % right.int_value_};
	throw runtime_error_binary("%", left, right);
}
mixed_t operator&(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ & right.int_value_};
	throw runtime_error_binary("&", left, right);
}
mixed_t operator|(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ | right.int_value_};
	throw runtime_error_binary("|", left, right);
}
mixed_t operator^(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ ^ right.int_value_};
	throw runtime_error_binary("^", left, right);
}
mixed_t operator<<(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ << right.int_value_};
	throw runtime_error_binary("<<", left, right);
}
mixed_t operator>>(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::int_v)
		return mixed_t{left.int_value_ >> right.int_value_};
	throw runtime_error_binary(">>", left, right);
}

bool_t operator==(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::null_v || right.kind() == mixed_t::kind_t::null_v) {
		return compare_with_null_eq(left.kind() == mixed_t::kind_t::null_v ? right : left);
	}
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return left.int_value_ == right.int_value_;
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return left.int_value_ == right.float_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return left.float_value_ == right.int_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return left.float_value_ == right.float_value_;
	if (left.kind() == mixed_t::kind_t::bool_v  && right.kind() == mixed_t::kind_t::bool_v)
		return left.bool_value_ == right.bool_value_;
	if ((left.kind() == mixed_t::kind_t::bool_v && right.kind() == mixed_t::kind_t::int_v)
	 || (left.kind() == mixed_t::kind_t::bool_v && right.kind() == mixed_t::kind_t::float_v)
	 || (left.kind() == mixed_t::kind_t::bool_v && right.kind() == mixed_t::kind_t::string_v)
	 || (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::bool_v)
	 || (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::bool_v)
	 || (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::bool_v))
		return compare_scalar_boolish_eq(left, right);
	if (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::string_v)
		return *left.string_value_ == *right.string_value_;
	if (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::string_v)
		return compare_numeric_to_string_eq(static_cast<double>(left.int_value_.native_value()), *right.string_value_);
	if (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::int_v)
		return compare_numeric_to_string_eq(static_cast<double>(right.int_value_.native_value()), *left.string_value_);
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::string_v)
		return compare_numeric_to_string_eq(left.float_value_.native_value(), *right.string_value_);
	if (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::float_v)
		return compare_numeric_to_string_eq(right.float_value_.native_value(), *left.string_value_);
	if ((left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::bool_v)
	 || (left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::int_v)
	 || (left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::float_v)
	 || (left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::string_v)
	 || (left.kind() == mixed_t::kind_t::bool_v && right.kind() == mixed_t::kind_t::shared_table_v)
	 || (left.kind() == mixed_t::kind_t::int_v && right.kind() == mixed_t::kind_t::shared_table_v)
	 || (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::shared_table_v)
	 || (left.kind() == mixed_t::kind_t::string_v && right.kind() == mixed_t::kind_t::shared_table_v))
		return bool_t{false};
	if (left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::shared_table_v)
		return left.shared_table_value_ == right.shared_table_value_;
	if (left.kind() == mixed_t::kind_t::dynamic_v && right.kind() == mixed_t::kind_t::dynamic_v)
		return left.dynamic_value_ == right.dynamic_value_;
	if (left.kind() == mixed_t::kind_t::shared_table_v && right.kind() == mixed_t::kind_t::weak_table_v)
		return compare_shared_to_weak_identity(left.shared_table_value_, right.weak_table_value_);
	if (left.kind() == mixed_t::kind_t::weak_table_v && right.kind() == mixed_t::kind_t::shared_table_v)
		return compare_shared_to_weak_identity(right.shared_table_value_, left.weak_table_value_);
	if (left.kind() == mixed_t::kind_t::weak_table_v && right.kind() == mixed_t::kind_t::weak_table_v)
		return compare_weak_to_weak_identity(left.weak_table_value_, right.weak_table_value_);
	if (left.kind() == mixed_t::kind_t::table_v && right.kind() == mixed_t::kind_t::table_v)
		deep_compare_not_implemented();
	throw runtime_error_binary("==", left, right);
}
bool_t operator!=(const mixed_t &left, const mixed_t &right) {
	return bool_t{!static_cast<bool>(operator==(left, right))};
}
bool_t operator<(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return left.int_value_ < right.int_value_;
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return left.int_value_ < right.float_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return left.float_value_ < right.int_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return left.float_value_ < right.float_value_;
	throw runtime_error_binary("<", left, right);
}
bool_t operator<=(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return left.int_value_ <= right.int_value_;
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return left.int_value_ <= right.float_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return left.float_value_ <= right.int_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return left.float_value_ <= right.float_value_;
	throw runtime_error_binary("<=", left, right);
}
bool_t operator>(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return left.int_value_ > right.int_value_;
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return left.int_value_ > right.float_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return left.float_value_ > right.int_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return left.float_value_ > right.float_value_;
	throw runtime_error_binary(">", left, right);
}
bool_t operator>=(const mixed_t &left, const mixed_t &right) {
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::int_v)
		return left.int_value_ >= right.int_value_;
	if (left.kind() == mixed_t::kind_t::int_v   && right.kind() == mixed_t::kind_t::float_v)
		return left.int_value_ >= right.float_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::int_v)
		return left.float_value_ >= right.int_value_;
	if (left.kind() == mixed_t::kind_t::float_v && right.kind() == mixed_t::kind_t::float_v)
		return left.float_value_ >= right.float_value_;
	throw runtime_error_binary(">=", left, right);
}
bool_t operator&&(const mixed_t &left, const mixed_t &right) {
	return bool_t(static_cast<bool>(::scpp::condition_truthy(left)) && static_cast<bool>(::scpp::condition_truthy(right)));
}
bool_t operator||(const mixed_t &left, const mixed_t &right) {
	return bool_t(static_cast<bool>(::scpp::condition_truthy(left)) || static_cast<bool>(::scpp::condition_truthy(right)));
}

// ============================================================
// Internal helpers
// ============================================================

void mixed_t::destroy() noexcept {
	assert_not_borrowed("destroy");
	switch (type_) {
		case kind_t::bool_v:         bool_value_.~bool_t();                            break;
		case kind_t::int_v:          int_value_.~int_t();                              break;
		case kind_t::float_v:        float_value_.~float_t();                          break;
		case kind_t::string_v:       string_value_.~unique_p<string_t>();              break;
		case kind_t::table_v:        table_value_.~unique_p<hash_t<mixed_t>>();       break;
		case kind_t::shared_table_v: shared_table_value_.~shared_p<hash_t<mixed_t>>(); break;
		case kind_t::dynamic_v:      dynamic_value_.~dynamic_t();                      break;
		case kind_t::weak_table_v:   weak_table_value_.~weak_p<hash_t<mixed_t>>();    break;
		case kind_t::null_v:         break;
	}
	type_ = kind_t::null_v;
}

void mixed_t::move_construct(mixed_t &&other) noexcept {
	other.assert_not_borrowed("move-construct-source");
	type_ = other.type_;
	switch (other.type_) {
		case kind_t::null_v:         break;
		case kind_t::bool_v:         bool_value_   = other.bool_value_;   break;
		case kind_t::int_v:          int_value_    = other.int_value_;    break;
		case kind_t::float_v:        float_value_  = other.float_value_;  break;
		case kind_t::string_v:
			new (&string_value_) unique_p<string_t>(std::move(other.string_value_)); break;
		case kind_t::table_v:
			new (&table_value_) unique_p<hash_t<mixed_t>>(std::move(other.table_value_)); break;
		case kind_t::shared_table_v:
			new (&shared_table_value_) shared_p<hash_t<mixed_t>>(std::move(other.shared_table_value_)); break;
		case kind_t::dynamic_v:
			new (&dynamic_value_) dynamic_t(std::move(other.dynamic_value_)); break;
		case kind_t::weak_table_v:
			new (&weak_table_value_) weak_p<hash_t<mixed_t>>(std::move(other.weak_table_value_)); break;
	}
	other.type_ = kind_t::null_v;
}


template <>
scalar_ref<int_t>::scalar_ref(mixed_t &value) : ptr_(nullptr), owner_(&value) {
	(void) value;
	throw std::runtime_error("scalar_ref<int_t>(mixed_t&) is disabled in the current safe subset");
}

template <>
scalar_ref<float_t>::scalar_ref(mixed_t &value) : ptr_(nullptr), owner_(&value) {
	(void) value;
	throw std::runtime_error("scalar_ref<float_t>(mixed_t&) is disabled in the current safe subset");
}

template <>
scalar_ref<bool_t>::scalar_ref(mixed_t &value) : ptr_(nullptr), owner_(&value) {
	(void) value;
	throw std::runtime_error("scalar_ref<bool_t>(mixed_t&) is disabled in the current safe subset");
}

template <>
scalar_ref<string_t>::scalar_ref(mixed_t &value) : ptr_(nullptr), owner_(&value) {
	(void) value;
	throw std::runtime_error("scalar_ref<string_t>(mixed_t&) is disabled in the current safe subset");
}

} // namespace scpp
