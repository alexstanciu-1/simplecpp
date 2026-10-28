// Generated from reviewed runtime metadata. Do not edit.
#include "scpp/numeric.hpp"
#include "scalar_semantics.hpp"
#include "scpp/string.hpp"
#include "sequence_semantics.hpp"
namespace scpp {
struct string::storage { std::string bytes; };
namespace detail { struct string_access {
    static const std::string& read(const string& value) noexcept { static const std::string empty; return value.storage_ ? value.storage_->bytes : empty; }
}; }
string::~string() { delete storage_; }
string::string(const string& other) : storage_(other.storage_ ? new storage{other.storage_->bytes} : nullptr) {}
string::string(string&& other) noexcept : storage_(std::exchange(other.storage_, nullptr)) {}
string& string::operator=(const string& other) { if (this != &other) { string copy(other); std::swap(storage_, copy.storage_); } return *this; }
string& string::operator=(string&& other) noexcept { if (this != &other) { delete storage_; storage_ = std::exchange(other.storage_, nullptr); } return *this; }
string::string(const char* bytes) : storage_(new storage{detail::string_from_c_string(bytes)}) {}
string::string(const char* bytes, int64 count) : storage_(new storage{detail::string_from_bytes(bytes, count.native_value())}) {}
int64 string::size() const { return int64{detail::checked_integer_cast<long long>(detail::string_access::read(*this).size())}; }
bool_t string::empty() const noexcept { return bool_t{detail::string_access::read(*this).empty()}; }
const char* string::data() const noexcept { return detail::string_access::read(*this).data(); }
void string::append(const string& other) { if (!storage_) { string copy(other); std::swap(storage_, copy.storage_); } else { storage_->bytes.append(detail::string_access::read(other)); } }
void string::clear() noexcept { delete storage_; storage_ = nullptr; }
string operator+(string left, const string& right) { left.append(right); return left; }
string& operator+=(string& left, const string& right) { left.append(right); return left; }
bool_t operator==(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) == detail::string_access::read(right)}; }
bool_t operator>(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) > detail::string_access::read(right)}; }
bool_t operator>=(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) >= detail::string_access::read(right)}; }
bool_t operator<(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) < detail::string_access::read(right)}; }
bool_t operator<=(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) <= detail::string_access::read(right)}; }
bool_t operator!=(const string& left, const string& right) noexcept { return bool_t{detail::string_access::read(left) != detail::string_access::read(right)}; }
namespace detail { struct integer_access { template<class T, class Rep> static T make(Rep value) { T out; out.value_ = value; return out; } }; }
static_assert(std::numeric_limits<double>::is_iec559 && std::numeric_limits<double>::radix == 2 && std::numeric_limits<double>::digits == 53 && std::numeric_limits<double>::max_exponent == 1024);
static_assert(sizeof(float64) == sizeof(double));
bool_t::bool_t(float64 value) noexcept : value_(detail::truth_cast<bool>(value.native_value())) {}
bool_t::bool_t(int32 value) noexcept : value_(detail::truth_cast<bool>(value.native_value())) {}
bool_t::bool_t(int64 value) noexcept : value_(detail::truth_cast<bool>(value.native_value())) {}
bool_t::bool_t(const string& value) : value_(detail::parse_boolean_text(detail::string_access::read(value))) {}
float64::float64(bool_t value) noexcept : value_(detail::numeric_cast<double>(value.native_value())) {}
float64::float64(double value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(float value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(int value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(int32 value) noexcept : value_(detail::numeric_cast<double>(value.native_value())) {}
float64::float64(int64 value) noexcept : value_(detail::numeric_cast<double>(value.native_value())) {}
float64::float64(long value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(long long value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(const string& value) : value_(detail::parse_decimal<double>(detail::string_access::read(value))) {}
float64::float64(unsigned int value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(unsigned long value) noexcept : value_(detail::numeric_cast<double>(value)) {}
float64::float64(unsigned long long value) noexcept : value_(detail::numeric_cast<double>(value)) {}
int32::int32(bool_t value) noexcept : value_(detail::numeric_cast<__INT32_TYPE__>(value.native_value())) {}
int32::int32(float64 value) : value_(detail::floating_integer_cast<__INT32_TYPE__>(value.native_value())) {}
int32::int32(int value) noexcept : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int32::int32(int64 value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value.native_value())) {}
int32::int32(long value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int32::int32(long long value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int32::int32(const string& value) : value_(detail::parse_decimal<__INT32_TYPE__>(detail::string_access::read(value))) {}
int32::int32(unsigned int value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int32::int32(unsigned long value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int32::int32(unsigned long long value) : value_(detail::checked_integer_cast<__INT32_TYPE__>(value)) {}
int64::int64(bool_t value) noexcept : value_(detail::numeric_cast<__INT64_TYPE__>(value.native_value())) {}
int64::int64(float64 value) : value_(detail::floating_integer_cast<__INT64_TYPE__>(value.native_value())) {}
int64::int64(int value) noexcept : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
int64::int64(int32 value) noexcept : value_(detail::checked_integer_cast<__INT64_TYPE__>(value.native_value())) {}
int64::int64(long value) noexcept : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
int64::int64(long long value) noexcept : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
int64::int64(const string& value) : value_(detail::parse_decimal<__INT64_TYPE__>(detail::string_access::read(value))) {}
int64::int64(unsigned int value) noexcept : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
int64::int64(unsigned long value) : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
int64::int64(unsigned long long value) : value_(detail::checked_integer_cast<__INT64_TYPE__>(value)) {}
string::string(bool_t value) : storage_(new storage{detail::format_boolean_text(value.native_value())}) {}
string::string(float64 value) : storage_(new storage{detail::format_decimal(value.native_value())}) {}
string::string(int32 value) : storage_(new storage{detail::format_decimal(value.native_value())}) {}
string::string(int64 value) : storage_(new storage{detail::format_decimal(value.native_value())}) {}
static_assert(std::numeric_limits<int>::digits == 31);
static_assert(std::numeric_limits<int>::is_signed == true);
static_assert(std::numeric_limits<long>::digits == 63);
static_assert(std::numeric_limits<long>::is_signed == true);
static_assert(std::numeric_limits<long long>::digits == 63);
static_assert(std::numeric_limits<long long>::is_signed == true);
static_assert(std::numeric_limits<unsigned int>::digits == 32);
static_assert(std::numeric_limits<unsigned int>::is_signed == false);
static_assert(std::numeric_limits<unsigned long>::digits == 64);
static_assert(std::numeric_limits<unsigned long>::is_signed == false);
static_assert(std::numeric_limits<unsigned long long>::digits == 64);
static_assert(std::numeric_limits<unsigned long long>::is_signed == false);
static_assert(sizeof(int32) == sizeof(__INT32_TYPE__));
static_assert(alignof(int32) == alignof(__INT32_TYPE__));
static_assert(std::numeric_limits<__INT32_TYPE__>::digits == 31);
static_assert(sizeof(int64) == sizeof(__INT64_TYPE__));
static_assert(alignof(int64) == alignof(__INT64_TYPE__));
static_assert(std::numeric_limits<__INT64_TYPE__>::digits == 63);
float64 operator+(float64 left, float64 right) {
    return float64{detail::floating_add<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
float64 operator+(float64 left, int32 right) {
    return float64{detail::floating_add<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
float64 operator+(int32 left, float64 right) {
    return float64{detail::floating_add<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
int32 operator+(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_add<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator+(int32 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_add<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int64 operator+(int64 left, int32 right) {
    return detail::integer_access::make<int64>(detail::integer_add<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int64 operator+(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_add<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
float64& operator+=(float64& left, float64 right) {
    left = static_cast<float64>(float64{detail::floating_add<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))});
    return left;
}
int32& operator+=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_add<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int32& operator+=(int32& left, int64 right) {
    left = static_cast<int32>(detail::integer_access::make<int64>(detail::integer_add<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
int64& operator+=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_add<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
int32 operator&(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_bit_and<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator&(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_bit_and<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int32& operator&=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_bit_and<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator&=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_bit_and<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
int32 operator~(int32 left) {
    return detail::integer_access::make<int32>(detail::integer_bit_not<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
}
int64 operator~(int64 left) {
    return detail::integer_access::make<int64>(detail::integer_bit_not<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
}
int32 operator|(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_bit_or<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator|(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_bit_or<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int32& operator|=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_bit_or<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator|=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_bit_or<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
int32 operator^(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_bit_xor<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator^(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_bit_xor<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int32& operator^=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_bit_xor<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator^=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_bit_xor<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
float64 operator/(float64 left, float64 right) {
    return float64{detail::floating_divide<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
int32 operator/(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_divide<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator/(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_divide<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
float64& operator/=(float64& left, float64 right) {
    left = static_cast<float64>(float64{detail::floating_divide<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))});
    return left;
}
int32& operator/=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_divide<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator/=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_divide<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
bool_t operator==(float64 left, float64 right) {
    return bool_t{detail::floating_equal<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator==(int32 left, int32 right) {
    return bool_t{detail::integer_equal(left.native_value(), right.native_value())};
}
bool_t operator==(int64 left, int64 right) {
    return bool_t{detail::integer_equal(left.native_value(), right.native_value())};
}
bool_t operator>(float64 left, float64 right) {
    return bool_t{detail::floating_greater<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator>(int32 left, int32 right) {
    return bool_t{detail::integer_greater(left.native_value(), right.native_value())};
}
bool_t operator>(int64 left, int64 right) {
    return bool_t{detail::integer_greater(left.native_value(), right.native_value())};
}
bool_t operator>=(float64 left, float64 right) {
    return bool_t{detail::floating_greater_equal<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator>=(int32 left, int32 right) {
    return bool_t{detail::integer_greater_equal(left.native_value(), right.native_value())};
}
bool_t operator>=(int64 left, int64 right) {
    return bool_t{detail::integer_greater_equal(left.native_value(), right.native_value())};
}
bool_t operator<(float64 left, float64 right) {
    return bool_t{detail::floating_less<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator<(int32 left, int32 right) {
    return bool_t{detail::integer_less(left.native_value(), right.native_value())};
}
bool_t operator<(int64 left, int64 right) {
    return bool_t{detail::integer_less(left.native_value(), right.native_value())};
}
bool_t operator<=(float64 left, float64 right) {
    return bool_t{detail::floating_less_equal<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator<=(int32 left, int32 right) {
    return bool_t{detail::integer_less_equal(left.native_value(), right.native_value())};
}
bool_t operator<=(int64 left, int64 right) {
    return bool_t{detail::integer_less_equal(left.native_value(), right.native_value())};
}
float64 operator*(float64 left, float64 right) {
    return float64{detail::floating_multiply<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
int32 operator*(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_multiply<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator*(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_multiply<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
float64& operator*=(float64& left, float64 right) {
    left = static_cast<float64>(float64{detail::floating_multiply<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))});
    return left;
}
int32& operator*=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_multiply<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator*=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_multiply<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
float64 operator-(float64 left) {
    return float64{detail::floating_negate<double>(static_cast<double>(left.native_value()))};
}
int32 operator-(int32 left) {
    return detail::integer_access::make<int32>(detail::integer_negate<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
}
int64 operator-(int64 left) {
    return detail::integer_access::make<int64>(detail::integer_negate<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
}
bool_t operator!=(float64 left, float64 right) {
    return bool_t{detail::floating_not_equal<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
bool_t operator!=(int32 left, int32 right) {
    return bool_t{detail::integer_not_equal(left.native_value(), right.native_value())};
}
bool_t operator!=(int64 left, int64 right) {
    return bool_t{detail::integer_not_equal(left.native_value(), right.native_value())};
}
float64 operator+(float64 left) {
    return float64{detail::floating_positive<double>(static_cast<double>(left.native_value()))};
}
int32 operator+(int32 left) {
    return detail::integer_access::make<int32>(detail::integer_positive<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
}
int64 operator+(int64 left) {
    return detail::integer_access::make<int64>(detail::integer_positive<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
}
float64 operator--(float64& left, int) {
    const auto old = left;
    left = float64{detail::floating_decrement<double>(static_cast<double>(left.native_value()))};
    return old;
}
int32 operator--(int32& left, int) {
    const auto old = left;
    left = detail::integer_access::make<int32>(detail::integer_decrement<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
    return old;
}
int64 operator--(int64& left, int) {
    const auto old = left;
    left = detail::integer_access::make<int64>(detail::integer_decrement<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
    return old;
}
float64 operator++(float64& left, int) {
    const auto old = left;
    left = float64{detail::floating_increment<double>(static_cast<double>(left.native_value()))};
    return old;
}
int32 operator++(int32& left, int) {
    const auto old = left;
    left = detail::integer_access::make<int32>(detail::integer_increment<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
    return old;
}
int64 operator++(int64& left, int) {
    const auto old = left;
    left = detail::integer_access::make<int64>(detail::integer_increment<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
    return old;
}
float64& operator--(float64& left) {
    left = float64{detail::floating_decrement<double>(static_cast<double>(left.native_value()))};
    return left;
}
int32& operator--(int32& left) {
    left = detail::integer_access::make<int32>(detail::integer_decrement<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
    return left;
}
int64& operator--(int64& left) {
    left = detail::integer_access::make<int64>(detail::integer_decrement<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
    return left;
}
float64& operator++(float64& left) {
    left = float64{detail::floating_increment<double>(static_cast<double>(left.native_value()))};
    return left;
}
int32& operator++(int32& left) {
    left = detail::integer_access::make<int32>(detail::integer_increment<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value())));
    return left;
}
int64& operator++(int64& left) {
    left = detail::integer_access::make<int64>(detail::integer_increment<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value())));
    return left;
}
int32 operator%(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_remainder<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator%(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_remainder<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
int32& operator%=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_remainder<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator%=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_remainder<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
int32 operator<<(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_shift_left<__INT32_TYPE__>(left.native_value(), right.native_value()));
}
int64 operator<<(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_shift_left<__INT64_TYPE__>(left.native_value(), right.native_value()));
}
int32& operator<<=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_shift_left<__INT32_TYPE__>(left.native_value(), right.native_value())));
    return left;
}
int64& operator<<=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_shift_left<__INT64_TYPE__>(left.native_value(), right.native_value())));
    return left;
}
int32 operator>>(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_shift_right<__INT32_TYPE__>(left.native_value(), right.native_value()));
}
int64 operator>>(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_shift_right<__INT64_TYPE__>(left.native_value(), right.native_value()));
}
int32& operator>>=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_shift_right<__INT32_TYPE__>(left.native_value(), right.native_value())));
    return left;
}
int64& operator>>=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_shift_right<__INT64_TYPE__>(left.native_value(), right.native_value())));
    return left;
}
float64 operator-(float64 left, float64 right) {
    return float64{detail::floating_subtract<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))};
}
int32 operator-(int32 left, int32 right) {
    return detail::integer_access::make<int32>(detail::integer_subtract<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value())));
}
int64 operator-(int64 left, int64 right) {
    return detail::integer_access::make<int64>(detail::integer_subtract<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value())));
}
float64& operator-=(float64& left, float64 right) {
    left = static_cast<float64>(float64{detail::floating_subtract<double>(static_cast<double>(left.native_value()), static_cast<double>(right.native_value()))});
    return left;
}
int32& operator-=(int32& left, int32 right) {
    left = static_cast<int32>(detail::integer_access::make<int32>(detail::integer_subtract<__INT32_TYPE__>(static_cast<__INT32_TYPE__>(left.native_value()), static_cast<__INT32_TYPE__>(right.native_value()))));
    return left;
}
int64& operator-=(int64& left, int64 right) {
    left = static_cast<int64>(detail::integer_access::make<int64>(detail::integer_subtract<__INT64_TYPE__>(static_cast<__INT64_TYPE__>(left.native_value()), static_cast<__INT64_TYPE__>(right.native_value()))));
    return left;
}
}
