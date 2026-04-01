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
		case value_t::kind_t::null_v:         return "null";
		case value_t::kind_t::bool_v:         return "bool";
		case value_t::kind_t::int_v:          return "int";
		case value_t::kind_t::float_v:        return "float";
		case value_t::kind_t::string_v:       return "string";
		case value_t::kind_t::table_v:        return "table";
		case value_t::kind_t::shared_table_v: return "shared_table";
		case value_t::kind_t::weak_table_v:   return "weak_table";
	}
	return "unknown";
}

[[nodiscard]] std::runtime_error runtime_error_unary(const char *operation, const value_t &value) {
	std::ostringstream out;
	out << "value_t runtime error: operation '" << operation
	    << "' is not supported for kind '" << kind_name(value.kind()) << "'";
	return std::runtime_error(out.str());
}

[[nodiscard]] std::runtime_error runtime_error_binary(const char *operation, const value_t &left, const value_t &right) {
	std::ostringstream out;
	out << "value_t runtime error: operation '" << operation
	    << "' is not supported for kinds '"
	    << kind_name(left.kind()) << "' and '" << kind_name(right.kind()) << "'";
	return std::runtime_error(out.str());
}

[[nodiscard]] bool_t compare_shared_to_weak_identity(
	const shared_p<table_t<value_t>> &shared_value,
	const weak_p<table_t<value_t>>   &weak_value
) {
	auto locked = weak_value.lock();
	if (!static_cast<bool>(locked)) return bool_t{false};
	return shared_value == locked;
}

[[nodiscard]] bool_t compare_weak_to_weak_identity(
	const weak_p<table_t<value_t>> &left,
	const weak_p<table_t<value_t>> &right
) {
	auto left_locked  = left.lock();
	auto right_locked = right.lock();
	if (!static_cast<bool>(left_locked) || !static_cast<bool>(right_locked)) return bool_t{false};
	return left_locked == right_locked;
}

[[nodiscard]] bool_t compare_with_null_eq(const value_t &value) {
	switch (value.kind()) {
		case value_t::kind_t::null_v:
			return bool_t{true};
		case value_t::kind_t::string_v:
			return cast<string_t>(null_t{}) == *value.string_if();
		case value_t::kind_t::table_v:
			return bool_t{value.table_if() == nullptr};
		case value_t::kind_t::shared_table_v:
			return *value.shared_table_if() == null_t{};
		case value_t::kind_t::weak_table_v:
			return *value.weak_table_if() == null_t{};
		case value_t::kind_t::bool_v:
		case value_t::kind_t::int_v:
		case value_t::kind_t::float_v:
			throw runtime_error_binary("==", value_t{null_t{}}, value);
	}
	throw runtime_error_binary("==", value_t{null_t{}}, value);
}

[[noreturn]] void deep_compare_not_implemented() {
	throw std::runtime_error("value_t runtime error: deep table compare is not implemented yet");
}

} // namespace

// ============================================================
// Constructors
// ============================================================

value_t::value_t() noexcept : type_(kind_t::null_v) {}
value_t::value_t(null_t)    noexcept : type_(kind_t::null_v) {}
value_t::value_t(nullopt_t) noexcept : type_(kind_t::null_v) {}
value_t::value_t(nullptr_t) noexcept : type_(kind_t::null_v) {}

value_t::value_t(const bool_t  &value) noexcept : type_(kind_t::bool_v),  bool_value_(value)  {}
value_t::value_t(const int_t   &value) noexcept : type_(kind_t::int_v),   int_value_(value)   {}
value_t::value_t(const float_t &value) noexcept : type_(kind_t::float_v), float_value_(value) {}

value_t::value_t(const string_t &value) : type_(kind_t::string_v) {
	new (&string_value_) unique_p<string_t>(unique<string_t>(value));
}
value_t::value_t(const char *value) : value_t(string_t{value}) {}

value_t::value_t(bool value)           noexcept : type_(kind_t::bool_v),  bool_value_(bool_t{value})    {}
value_t::value_t(std::int64_t value)   noexcept : type_(kind_t::int_v),   int_value_(int_t{value})      {}
value_t::value_t(double value)         noexcept : type_(kind_t::float_v), float_value_(float_t{value})  {}

