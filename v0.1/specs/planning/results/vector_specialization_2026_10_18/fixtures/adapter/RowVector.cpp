#include "RowVector.hpp"
#include <scpp/lang/php.hpp>
struct RowVectorState { scpp::vector_t<Row> values; };
RowVector::RowVector() : state_(new RowVectorState) {}
RowVector::RowVector(const RowVector& x) : state_(x.state_ ? new RowVectorState(*x.state_) : new RowVectorState) {}
RowVector::RowVector(RowVector&& x) noexcept : state_(x.state_) { x.state_=nullptr; }
RowVector& RowVector::operator=(const RowVector& x) {
    if(this != &x) { RowVector next(x); auto *old=state_; state_=next.state_; next.state_=old; } return *this;
}
RowVector& RowVector::operator=(RowVector&& x) noexcept {
    if(this != &x) { delete state_; state_=x.state_; x.state_=nullptr; } return *this;
}
RowVector::~RowVector() { delete state_; }
size_t RowVector::size() const { return state_ ? state_->values.size() : 0; }
void RowVector::append(Row v) { if(!state_) state_=new RowVectorState; state_->values.append(static_cast<Row&&>(v)); }
Row RowVector::get(size_t i) const { if(i>=size()) throw BoundsError{}; return state_->values.at(i); }
void RowVector::set(size_t i, Row v) { if(i>=size()) throw BoundsError{}; state_->values.at(i)=static_cast<Row&&>(v); }
