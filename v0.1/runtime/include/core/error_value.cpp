#include "scpp/detail/error_value_access.hpp"
#include "scpp/detail/string_access.hpp"

namespace scpp
{
    const error_value error_value::no_error{};
    error_value::error_value() { ::new (this->storage_) error_t(); }
    error_value::error_value(void (*construct)(void *, const void *), const void *context)
    {
        construct(this->storage_, context);
    }
    error_value::error_value(const error_value &other)
    {
        ::new (this->storage_) error_t(detail::error_value_access::get(other));
    }
    error_value::error_value(error_value &&other) noexcept
    {
        ::new (this->storage_) error_t(std::move(detail::error_value_access::get(other)));
    }
    error_value::~error_value() noexcept { detail::error_value_access::get(*this).~error_t(); }
    error_value &error_value::operator=(const error_value &other)
    {
        if (this != &other) { detail::error_value_access::get(*this) = detail::error_value_access::get(other); }
        return *this;
    }
    error_value &error_value::operator=(error_value &&other) noexcept
    {
        if (this != &other) { detail::error_value_access::get(*this) = std::move(detail::error_value_access::get(other)); }
        return *this;
    }
    error_value error_value::make(const string &message, int64 line, const string &file)
    {
        return detail::error_value_access::make([&] {
            return error_t::make(detail::string_access::get(message), int_t<>(line.native_value()), detail::string_access::get(file));
        });
    }
    boolean error_value::has_error() const noexcept
    {
        return boolean(detail::error_value_access::get(*this).has_error().native_value());
    }
    error_value::operator bool() const noexcept { return static_cast<bool>(this->has_error()); }
    boolean error_value::operator!() const noexcept { return !this->has_error(); }
    string error_value::get_message() const
    {
        return detail::string_access::make([&] { return detail::error_value_access::get(*this).get_message(); });
    }
    int64 error_value::get_line() const noexcept
    {
        return int64(detail::error_value_access::get(*this).get_line().native_value());
    }
    string error_value::get_file() const
    {
        return detail::string_access::make([&] { return detail::error_value_access::get(*this).get_file(); });
    }
}
