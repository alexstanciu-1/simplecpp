#pragma once
namespace scpp {
[[noreturn]] void throw_empty_owner();
[[noreturn]] void throw_invalid_object_cast();
// Thread-confined control shared by every typed view of one application allocation.
// Retaining and observing counts stay inline; last-owner cleanup lives in the runtime.
struct application_control {
    __UINT32_TYPE__ count{1};
    __UINT32_TYPE__ weak_count{1};
    void (*destroy)(application_control*) noexcept;
    void (*dispose)(application_control*) noexcept;
    application_control(void (*destroy_value)(application_control*) noexcept,
                        void (*dispose_allocation)(application_control*) noexcept) noexcept
        : destroy(destroy_value), dispose(dispose_allocation) {}
    void retain_strong() noexcept {
        if (count == __UINT32_MAX__) __builtin_trap();
        ++count;
    }
    void retain_weak() noexcept {
        if (weak_count == __UINT32_MAX__) __builtin_trap();
        ++weak_count;
    }
    void release_strong() noexcept { if (--count == 0) release_value(); }
    void release_weak() noexcept { if (--weak_count == 0) dispose(this); }
private:
    void release_value() noexcept;
};
}