value_t::value_t(unique_p<table_t<value_t>> value) noexcept : type_(kind_t::table_v) {
	new (&table_value_) unique_p<table_t<value_t>>(std::move(value));
}
value_t::value_t(shared_p<table_t<value_t>> value) noexcept : type_(kind_t::shared_table_v) {
	new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(value));
}
value_t::value_t(weak_p<table_t<value_t>> value) noexcept : type_(kind_t::weak_table_v) {
	new (&weak_table_value_) weak_p<table_t<value_t>>(std::move(value));
}
value_t::value_t(std::unique_ptr<table_t<value_t>> value) noexcept
	: value_t(unique_p<table_t<value_t>>(std::move(value))) {}
value_t::value_t(std::shared_ptr<table_t<value_t>> value) noexcept
	: value_t(shared_p<table_t<value_t>>(std::move(value))) {}
value_t::value_t(std::weak_ptr<table_t<value_t>> value) noexcept
	: value_t(weak_p<table_t<value_t>>(std::move(value))) {}

value_t::~value_t() { destroy(); }

value_t::value_t(const value_t &other) : type_(kind_t::null_v) { *this = other.clone(); }
value_t::value_t(value_t &&other)      noexcept : type_(kind_t::null_v) { move_construct(std::move(other)); }

// ============================================================
// Assignment operators
// ============================================================

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

value_t &value_t::operator=(null_t)    noexcept { destroy(); type_ = kind_t::null_v; return *this; }
value_t &value_t::operator=(nullopt_t) noexcept { destroy(); type_ = kind_t::null_v; return *this; }
value_t &value_t::operator=(nullptr_t) noexcept { destroy(); type_ = kind_t::null_v; return *this; }

value_t &value_t::operator=(const bool_t &value) noexcept {
	destroy(); type_ = kind_t::bool_v; new (&bool_value_) bool_t(value); return *this;
}
value_t &value_t::operator=(const int_t &value) noexcept {
	destroy(); type_ = kind_t::int_v; new (&int_value_) int_t(value); return *this;
}
value_t &value_t::operator=(const float_t &value) noexcept {
	destroy(); type_ = kind_t::float_v; new (&float_value_) float_t(value); return *this;
}
value_t &value_t::operator=(const string_t &value) {
	destroy(); type_ = kind_t::string_v;
	new (&string_value_) unique_p<string_t>(unique<string_t>(value));
	return *this;
}
value_t &value_t::operator=(const char *value) { return (*this = string_t{value}); }
value_t &value_t::operator=(bool value)           noexcept { return (*this = bool_t{value});  }
value_t &value_t::operator=(std::int64_t value)   noexcept { return (*this = int_t{value});   }
value_t &value_t::operator=(double value)          noexcept { return (*this = float_t{value}); }

value_t &value_t::operator=(unique_p<table_t<value_t>> value) noexcept {
	destroy(); type_ = kind_t::table_v;
	new (&table_value_) unique_p<table_t<value_t>>(std::move(value));
	return *this;
}
value_t &value_t::operator=(shared_p<table_t<value_t>> value) noexcept {
	destroy(); type_ = kind_t::shared_table_v;
	new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(value));
	return *this;
}
value_t &value_t::operator=(weak_p<table_t<value_t>> value) noexcept {
	destroy(); type_ = kind_t::weak_table_v;
	new (&weak_table_value_) weak_p<table_t<value_t>>(std::move(value));
	return *this;
}
value_t &value_t::operator=(std::unique_ptr<table_t<value_t>> value) noexcept {
	return (*this = unique_p<table_t<value_t>>(std::move(value)));
}
value_t &value_t::operator=(std::shared_ptr<table_t<value_t>> value) noexcept {
	return (*this = shared_p<table_t<value_t>>(std::move(value)));
}
value_t &value_t::operator=(std::weak_ptr<table_t<value_t>> value) noexcept {
	return (*this = weak_p<table_t<value_t>>(std::move(value)));
}

// ============================================================
// clone
// ============================================================

value_t value_t::clone() const {
	switch (type_) {
		case kind_t::null_v:         return value_t{null_t{}};
		case kind_t::bool_v:         return value_t{bool_value_};
		case kind_t::int_v:          return value_t{int_value_};
		case kind_t::float_v:        return value_t{float_value_};
		case kind_t::string_v:       return value_t{*string_value_};
		case kind_t::table_v:        return value_t{unique<table_t<value_t>>(*table_value_)};
		case kind_t::shared_table_v: return value_t{shared_table_value_};
		case kind_t::weak_table_v:   return value_t{weak_table_value_};
	}
	return value_t{null_t{}};
}

