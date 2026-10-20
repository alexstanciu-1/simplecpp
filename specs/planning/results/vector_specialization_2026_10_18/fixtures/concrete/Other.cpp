#include "Other.hpp"
#include "Other_payload.hpp"
static void destroy_Other(void *value) { delete static_cast<OtherPayload*>(value); }
Other::Other(int64_t v) : state_(owner_create(new OtherPayload(v), destroy_Other)) {}
Other::Other(const Other& x) : state_(owner_copy(x.state_)) {}
Other::Other(Other&& x) noexcept : state_(x.state_) { x.state_=nullptr; }
Other& Other::operator=(const Other& x) {
    if(this!=&x) { auto *next=owner_copy(x.state_); owner_drop(state_); state_=next; } return *this;
}
Other& Other::operator=(Other&& x) noexcept {
    if(this!=&x) { owner_drop(state_); state_=x.state_; x.state_=nullptr; } return *this;
}
Other::~Other() { owner_drop(state_); }
int64_t Other::value() const { return static_cast<OtherPayload*>(owner_get(state_))->value(); }
void Other::value(int64_t v) { static_cast<OtherPayload*>(owner_get(state_))->value_=v; }
bool Other::same(const Other& x) const { return state_ && x.state_ && owner_get(state_)==owner_get(x.state_); }
int Other::live() { return OtherPayload::live; }
int Other::made() { return OtherPayload::made; }
int Other::dropped() { return OtherPayload::dropped; }
