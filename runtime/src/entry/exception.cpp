#include <scpp/exception.hpp>
#include <stdexcept>

namespace scpp {
namespace {
struct exception_allocation final : application_control {
    union { exception_payload value; };

    explicit exception_allocation(const string& message)
        : application_control(destroy_value, dispose), value(message) {}
    ~exception_allocation() {}

    static void destroy_value(application_control* allocation) noexcept {
        static_cast<exception_allocation*>(allocation)->value.~exception_payload();
    }
    static void dispose(application_control* allocation) noexcept {
        delete static_cast<exception_allocation*>(allocation);
    }
};
}

exception_owner::exception_owner(const exception_owner& other) noexcept : ptr(other.ptr), view(other.view) {
    if (ptr) ptr->retain_strong();
}

exception_owner::exception_owner(exception_owner&& other) noexcept : ptr(other.ptr), view(other.view) {
    other.ptr = nullptr;
    other.view = nullptr;
}

exception_owner& exception_owner::operator=(exception_owner other) noexcept {
    auto* old_ptr = ptr;
    auto* old_view = view;
    ptr = other.ptr;
    view = other.view;
    other.ptr = old_ptr;
    other.view = old_view;
    return *this;
}

exception_owner::~exception_owner() {
    if (ptr) ptr->release_strong();
}

string exception_owner::get_message() const {
    if (!ptr) throw_empty_owner();
    return view->message();
}

exception_owner make_exception(const string& message) {
    auto* allocation = new exception_allocation(message);
    exception_owner result;
    result.ptr = allocation;
    result.view = &allocation->value;
    return result;
}

void throw_exception(exception_owner value) {
    if (!value.ptr) throw std::runtime_error("absent_exception");
    throw value;
}
}
