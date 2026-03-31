#include "scpp/value_t.hpp"
#include "scpp/table_t.hpp"
#include "scpp/cast.hpp"
#include "scpp/memory.hpp"

#include <sstream>
#include <utility>

namespace scpp {
namespace {

[[nodiscard]] const char *kind_name(const value_t::kind_t kind) noexcept {
	switch (kind) {
		case value_t::kind_t::null_v: return "null";
		case value_t::kind_t::bool_v: return "bool";
		case value_t::kind_t::int_v: return "int";
		case value_t::kind_t::float_v: return "float";
		case value_t::kind_t::string_v: return "string";
		case value_t::kind_t::table_v: return "table";
		case value_t::kind_t::shared_table_v: return "shared_table";
		case value_t::kind_t::weak_table_v: return "weak_table";
	}
	return "unknown";
}

[[nodiscard]] std::runtime_error runtime_error_unary(const char *operation, const value_t &value) {
	std::ostringstream out;
	out << "value_t runtime error: operation '" << operation << "' is not supported for kind '" << kind_name(value.kind()) << "'";
	return std::runtime_error(out.str());
}

[[nodiscard]] std::runtime_error runtime_error_binary(const char *operation, const value_t &left, const value_t &right) {
	std::ostringstream out;
	out << "value_t runtime error: operation '" << operation << "' is not supported for kinds '"
		<< kind_name(left.kind()) << "' and '" << kind_name(right.kind()) << "'";
	return std::runtime_error(out.str());
}

[[nodiscard]] bool_t compare_shared_to_weak_identity(const shared_p<table_t<value_t>> &shared_value, const weak_p<table_t<value_t>> &weak_value) {
	auto locked = weak_value.lock();
	return locked ? bool_t(shared_value == locked) : bool_t(false);
}

[[nodiscard]] bool_t compare_weak_to_weak_identity(const weak_p<table_t<value_t>> &left, const weak_p<table_t<value_t>> &right) {
	auto left_locked = left.lock();
	auto right_locked = right.lock();
	return (left_locked && right_locked) ? bool_t(left_locked == right_locked) : bool_t(false);
}

[[nodiscard]] bool_t compare_with_null_eq(const value_t &value) {
	switch (value.kind()) {
		case value_t::kind_t::null_v: return bool_t(true);
		case value_t::kind_t::string_v: return cast<string_t>(null_t{}) == *value.string_if();
		case value_t::kind_t::table_v: return bool_t(value.table_if() == nullptr);
		case value_t::kind_t::shared_table_v: return *value.shared_table_if() == null_t{};
		case value_t::kind_t::weak_table_v: return *value.weak_table_if() == null_t{};
		default: throw runtime_error_binary("==", value_t{null_t{}}, value);
	}
}

} // namespace

value_t::value_t() noexcept : type_(kind_t::null_v) {}
value_t::value_t(null_t) noexcept : type_(kind_t::null_v) {}
value_t::value_t(nullopt_t) noexcept : type_(kind_t::null_v) {}
value_t::value_t(nullptr_t) noexcept : type_(kind_t::null_v) {}
value_t::value_t(const bool_t &value) noexcept : type_(kind_t::bool_v), bool_value_(value) {}
value_t::value_t(const int_t &value) noexcept : type_(kind_t::int_v), int_value_(value) {}
value_t::value_t(const float_t &value) noexcept : type_(kind_t::float_v), float_value_(value) {}
value_t::value_t(const string_t &value) : type_(kind_t::string_v) { new (&string_value_) unique_p<string_t>(unique<string_t>(value)); }
value_t::value_t(const char *value) : value_t(string_t{value}) {}
value_t::value_t(bool value) noexcept : type_(kind_t::bool_v), bool_value_(bool_t{value}) {}
value_t::value_t(std::int64_t value) noexcept : type_(kind_t::int_v), int_value_(int_t{value}) {}
value_t::value_t(double value) noexcept : type_(kind_t::float_v), float_value_(float_t{value}) {}
value_t::value_t(unique_p<table_t<value_t>> value) noexcept : type_(kind_t::table_v) { new (&table_value_) unique_p<table_t<value_t>>(std::move(value)); }
value_t::value_t(shared_p<table_t<value_t>> value) noexcept : type_(kind_t::shared_table_v) { new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(value)); }
value_t::value_t(weak_p<table_t<value_t>> value) noexcept : type_(kind_t::weak_table_v) { new (&weak_table_value_) weak_p<table_t<value_t>>(std::move(value)); }
value_t::value_t(std::unique_ptr<table_t<value_t>> value) noexcept : value_t(unique_p<table_t<value_t>>(std::move(value))) {}
value_t::value_t(std::shared_ptr<table_t<value_t>> value) noexcept : value_t(shared_p<table_t<value_t>>(std::move(value))) {}
value_t::value_t(std::weak_ptr<table_t<value_t>> value) noexcept : value_t(weak_p<table_t<value_t>>(std::move(value))) {}
value_t::~value_t() { destroy(); }
value_t::value_t(const value_t &other) : type_(kind_t::null_v) { *this = other.clone(); }
value_t::value_t(value_t &&other) noexcept : type_(kind_t::null_v) { move_construct(std::move(other)); }

value_t &value_t::operator=(const value_t &other) {
	if (this == &other) return *this;
	destroy();
	*this = other.clone();
	return *this;
}

value_t &value_t::operator=(value_t &&other) noexcept {
	if (this == &other) return *this;
	destroy();
	move_construct(std::move(other));
	return *this;
}

