#include "OtherVector.hpp"
#include <new>
OtherVector::OtherVector() = default;
OtherVector::OtherVector(const OtherVector& x) {
    auto *next = new Other[x.size_];
    try { for(size_t i=0;i<x.size_;++i) next[i]=x.data_[i]; } catch(...) { delete[] next; throw; }
    data_=next; size_=capacity_=x.size_;
}
OtherVector::OtherVector(OtherVector&& x) noexcept : data_(x.data_),size_(x.size_),capacity_(x.capacity_) {
    x.data_=nullptr; x.size_=x.capacity_=0;
}
OtherVector& OtherVector::operator=(const OtherVector& x) {
    if(this!=&x) { OtherVector next(x); *this=static_cast<OtherVector&&>(next); } return *this;
}
OtherVector& OtherVector::operator=(OtherVector&& x) noexcept {
    if(this!=&x) { delete[] data_; data_=x.data_; size_=x.size_; capacity_=x.capacity_; x.data_=nullptr; x.size_=x.capacity_=0; } return *this;
}
OtherVector::~OtherVector() { delete[] data_; }
size_t OtherVector::size() const { return size_; }
void OtherVector::append(Other v) {
    if(size_==capacity_) {
        if(capacity_ > SIZE_MAX / 2 / sizeof(Other)) throw std::bad_array_new_length();
        size_t capacity=capacity_ ? capacity_*2 : 4;
        auto *next=new Other[capacity];
        // Concrete handles have noexcept moves; allocation completes before mutation.
        for(size_t i=0;i<size_;++i) next[i]=static_cast<Other&&>(data_[i]);
        delete[] data_; data_=next; capacity_=capacity;
    }
    data_[size_++]=static_cast<Other&&>(v);
}
Other OtherVector::get(size_t i) const { if(i>=size_) throw BoundsError{}; return data_[i]; }
void OtherVector::set(size_t i, Other v) { if(i>=size_) throw BoundsError{}; data_[i]=static_cast<Other&&>(v); }