// ============================================================
// Observers
// ============================================================

value_t::kind_t value_t::kind()    const noexcept { return type_; }
bool_t          value_t::is_null() const noexcept { return bool_t{type_ == kind_t::null_v}; }

bool_t value_t::bool_value() const {
	if (type_ != kind_t::bool_v) throw std::logic_error("value_t: not bool");
	return bool_value_;
}
int_t value_t::int_value() const {
	if (type_ != kind_t::int_v) throw std::logic_error("value_t: not int");
	return int_value_;
}
float_t value_t::float_value() const {
	if (type_ != kind_t::float_v) throw std::logic_error("value_t: not float");
	return float_value_;
}

string_t *value_t::string_if() noexcept {
	return (type_ == kind_t::string_v) ? string_value_.get() : nullptr;
}
const string_t *value_t::string_if() const noexcept {
	return (type_ == kind_t::string_v) ? string_value_.get() : nullptr;
}

table_t<value_t> *value_t::table_if() noexcept {
	if (type_ == kind_t::table_v)        return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	return nullptr;
}
const table_t<value_t> *value_t::table_if() const noexcept {
	if (type_ == kind_t::table_v)        return table_value_.get();
	if (type_ == kind_t::shared_table_v) return shared_table_value_.get();
	return nullptr;
}

shared_p<table_t<value_t>> *value_t::shared_table_if() noexcept {
	return (type_ == kind_t::shared_table_v) ? &shared_table_value_ : nullptr;
}
const shared_p<table_t<value_t>> *value_t::shared_table_if() const noexcept {
	return (type_ == kind_t::shared_table_v) ? &shared_table_value_ : nullptr;
}

weak_p<table_t<value_t>> *value_t::weak_table_if() noexcept {
	return (type_ == kind_t::weak_table_v) ? &weak_table_value_ : nullptr;
}
const weak_p<table_t<value_t>> *value_t::weak_table_if() const noexcept {
	return (type_ == kind_t::weak_table_v) ? &weak_table_value_ : nullptr;
}

// ============================================================
// as_*_ref  — autovivify + coerce, return direct reference
// ============================================================

int_t &value_t::as_int_ref() {
	switch (type_) {
		case kind_t::int_v:   return int_value_;
		case kind_t::null_v:  *this = int_t{};                    return int_value_;
		case kind_t::float_v: *this = cast<int_t>(float_value_);  return int_value_;
		default: throw runtime_error_unary("as_int_ref", *this);
	}
}

float_t &value_t::as_float_ref() {
	switch (type_) {
		case kind_t::float_v: return float_value_;
		case kind_t::null_v:  *this = float_t{};                                               return float_value_;
		case kind_t::int_v:   *this = float_t(static_cast<double>(int_value_.native_value())); return float_value_;
		default: throw runtime_error_unary("as_float_ref", *this);
	}
}

bool_t &value_t::as_bool_ref() {
	switch (type_) {
		case kind_t::bool_v:  return bool_value_;
		case kind_t::null_v:  *this = bool_t{};                  return bool_value_;
		case kind_t::int_v:   *this = cast<bool_t>(int_value_);  return bool_value_;
		case kind_t::float_v: *this = cast<bool_t>(float_value_); return bool_value_;
		default: throw runtime_error_unary("as_bool_ref", *this);
	}
}

string_t &value_t::as_string_ref() {
	switch (type_) {
		case kind_t::string_v: return *string_value_;
		case kind_t::null_v:   *this = string_t{};                    return *string_value_;
		case kind_t::bool_v:   *this = cast<string_t>(bool_value_);   return *string_value_;
		case kind_t::int_v:    *this = cast<string_t>(int_value_);    return *string_value_;
		case kind_t::float_v:  *this = cast<string_t>(float_value_);  return *string_value_;
		default: throw runtime_error_unary("as_string_ref", *this);
	}
}

table_t<value_t> &value_t::as_table_ref() {
	if (type_ == kind_t::table_v)        return *table_value_;
	if (type_ == kind_t::shared_table_v) return *shared_table_value_;
	throw runtime_error_unary("as_table_ref", *this);
}

