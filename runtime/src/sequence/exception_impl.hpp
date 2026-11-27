#include <scpp/exception.hpp>
#include <stdexcept>
#include <utility>

namespace scpp {
namespace {
struct exception_allocation final : application_control {
    union { exception_payload value; };

    exception_allocation(string message, __INT64_TYPE__ line, string file)
        : application_control(destroy_value, dispose), value(std::move(message), line, std::move(file)) {}
    ~exception_allocation() {}

    static void destroy_value(application_control* allocation) noexcept {
        static_cast<exception_allocation*>(allocation)->value.~exception_payload();
    }
    static void dispose(application_control* allocation) noexcept {
        delete static_cast<exception_allocation*>(allocation);
    }
};
}

exception::exception(const exception& other) noexcept : ptr(other.ptr), view(other.view) {
    if (ptr) ptr->retain_strong();
}

exception::exception(exception&& other) noexcept : ptr(other.ptr), view(other.view) {
    other.ptr = nullptr;
    other.view = nullptr;
}

exception& exception::operator=(exception other) noexcept {
    auto* old_ptr = ptr;
    auto* old_view = view;
    ptr = other.ptr;
    view = other.view;
    other.ptr = old_ptr;
    other.view = old_view;
    return *this;
}

exception::~exception() {
    if (ptr) ptr->release_strong();
}

const exception exception::no_error{};

exception_payload::exception_payload(string message, __INT64_TYPE__ line, string file)
    : message_(std::move(message)), file_(std::move(file)), line_(line) {}

namespace {
thread_local const exception_site* pending_site{};
}

exception_site::scope::scope(const exception_site& site) noexcept : previous(pending_site) {
    pending_site = &site;
}

exception_site::scope::~scope() { pending_site = previous; }

exception_payload::exception_payload() {
    if (auto* site = std::exchange(pending_site, nullptr)) {
        file_ = string{site->file};
        line_ = site->line;
    }
}

exception::exception(string message, __INT64_TYPE__ line, string file) {
    auto* allocation = new exception_allocation(std::move(message), line, std::move(file));
    ptr = allocation;
    view = &allocation->value;
}

string exception::get_message() const {
    if (!ptr) throw std::runtime_error("ownership_empty_access");
    return view->message();
}

string exception::get_file() const { return view ? view->file() : string{}; }
__INT64_TYPE__ exception::get_line() const noexcept { return view ? view->get_line() : 0; }

exception make_exception(string message, __INT64_TYPE__ line, const char* file) {
    return exception(std::move(message), line, string{file});
}

void throw_exception(exception value) {
    if (!value.ptr) throw std::runtime_error("absent_exception");
    throw value;
}
}
