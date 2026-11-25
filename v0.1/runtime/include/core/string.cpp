#include "scpp/detail/string_access.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/failure.hpp"
#include <limits>
#include <string_view>

namespace scpp
{
    // Reject host-size truncation before any memory access, including on 32-bit hosts.
    static std::size_t string_host_size(uint64 value)
    {
        if (value.native_value() > std::numeric_limits<std::size_t>::max()) {
            raise_runtime_error("String size exceeds host range", "size_out_of_range", "core", "string");
        }
        return static_cast<std::size_t>(value.native_value());
    }

    string::string() { ::new (this->storage_) string_t(); }
    string::string(const char *text) { ::new (this->storage_) string_t(text); }
    string::string(const char *bytes, uint64 length)
    {
        const auto size = string_host_size(length);
        if (bytes == nullptr && size != 0) {
            raise_runtime_error("Null string bytes with nonzero length", "invalid_argument", "core", "string");
        }
        ::new (this->storage_) string_t(std::string_view(bytes == nullptr ? "" : bytes, size));
    }

    string::string(void (*construct)(void *, const void *), const void *context)
    {
        construct(this->storage_, context);
    }

    string::string(const string &other) { ::new (this->storage_) string_t(detail::string_access::get(other)); }
    string::string(string &&other) noexcept { ::new (this->storage_) string_t(std::move(detail::string_access::get(other))); }
    string::~string() noexcept { detail::string_access::get(*this).~string_t(); }

    string &string::operator=(const string &other)
    {
        if (this != &other) {
            detail::string_access::get(*this) = detail::string_access::get(other);
        }
        return *this;
    }

    string &string::operator=(string &&other) noexcept
    {
        if (this != &other) {
            detail::string_access::get(*this) = std::move(detail::string_access::get(other));
        }
        return *this;
    }

    uint64 string::size() const noexcept { return this->byte_size(); }
    uint64 string::byte_size() const noexcept { return uint64(detail::string_access::get(*this).byte_size()); }
    uint64 string::byte_capacity() const noexcept { return uint64(detail::string_access::get(*this).byte_capacity()); }
    uint64 string::estimated_storage_bytes() const noexcept { return uint64(detail::string_access::get(*this).estimated_storage_bytes()); }
    boolean string::empty() const noexcept { return boolean(detail::string_access::get(*this).empty().native_value()); }
    boolean string::is_valid_utf8() const { return boolean(detail::string_access::get(*this).is_valid_utf8()); }
    uint64 string::length_cp() const { return uint64(detail::string_access::get(*this).length_cp()); }

    uint32 string::byte_at(uint64 index) const
    {
        const auto &bytes = detail::string_access::get(*this).native_value();
        if (index.native_value() >= bytes.size()) {
            raise_runtime_error("String byte index out of range", "index_out_of_range", "core", "string.byte_at");
        }
        return uint32(static_cast<unsigned char>(bytes[static_cast<std::size_t>(index.native_value())]));
    }

    string string::slice(uint64 start, uint64 end) const
    {
        return detail::string_access::make([&] {
            return detail::string_access::get(*this).slice(string_host_size(start), string_host_size(end));
        });
    }

    string string::substr_cp(uint64 start, uint64 length) const
    {
        return detail::string_access::make([&] {
            return detail::string_access::get(*this).substr_cp(string_host_size(start), string_host_size(length));
        });
    }

    string string::substr_cp(uint64 start) const
    {
        return detail::string_access::make([&] {
            return detail::string_access::get(*this).substr_cp(string_host_size(start));
        });
    }

    boolean string::equals(const string &other) const { return *this == other; }
    boolean string::equals(const char *text) const
    {
        return boolean(detail::string_access::get(*this).native_value() == text);
    }

    // Borrow the space-delimited vocabulary; no token strings or containers are built.
    boolean string::is_one_of(const char *vocabulary) const
    {
        const auto &bytes = detail::string_access::get(*this).native_value();
        const char *cursor = vocabulary;
        while (*cursor != '\0') {
            while (*cursor == ' ') { ++cursor; }
            const char *start = cursor;
            while (*cursor != '\0' && *cursor != ' ') { ++cursor; }
            if (cursor != start && bytes == std::string_view(start, cursor - start)) {
                return boolean(true);
            }
        }
        return boolean(false);
    }

    void string::append(const string &other) { detail::string_access::get(*this).append(detail::string_access::get(other)); }
    void string::swap(string &other) noexcept
    {
        if (this != &other) {
            std::swap(detail::string_access::get(*this), detail::string_access::get(other));
        }
    }
    void string::clear() noexcept { detail::string_access::get(*this)._unset_(); }
    void string::_unset_() noexcept { this->clear(); }

    string operator+(const string &left, const string &right)
    {
        return detail::string_access::make([&] {
            return detail::string_access::get(left) + detail::string_access::get(right);
        });
    }
    string &operator+=(string &left, const string &right) { left.append(right); return left; }
    boolean operator==(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) == detail::string_access::get(right)).native_value());
    }
    boolean operator!=(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) != detail::string_access::get(right)).native_value());
    }
    boolean operator<(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) < detail::string_access::get(right)).native_value());
    }
    boolean operator<=(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) <= detail::string_access::get(right)).native_value());
    }
    boolean operator>(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) > detail::string_access::get(right)).native_value());
    }
    boolean operator>=(const string &left, const string &right)
    {
        return boolean((detail::string_access::get(left) >= detail::string_access::get(right)).native_value());
    }
    uint64 strlen(const string &value) { return value.byte_size(); }
    boolean empty(const string &value) { return value.empty(); }
    boolean isset(const string &) { return boolean(true); }
}
