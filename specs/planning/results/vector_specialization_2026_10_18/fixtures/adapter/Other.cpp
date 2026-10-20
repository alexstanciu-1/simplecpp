#include "Other.hpp"
#include "Other_payload.hpp"
struct OtherState { scpp::shared_p<OtherPayload> ptr; };
Other::Other(int64_t v) : state_(new OtherState{scpp::shared_p<OtherPayload>(std::make_shared<OtherPayload>(v))}) {}
Other::Other(const Other& x) : state_(x.state_ ? new OtherState{x.state_->ptr} : nullptr) {}
Other::Other(Other&& x) noexcept : state_(x.state_) { x.state_ = nullptr; }
Other& Other::operator=(const Other& x) {
    if (this != &x) { auto *next = x.state_ ? new OtherState{x.state_->ptr} : nullptr; delete state_; state_ = next; }
    return *this;
}
Other& Other::operator=(Other&& x) noexcept {
    if (this != &x) { delete state_; state_ = x.state_; x.state_ = nullptr; } return *this;
}
Other::~Other() { delete state_; }
int64_t Other::value() const { return state_->ptr->value(); }
void Other::value(int64_t v) { state_->ptr->value_ = v; }
bool Other::same(const Other& x) const { return state_ && x.state_ && state_->ptr.get() == x.state_->ptr.get(); }
int Other::live() { return OtherPayload::live; }
int Other::made() { return OtherPayload::made; }
int Other::dropped() { return OtherPayload::dropped; }
