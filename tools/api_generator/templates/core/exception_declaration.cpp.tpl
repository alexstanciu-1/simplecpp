#pragma once

#include <@string_header@>
#include <scpp/owner_access.hpp>

namespace scpp {

class exception_payload {
    string message_{"Exception", 9};

public:
    exception_payload() = default;
    explicit exception_payload(const string& message) : message_(message) {}
    virtual ~exception_payload() noexcept = default;

    const string& message() const noexcept { return message_; }
    string get_message() const { return message_; }
};

// One owning view of a runtime or source allocation. Catch matching uses the
// polymorphic payload; the control block keeps the original allocation alive.
struct exception_owner {
    application_control* ptr{};
    exception_payload* view{};

    exception_owner() noexcept = default;
    exception_owner(const exception_owner& other) noexcept;
    exception_owner(exception_owner&& other) noexcept;
    exception_owner& operator=(exception_owner other) noexcept;
    ~exception_owner();

    string get_message() const;
};

exception_owner make_exception(const string& message);
[[noreturn]] void throw_exception(exception_owner value);

}