// ============================================================
// Conversion operators
// ============================================================

value_t::operator bool_t()  const { return cast<bool_t>(*this);  }
value_t::operator int_t()   const { return cast<int_t>(*this);   }
value_t::operator float_t() const { return cast<float_t>(*this); }
value_t::operator string_t() const { return cast<string_t>(*this); }

value_t::operator string_t&() { return as_string_ref(); }

value_t::operator bool() const { return cast<bool>(*this); }

value_t::operator shared_p<table_t<value_t>>() const {
	return cast<shared_p<table_t<value_t>>>(*this);
}
value_t::operator weak_p<table_t<value_t>>() const {
	return cast<weak_p<table_t<value_t>>>(*this);
}

// ============================================================
// Increment / Decrement
// ============================================================

value_t &value_t::operator++() {
	switch (type_) {
		case kind_t::int_v:   ++int_value_;   return *this;
		case kind_t::float_v: ++float_value_; return *this;
		default: throw runtime_error_unary("++", *this);
	}
}
value_t value_t::operator++(int) { value_t snap = clone(); ++(*this); return snap; }

value_t &value_t::operator--() {
	switch (type_) {
		case kind_t::int_v:   --int_value_;   return *this;
		case kind_t::float_v: --float_value_; return *this;
		default: throw runtime_error_unary("--", *this);
	}
}
value_t value_t::operator--(int) { value_t snap = clone(); --(*this); return snap; }

// ============================================================
// Compound assignment operators
// ============================================================

value_t &value_t::operator+=(const value_t &right) {
	switch (type_) {
		case kind_t::int_v:
			if (right.kind() == kind_t::int_v)   { int_value_   += right.int_value_;   return *this; }
			break;
		case kind_t::float_v:
			if (right.kind() == kind_t::float_v) { float_value_ += right.float_value_; return *this; }
			if (right.kind() == kind_t::int_v)   { float_value_ += right.int_value_;   return *this; }
			break;
		case kind_t::string_v:
			if (right.kind() == kind_t::string_v) { *string_value_ = *string_value_ + *right.string_value_; return *this; }
			break;
		default: break;
	}
	throw runtime_error_binary("+=", *this, right);
}

value_t &value_t::operator-=(const value_t &right) {
	switch (type_) {
		case kind_t::int_v:
			if (right.kind() == kind_t::int_v)   { int_value_   -= right.int_value_;   return *this; }
			break;
		case kind_t::float_v:
			if (right.kind() == kind_t::float_v) { float_value_ -= right.float_value_; return *this; }
			if (right.kind() == kind_t::int_v)   { float_value_ -= right.int_value_;   return *this; }
			break;
		default: break;
	}
	throw runtime_error_binary("-=", *this, right);
}

value_t &value_t::operator*=(const value_t &right) {
	switch (type_) {
		case kind_t::int_v:
			if (right.kind() == kind_t::int_v)   { int_value_   *= right.int_value_;   return *this; }
			break;
		case kind_t::float_v:
			if (right.kind() == kind_t::float_v) { float_value_ *= right.float_value_; return *this; }
			if (right.kind() == kind_t::int_v)   { float_value_ *= right.int_value_;   return *this; }
			break;
		default: break;
	}
	throw runtime_error_binary("*=", *this, right);
}

value_t &value_t::operator/=(const value_t &right) {
	switch (type_) {
		case kind_t::int_v:
			if (right.kind() == kind_t::int_v)   { int_value_   /= right.int_value_;   return *this; }
			break;
		case kind_t::float_v:
			if (right.kind() == kind_t::float_v) { float_value_ /= right.float_value_; return *this; }
			if (right.kind() == kind_t::int_v)   { float_value_ /= right.int_value_;   return *this; }
			break;
		default: break;
	}
	throw runtime_error_binary("/=", *this, right);
}