value_t &value_t::operator=(null_t) noexcept { destroy(); type_ = kind_t::null_v; return *this; }
value_t &value_t::operator=(const bool_t &value) noexcept { destroy(); type_ = kind_t::bool_v; new (&bool_value_) bool_t(value); return *this; }
value_t &value_t::operator=(const int_t &value) noexcept { destroy(); type_ = kind_t::int_v; new (&int_value_) int_t(value); return *this; }
value_t &value_t::operator=(const float_t &value) noexcept { destroy(); type_ = kind_t::float_v; new (&float_value_) float_t(value); return *this; }
value_t &value_t::operator=(const string_t &value) { destroy(); type_ = kind_t::string_v; new (&string_value_) unique_p<string_t>(unique<string_t>(value)); return *this; }
value_t &value_t::operator=(unique_p<table_t<value_t>> value) noexcept { destroy(); type_ = kind_t::table_v; new (&table_value_) unique_p<table_t<value_t>>(std::move(value)); return *this; }
value_t &value_t::operator=(shared_p<table_t<value_t>> value) noexcept { destroy(); type_ = kind_t::shared_table_v; new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(value)); return *this; }

value_t value_t::clone() const {
	switch (type_) {
		case kind_t::null_v: return value_t{null_t{}};
		case kind_t::bool_v: return value_t{bool_value_};
		case kind_t::int_v: return value_t{int_value_};
		case kind_t::float_v: return value_t{float_value_};
		case kind_t::string_v: return value_t{*string_value_};
		case kind_t::table_v: return value_t{unique<table_t<value_t>>(*table_value_)};
		case kind_t::shared_table_v: return value_t{shared_table_value_};
		case kind_t::weak_table_v: return value_t{weak_table_value_};
		default: return value_t{null_t{}};
	}
}

value_t::kind_t value_t::kind() const noexcept { return type_; }
bool_t value_t::is_null() const noexcept { return bool_t{type_ == kind_t::null_v}; }

string_t *value_t::string_if() noexcept { return (type_ == kind_t::string_v) ? string_value_.get() : nullptr; }
const string_t *value_t::string_if() const noexcept { return (type_ == kind_t::string_v) ? string_value_.get() : nullptr; }

table_t<value_t> *value_t::table_if() noexcept {
	if (type_ == kind_t::table_v) return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	return nullptr;
}
const table_t<value_t> *value_t::table_if() const noexcept {
	if (type_ == kind_t::table_v) return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	return nullptr;
}

string_t &value_t::as_string_ref() {
	if (type_ == kind_t::string_v) return *string_value_;
	if (type_ == kind_t::null_v) { *this = string_t{}; return *string_value_; }
	throw runtime_error_unary("as_string_ref", *this);
}

value_t::operator string_t&() { return as_string_ref(); }

value_t& value_t::operator[](const int_t& key) {
	if (type_ == kind_t::null_v) *this = unique<table_t<value_t>>();
	if (auto* t = table_if()) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}

const value_t& value_t::operator[](const int_t& key) const {
	if (auto* t = table_if()) return (*t)[key];
	static const value_t null_val{null_t{}};
	return null_val;
}

value_t& value_t::operator[](const string_t& key) {
	if (type_ == kind_t::null_v) *this = unique<table_t<value_t>>();
	if (auto* t = table_if()) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}

const value_t& value_t::operator[](const string_t& key) const {
	if (auto* t = table_if()) return (*t)[key];
	static const value_t null_val{null_t{}};
	return null_val;
}

value_t& value_t::operator[](const value_t& key) {
	if (type_ == kind_t::null_v) *this = unique<table_t<value_t>>();
	if (key.kind() == kind_t::int_v) return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	throw runtime_error_unary("operator[]", *this);
}

const value_t& value_t::operator[](const value_t& key) const {
	if (key.kind() == kind_t::int_v) return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	static const value_t null_val{null_t{}};
	return null_val;
}

void value_t::append(const value_t& val) {
	if (type_ == kind_t::null_v) *this = unique<table_t<value_t>>();
	if (auto* t = table_if()) { t->append(val); return; }
	throw runtime_error_unary("append", *this);
}

bool_t value_t::isset(const value_t& key) const {
	return bool_t(!(*this)[key].is_null());
}

void value_t::destroy() noexcept {
	switch (type_) {
		case kind_t::string_v: string_value_.~unique_p<string_t>(); break;
		case kind_t::table_v: table_value_.~unique_p<table_t<value_t>>(); break;
		case kind_t::shared_table_v: shared_table_value_.~shared_p<table_t<value_t>>(); break;
		case kind_t::weak_table_v: weak_table_value_.~weak_p<table_t<value_t>>(); break;
		default: break;
	}
	type_ = kind_t::null_v;
}

void value_t::move_construct(value_t &&other) noexcept {
	type_ = other.type_;
	switch (other.type_) {
		case kind_t::string_v: new (&string_value_) unique_p<string_t>(std::move(other.string_value_)); break;
		case kind_t::table_v: new (&table_value_) unique_p<table_t<value_t>>(std::move(other.table_value_)); break;
		case kind_t::shared_table_v: new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(other.shared_table_value_)); break;
		case kind_t::weak_table_v: new (&weak_table_value_) weak_p<table_t<value_t>>(std::move(other.weak_table_value_)); break;
		case kind_t::int_v: int_value_ = other.int_value_; break;
		case kind_t::float_v: float_value_ = other.float_value_; break;
		case kind_t::bool_v: bool_value_ = other.bool_value_; break;
		default: break;
	}
	other.type_ = kind_t::null_v;
}

} // namespace scpp
