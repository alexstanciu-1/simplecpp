#include "OtherVector.hpp"
#include <scpp/lang/php.hpp>
struct OtherVectorState { scpp::vector_t<Other> values; };
OtherVector::OtherVector() : state_(new OtherVectorState) {}
OtherVector::OtherVector(const OtherVector& x) : state_(x.state_ ? new OtherVectorState(*x.state_) : new OtherVectorState) {}
OtherVector::OtherVector(OtherVector&& x) noexcept : state_(x.state_) { x.state_=nullptr; }
OtherVector& OtherVector::operator=(const OtherVector& x) {
    if(this != &x) { OtherVector next(x); auto *old=state_; state_=next.state_; next.state_=old; } return *this;
}
OtherVector& OtherVector::operator=(OtherVector&& x) noexcept {
    if(this != &x) { delete state_; state_=x.state_; x.state_=nullptr; } return *this;
}
OtherVector::~OtherVector() { delete state_; }
size_t OtherVector::size() const { return state_ ? state_->values.size() : 0; }
void OtherVector::append(Other v) { if(!state_) state_=new OtherVectorState; state_->values.append(static_cast<Other&&>(v)); }
Other OtherVector::get(size_t i) const { if(i>=size()) throw BoundsError{}; return state_->values.at(i); }
void OtherVector::set(size_t i, Other v) { if(i>=size()) throw BoundsError{}; state_->values.at(i)=static_cast<Other&&>(v); }
