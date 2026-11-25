#include "scpp/detail/exception_info_access.hpp"
#include "scpp/detail/string_access.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/failure.hpp"
#include <limits>

namespace scpp
{
    // Observe within the handler: some C++ implementations may copy on rethrow.
    // Only owned results escape; never return a reference to the caught object.
    template<class Operation>
    static auto inspect_exception(const exception_info &info, const Operation &operation)
    {
        try { info.rethrow(); }
        catch (const runtime_error &error) {
            static_assert(!std::is_reference_v<decltype(operation(error))>);
            return operation(error);
        }
    }

    static int64 exception_count(std::size_t count)
    {
        if (count > static_cast<std::size_t>(std::numeric_limits<__INT64_TYPE__>::max())) {
            raise_runtime_error("Exception collection exceeds index range", "size_out_of_range", "core", "exception_info");
        }
        return int64(static_cast<__INT64_TYPE__>(count));
    }

    static std::size_t exception_index(int64 index, std::size_t count)
    {
        if (index.native_value() < 0 || static_cast<__UINT64_TYPE__>(index.native_value()) >= count) {
            raise_runtime_error("Exception index out of range", "index_out_of_range", "core", "exception_info");
        }
        return static_cast<std::size_t>(index.native_value());
    }

    exception_info::exception_info(void (*construct)(void *, const void *), const void *context)
    {
        construct(this->storage_, context);
    }
    exception_info::exception_info(const exception_info &other) noexcept
    {
        ::new (this->storage_) std::exception_ptr(detail::exception_info_access::get(other));
    }
    exception_info &exception_info::operator=(const exception_info &other) noexcept
    {
        detail::exception_info_access::get(*this) = detail::exception_info_access::get(other);
        return *this;
    }
    exception_info::~exception_info() noexcept
    {
        detail::exception_info_access::get(*this).~exception_ptr();
    }

    exception_info capture_runtime_exception()
    {
        const auto retained = std::current_exception();
        if (!retained) {
            raise_runtime_error("No active exception to capture", "no_active_exception", "core", "capture_runtime_exception");
        }
        // A foreign exception propagates without wrapping or conversion.
        try { std::rethrow_exception(retained); }
        catch (const runtime_error &) {
            return exception_info([](void *storage, const void *context) {
                ::new (storage) std::exception_ptr(*static_cast<const std::exception_ptr *>(context));
            }, &retained);
        }
    }
    void exception_info::rethrow() const { std::rethrow_exception(detail::exception_info_access::get(*this)); }
    string exception_info::message() const
    {
        return inspect_exception(*this, [](const runtime_error &error) { return string(error.what()); });
    }
    string exception_info::code() const
    {
        return inspect_exception(*this, [](const runtime_error &error) {
            return detail::string_access::make([&] { return string_t(error.code()); });
        });
    }
    string exception_info::component() const
    {
        return inspect_exception(*this, [](const runtime_error &error) {
            return detail::string_access::make([&] { return string_t(error.component()); });
        });
    }
    string exception_info::operation() const
    {
        return inspect_exception(*this, [](const runtime_error &error) {
            return detail::string_access::make([&] { return string_t(error.operator_symbol()); });
        });
    }
    int64 exception_info::detail_count() const
    {
        return inspect_exception(*this, [](const runtime_error &error) { return exception_count(error.details().size()); });
    }
    int64 exception_info::trace_count() const
    {
        return inspect_exception(*this, [](const runtime_error &error) { return exception_count(error.trace().size()); });
    }
    string exception_info::detail_key(int64 index) const
    {
        return inspect_exception(*this, [&](const runtime_error &error) {
            const auto &items = error.details();
            const auto position = exception_index(index, items.size());
            return detail::string_access::make([&] { return string_t(items[position].key); });
        });
    }
    string exception_info::detail_value(int64 index) const
    {
        return inspect_exception(*this, [&](const runtime_error &error) {
            const auto &items = error.details();
            const auto position = exception_index(index, items.size());
            return detail::string_access::make([&] { return string_t(items[position].value); });
        });
    }
    string exception_info::trace_module(int64 index) const
    {
        return inspect_exception(*this, [&](const runtime_error &error) {
            const auto &items = error.trace();
            const auto position = exception_index(index, items.size());
            return detail::string_access::make([&] { return string_t(items[position].module_path); });
        });
    }
    string exception_info::trace_address(int64 index) const
    {
        return inspect_exception(*this, [&](const runtime_error &error) {
            const auto &items = error.trace();
            const auto position = exception_index(index, items.size());
            return detail::string_access::make([&] { return string_t(items[position].relative_address); });
        });
    }
}
