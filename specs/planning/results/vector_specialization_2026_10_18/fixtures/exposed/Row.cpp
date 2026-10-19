#include "Row.hpp"
#include "Row_payload.hpp"
struct RowState { scpp::shared_p<RowPayload> ptr; };
Row::Row(int64_t v) : state_(new RowState{scpp::shared_p<RowPayload>(std::make_shared<RowPayload>(v))}) {}
Row::Row(const Row& x) : state_(x.state_ ? new RowState{x.state_->ptr} : nullptr) {}
Row::Row(Row&& x) noexcept : state_(x.state_) { x.state_ = nullptr; }
Row& Row::operator=(const Row& x) {
    if (this != &x) { auto *next = x.state_ ? new RowState{x.state_->ptr} : nullptr; delete state_; state_ = next; }
    return *this;
}
Row& Row::operator=(Row&& x) noexcept {
    if (this != &x) { delete state_; state_ = x.state_; x.state_ = nullptr; } return *this;
}
Row::~Row() { delete state_; }
int64_t Row::value() const { return state_->ptr->value(); }
void Row::value(int64_t v) { state_->ptr->value_ = v; }
bool Row::same(const Row& x) const { return state_ && x.state_ && state_->ptr.get() == x.state_->ptr.get(); }
int Row::live() { return RowPayload::live; }
int Row::made() { return RowPayload::made; }
int Row::dropped() { return RowPayload::dropped; }
