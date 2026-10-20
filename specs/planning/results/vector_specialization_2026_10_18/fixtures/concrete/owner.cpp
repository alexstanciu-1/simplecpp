#include "owner.hpp"
#include <memory>
struct SharedOwner { std::shared_ptr<void> ptr; };
SharedOwner *owner_create(void *value, void (*drop)(void *)) {
    // Establish cleanup before allocating the externally owned handle.
    std::shared_ptr<void> retained(value, drop);
    return new SharedOwner{retained};
}
SharedOwner *owner_copy(const SharedOwner *value) { return value ? new SharedOwner{value->ptr} : nullptr; }
void owner_drop(SharedOwner *value) { delete value; }
void *owner_get(const SharedOwner *value) { return value ? value->ptr.get() : nullptr; }