value_t &value_t::operator%=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ %= right.int_value_; return *this;
	}
	throw runtime_error_binary("%=", *this, right);
}
value_t &value_t::operator&=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ &= right.int_value_; return *this;
	}
	throw runtime_error_binary("&=", *this, right);
}
value_t &value_t::operator|=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ |= right.int_value_; return *this;
	}
	throw runtime_error_binary("|=", *this, right);
}
value_t &value_t::operator^=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ ^= right.int_value_; return *this;
	}
	throw runtime_error_binary("^=", *this, right);
}
value_t &value_t::operator<<=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ <<= right.int_value_; return *this;
	}
	throw runtime_error_binary("<<=", *this, right);
}
value_t &value_t::operator>>=(const value_t &right) {
	if (type_ == kind_t::int_v && right.kind() == kind_t::int_v) {
		int_value_ >>= right.int_value_; return *this;
	}
	throw runtime_error_binary(">>=", *this, right);
}

// ============================================================
// Fat-variable operator[]  — autovivifying table access
// ============================================================

// Helper: resolve table pointer for the three ownership variants.
// For mutable access, also autovivifies null -> unique table.
namespace {
table_t<value_t> *resolve_table_mut(value_t &self) {
	if (self.kind() == value_t::kind_t::null_v) {
		self = unique<table_t<value_t>>();
	}
	return self.table_if();
}
const table_t<value_t> *resolve_table_const(const value_t &self) noexcept {
	if (self.kind() == value_t::kind_t::weak_table_v) {
		// weak_table_if() returns pointer to the weak_p — we cannot dereference
		// without locking, and we cannot store a locked copy here.
		// Return nullptr so const operator[] safely returns the static null.
		return nullptr;
	}
	return self.table_if();
}
} // namespace

value_t &value_t::operator[](const int_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}
value_t &value_t::operator[](const string_t &key) {
	auto *t = resolve_table_mut(*this);
	if (t) return (*t)[key];
	throw runtime_error_unary("operator[]", *this);
}
value_t &value_t::operator[](const value_t &key) {
	if (key.kind() == kind_t::int_v)    return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	throw runtime_error_unary("operator[]", *this);
}
value_t &value_t::operator[](const char *key) {
	return operator[](string_t{key});
}
value_t &value_t::operator[](int native_key) {
	return operator[](int_t{static_cast<std::int64_t>(native_key)});
}

const value_t &value_t::operator[](const int_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return (*t)[key];
	// weak_table: lock and look up
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return (*locked)[key];
	}
	static const value_t null_val{null_t{}};
	return null_val;
}
const value_t &value_t::operator[](const string_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return (*t)[key];
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return (*locked)[key];
	}
	static const value_t null_val{null_t{}};
	return null_val;
}
const value_t &value_t::operator[](const value_t &key) const {
	if (key.kind() == kind_t::int_v)    return operator[](key.int_value());
	if (key.kind() == kind_t::string_v) return operator[](*key.string_if());
	static const value_t null_val{null_t{}};
	return null_val;
}
const value_t &value_t::operator[](const char *key) const {
	return operator[](string_t{key});
}
const value_t &value_t::operator[](int native_key) const {
	return operator[](int_t{static_cast<std::int64_t>(native_key)});
}

// ============================================================
// get (non-autovivifying read-by-value)
// ============================================================

value_t value_t::get(const int_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return t->_find_val(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->_find_val(key);
	}
	return value_t{null_t{}};
}
value_t value_t::get(const string_t &key) const {
	if (const auto *t = resolve_table_const(*this)) return t->_find_val(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->_find_val(key);
	}
	return value_t{null_t{}};
}
value_t value_t::get(const value_t &key) const {
	if (key.kind() == kind_t::int_v) return get(key.int_value());
	if (key.kind() == kind_t::string_v) return get(*key.string_if());
	return value_t{null_t{}};
}
value_t value_t::get(const char *key) const {
	return get(string_t{key});
}
value_t value_t::get(int native_key) const {
	return get(int_t{static_cast<std::int64_t>(native_key)});
}

// ============================================================
// append
// ============================================================

void value_t::append(const value_t &val) {
	auto *t = resolve_table_mut(*this);
	if (t) { (void)t->append(val); return; }
	throw runtime_error_unary("append", *this);
}

// ============================================================
// isset
// ============================================================

