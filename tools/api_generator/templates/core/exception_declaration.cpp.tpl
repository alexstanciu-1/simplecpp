#pragma once

#include <@string_header@>
#include <scpp/owner_access.hpp>

namespace scpp {

class exception_payload {
    string message_{"Exception", 9};

    string file_;
    __INT64_TYPE__ line_{};

public:
    exception_payload();
    exception_payload(string message, __INT64_TYPE__ line, string file);
    const string& file() const noexcept { return file_; }
    __INT64_TYPE__ get_line() const noexcept { return line_; }
    string get_file() const { return file_; }
    virtual ~exception_payload() noexcept = default;

    const string& message() const noexcept { return message_; }
    string get_message() const { return message_; }
};

// One owning view of a runtime or source allocation. Catch matching uses the
// polymorphic payload; the control block keeps the original allocation alive.
struct exception {
    application_control* ptr{};
    exception_payload* view{};

    exception() noexcept = default;
    exception(const exception& other) noexcept;
    exception(exception&& other) noexcept;
    exception& operator=(exception other) noexcept;
    ~exception();

    explicit exception(string message, __INT64_TYPE__ line = 0, string file = {});
@fences@    static const exception no_error;
    bool has_error() const noexcept { return ptr != nullptr; }
    explicit operator bool() const noexcept { return has_error(); }
    bool same_identity(const exception& other) const noexcept;
    string get_message() const;
    string get_file() const;
    __INT64_TYPE__ get_line() const noexcept;
};

// Source constructors keep their normal signature. Their runtime base consumes the
// creation site before source fields or constructor statements observe the object.
struct exception_site {
    __INT64_TYPE__ line;
    const char* file;

    struct scope {
        const exception_site* previous;
        explicit scope(const exception_site& site) noexcept;
        ~scope();
    };
};

exception make_exception(string message, __INT64_TYPE__ line = 0, const char* file = "");
[[noreturn]] void throw_exception(exception value);

}
