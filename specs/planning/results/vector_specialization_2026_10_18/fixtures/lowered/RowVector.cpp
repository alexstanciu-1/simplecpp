#include "RowVector.hpp"
#include <new>
RowVector::RowVector() = default;
RowVector::RowVector(const RowVector& x) {
    auto *next = new Row[x.size_];
    try { for(size_t i=0;i<x.size_;++i) next[i]=x.data_[i]; } catch(...) { delete[] next; throw; }
    data_=next; size_=capacity_=x.size_;
}
RowVector::RowVector(RowVector&& x) noexcept : data_(x.data_),size_(x.size_),capacity_(x.capacity_) {
    x.data_=nullptr; x.size_=x.capacity_=0;
}
RowVector& RowVector::operator=(const RowVector& x) {
    if(this!=&x) { RowVector next(x); *this=static_cast<RowVector&&>(next); } return *this;
}
RowVector& RowVector::operator=(RowVector&& x) noexcept {
    if(this!=&x) { delete[] data_; data_=x.data_; size_=x.size_; capacity_=x.capacity_; x.data_=nullptr; x.size_=x.capacity_=0; } return *this;
}
RowVector::~RowVector() { delete[] data_; }
size_t RowVector::size() const { return size_; }
void RowVector::append(Row v) {
    if(size_==capacity_) {
        if(capacity_ > SIZE_MAX / 2 / sizeof(Row)) throw std::bad_array_new_length();
        size_t capacity=capacity_ ? capacity_*2 : 4;
        auto *next=new Row[capacity];
        // Concrete handles have noexcept moves; allocation completes before mutation.
        for(size_t i=0;i<size_;++i) next[i]=static_cast<Row&&>(data_[i]);
        delete[] data_; data_=next; capacity_=capacity;
    }
    data_[size_++]=static_cast<Row&&>(v);
}
Row RowVector::get(size_t i) const { if(i>=size_) throw BoundsError{}; return data_[i]; }
void RowVector::set(size_t i, Row v) { if(i>=size_) throw BoundsError{}; data_[i]=static_cast<Row&&>(v); }