bool_t value_t::isset(const value_t &key) const {
	if (key.kind() == kind_t::int_v)    return isset(key.int_value());
	if (key.kind() == kind_t::string_v) return isset(*key.string_if());
	return bool_t{false};
}
bool_t value_t::isset(const int_t &key) const {
	if (const auto *t = table_if()) return t->has(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->has(key);
	}
	return bool_t{false};
}
bool_t value_t::isset(const string_t &key) const {
	if (const auto *t = table_if()) return t->has(key);
	if (type_ == kind_t::weak_table_v) {
		auto locked = weak_table_value_.lock();
		if (locked) return locked->has(key);
	}
	return bool_t{false};
}
bool_t value_t::isset(const char *key) const {
	return isset(string_t{key});
}
bool_t value_t::isset(int native_key) const {
	return isset(int_t{static_cast<std::int64_t>(native_key)});
}

// ============================================================
// Free (binary / unary) operators
// ============================================================

value_t operator+(const value_t &value) {
	switch (value.kind()) {
		case value_t::kind_t::int_v:   return value_t{+value.int_value_};
		case value_t::kind_t::float_v: return value_t{+value.float_value_};
		default: throw runtime_error_unary("+", value);
	}
}
value_t operator-(const value_t &value) {
	switch (value.kind()) {
		case value_t::kind_t::int_v:   return value_t{-value.int_value_};
		case value_t::kind_t::float_v: return value_t{-value.float_value_};
		default: throw runtime_error_unary("-", value);
	}
}
bool_t operator!(const value_t &value) {
	if (value.kind() == value_t::kind_t::bool_v) return !value.bool_value_;
	throw runtime_error_unary("!", value);
}
value_t operator~(const value_t &value) {
	if (value.kind() == value_t::kind_t::int_v) return value_t{~value.int_value_};
	throw runtime_error_unary("~", value);
}

value_t operator+(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ + right.int_value_};
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return value_t{left.int_value_ + right.float_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.float_value_ + right.int_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return value_t{left.float_value_ + right.float_value_};
	if (left.kind() == value_t::kind_t::string_v && right.kind() == value_t::kind_t::string_v)
		return value_t{*left.string_value_ + *right.string_value_};
	throw runtime_error_binary("+", left, right);
}
value_t operator-(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ - right.int_value_};
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return value_t{left.int_value_ - right.float_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.float_value_ - right.int_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return value_t{left.float_value_ - right.float_value_};
	throw runtime_error_binary("-", left, right);
}
value_t operator*(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ * right.int_value_};
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return value_t{left.int_value_ * right.float_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.float_value_ * right.int_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return value_t{left.float_value_ * right.float_value_};
	throw runtime_error_binary("*", left, right);
}
value_t operator/(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ / right.int_value_};
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return value_t{left.int_value_ / right.float_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.float_value_ / right.int_value_};
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return value_t{left.float_value_ / right.float_value_};
	throw runtime_error_binary("/", left, right);
}
value_t operator%(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ % right.int_value_};
	throw runtime_error_binary("%", left, right);
}
value_t operator&(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ & right.int_value_};
	throw runtime_error_binary("&", left, right);
}
value_t operator|(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ | right.int_value_};
	throw runtime_error_binary("|", left, right);
}
value_t operator^(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ ^ right.int_value_};
	throw runtime_error_binary("^", left, right);
}
value_t operator<<(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ << right.int_value_};
	throw runtime_error_binary("<<", left, right);
}
value_t operator>>(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v && right.kind() == value_t::kind_t::int_v)
		return value_t{left.int_value_ >> right.int_value_};
	throw runtime_error_binary(">>", left, right);
}

