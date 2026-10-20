#include "Row.hpp"
#include "Row_payload.hpp"
static void destroy_Row(void *value) { delete static_cast<RowPayload*>(value); }
Row::Row(int64_t v) : state_(owner_create(new RowPayload(v), destroy_Row)) {}
Row::Row(const Row& x) : state_(owner_copy(x.state_)) {}
Row::Row(Row&& x) noexcept : state_(x.state_) { x.state_=nullptr; }
Row& Row::operator=(const Row& x) {
    if(this!=&x) { auto *next=owner_copy(x.state_); owner_drop(state_); state_=next; } return *this;
}
Row& Row::operator=(Row&& x) noexcept {
    if(this!=&x) { owner_drop(state_); state_=x.state_; x.state_=nullptr; } return *this;
}
Row::~Row() { owner_drop(state_); }
int64_t Row::value() const { return static_cast<RowPayload*>(owner_get(state_))->value(); }
void Row::value(int64_t v) { static_cast<RowPayload*>(owner_get(state_))->value_=v; }
bool Row::same(const Row& x) const { return state_ && x.state_ && owner_get(state_)==owner_get(x.state_); }
int Row::live() { return RowPayload::live; }
int Row::made() { return RowPayload::made; }
int Row::dropped() { return RowPayload::dropped; }
