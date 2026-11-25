// Linked only into allocation-count runs. Counts requested C++ heap bytes.
#include <cstdlib>
#include <cstddef>
#include <new>
std::size_t calls, requested;
static void* allocate(std::size_t size, std::size_t alignment) {
    void* p = nullptr;
    if (alignment <= alignof(std::max_align_t)) p = std::malloc(size ? size : 1);
    else if (posix_memalign(&p, alignment, size ? size : 1)) p = nullptr;
    if (!p) throw std::bad_alloc();
    ++calls;
    requested += size;
    return p;
}
#define HOOK(op, del) \
void* operator op(std::size_t n) { return allocate(n, 1); } \
void* operator op(std::size_t n, std::align_val_t a) { return allocate(n, static_cast<std::size_t>(a)); } \
void operator del(void* p) noexcept { std::free(p); } \
void operator del(void* p, std::size_t) noexcept { std::free(p); } \
void operator del(void* p, std::align_val_t) noexcept { std::free(p); } \
void operator del(void* p, std::size_t, std::align_val_t) noexcept { std::free(p); }
HOOK(new, delete)
HOOK(new[], delete[])