bool_t operator==(const value_t &left, const value_t &right) {
	if (left.kind()  == value_t::kind_t::null_v) return compare_with_null_eq(right);
	if (right.kind() == value_t::kind_t::null_v) return compare_with_null_eq(left);
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return left.int_value_ == right.int_value_;
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return left.int_value_ == right.float_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return left.float_value_ == right.int_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return left.float_value_ == right.float_value_;
	if (left.kind() == value_t::kind_t::bool_v  && right.kind() == value_t::kind_t::bool_v)
		return left.bool_value_ == right.bool_value_;
	if (left.kind() == value_t::kind_t::string_v && right.kind() == value_t::kind_t::string_v)
		return *left.string_value_ == *right.string_value_;
	if (left.kind() == value_t::kind_t::shared_table_v && right.kind() == value_t::kind_t::shared_table_v)
		return left.shared_table_value_ == right.shared_table_value_;
	if (left.kind() == value_t::kind_t::shared_table_v && right.kind() == value_t::kind_t::weak_table_v)
		return compare_shared_to_weak_identity(left.shared_table_value_, right.weak_table_value_);
	if (left.kind() == value_t::kind_t::weak_table_v && right.kind() == value_t::kind_t::shared_table_v)
		return compare_shared_to_weak_identity(right.shared_table_value_, left.weak_table_value_);
	if (left.kind() == value_t::kind_t::weak_table_v && right.kind() == value_t::kind_t::weak_table_v)
		return compare_weak_to_weak_identity(left.weak_table_value_, right.weak_table_value_);
	if (left.kind() == value_t::kind_t::table_v && right.kind() == value_t::kind_t::table_v)
		deep_compare_not_implemented();
	throw runtime_error_binary("==", left, right);
}
bool_t operator!=(const value_t &left, const value_t &right) {
	return bool_t{!static_cast<bool>(operator==(left, right))};
}
bool_t operator<(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return left.int_value_ < right.int_value_;
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return left.int_value_ < right.float_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return left.float_value_ < right.int_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return left.float_value_ < right.float_value_;
	throw runtime_error_binary("<", left, right);
}
bool_t operator<=(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return left.int_value_ <= right.int_value_;
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return left.int_value_ <= right.float_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return left.float_value_ <= right.int_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return left.float_value_ <= right.float_value_;
	throw runtime_error_binary("<=", left, right);
}
bool_t operator>(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return left.int_value_ > right.int_value_;
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return left.int_value_ > right.float_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return left.float_value_ > right.int_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return left.float_value_ > right.float_value_;
	throw runtime_error_binary(">", left, right);
}
bool_t operator>=(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::int_v)
		return left.int_value_ >= right.int_value_;
	if (left.kind() == value_t::kind_t::int_v   && right.kind() == value_t::kind_t::float_v)
		return left.int_value_ >= right.float_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::int_v)
		return left.float_value_ >= right.int_value_;
	if (left.kind() == value_t::kind_t::float_v && right.kind() == value_t::kind_t::float_v)
		return left.float_value_ >= right.float_value_;
	throw runtime_error_binary(">=", left, right);
}
bool_t operator&&(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::bool_v && right.kind() == value_t::kind_t::bool_v)
		return left.bool_value_ && right.bool_value_;
	throw runtime_error_binary("&&", left, right);
}
bool_t operator||(const value_t &left, const value_t &right) {
	if (left.kind() == value_t::kind_t::bool_v && right.kind() == value_t::kind_t::bool_v)
		return left.bool_value_ || right.bool_value_;
	throw runtime_error_binary("||", left, right);
}

// ============================================================
// Internal helpers
// ============================================================

void value_t::destroy() noexcept {
	switch (type_) {
		case kind_t::bool_v:         bool_value_.~bool_t();                            break;
		case kind_t::int_v:          int_value_.~int_t();                              break;
		case kind_t::float_v:        float_value_.~float_t();                          break;
		case kind_t::string_v:       string_value_.~unique_p<string_t>();              break;
		case kind_t::table_v:        table_value_.~unique_p<table_t<value_t>>();       break;
		case kind_t::shared_table_v: shared_table_value_.~shared_p<table_t<value_t>>(); break;
		case kind_t::weak_table_v:   weak_table_value_.~weak_p<table_t<value_t>>();    break;
		case kind_t::null_v:         break;
	}
	type_ = kind_t::null_v;
}

void value_t::move_construct(value_t &&other) noexcept {
	type_ = other.type_;
	switch (other.type_) {
		case kind_t::null_v:         break;
		case kind_t::bool_v:         bool_value_   = other.bool_value_;   break;
		case kind_t::int_v:          int_value_    = other.int_value_;    break;
		case kind_t::float_v:        float_value_  = other.float_value_;  break;
		case kind_t::string_v:
			new (&string_value_) unique_p<string_t>(std::move(other.string_value_)); break;
		case kind_t::table_v:
			new (&table_value_) unique_p<table_t<value_t>>(std::move(other.table_value_)); break;
		case kind_t::shared_table_v:
			new (&shared_table_value_) shared_p<table_t<value_t>>(std::move(other.shared_table_value_)); break;
		case kind_t::weak_table_v:
			new (&weak_table_value_) weak_p<table_t<value_t>>(std::move(other.weak_table_value_)); break;
	}
	other.type_ = kind_t::null_v;
}

} // namespace scpp
