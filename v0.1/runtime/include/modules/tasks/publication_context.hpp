#pragma once

#include <mutex>
#include <stdexcept>
#include <utility>

namespace scpp::tasks::detail {

// Only the currently executing unordered task batch owns this lock. It never escapes.
inline thread_local std::mutex *active_publication = nullptr;

class publication_context {
    std::mutex *previous;
public:
    explicit publication_context(std::mutex *current) : previous(active_publication) {
        active_publication = current;
    }
    ~publication_context() { active_publication = previous; }
    publication_context(const publication_context &) = delete;
    publication_context &operator=(const publication_context &) = delete;
};

template <typename Callback>
void synchronized_publication(Callback &&callback) {
    auto *mutex = active_publication;
    if (mutex == nullptr) {
        throw std::logic_error("task_synchronize requires an unordered work callback; nesting is unsupported");
    }
    std::lock_guard<std::mutex> guard(*mutex);
    publication_context unavailable(nullptr);
    std::forward<Callback>(callback)();
}

} // namespace scpp::tasks::detail
