#include "concrete.hpp"
namespace scpp {
H_I_string_t::H_I_string_t(const H_I_string_t& x) {
    auto *next=new E_H_I_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_I_string_t::H_I_string_t(H_I_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_I_string_t& H_I_string_t::operator=(const H_I_string_t& x) {if(this!=&x) { H_I_string_t next(x);*this=static_cast<H_I_string_t&&>(next); }return *this;}
H_I_string_t& H_I_string_t::operator=(H_I_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_I_string_t::~H_I_string_t() {delete[] data_;delete[] buckets_;}
void H_I_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_I_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_I_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_I_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_I_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_I_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_I_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
I& H_I_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const I& H_I_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
I& H_I_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_I_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=I{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_AccrualRecord_string_t::H_P_AccrualRecord_string_t(const H_P_AccrualRecord_string_t& x) {
    auto *next=new E_H_P_AccrualRecord_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_AccrualRecord_string_t::H_P_AccrualRecord_string_t(H_P_AccrualRecord_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_AccrualRecord_string_t& H_P_AccrualRecord_string_t::operator=(const H_P_AccrualRecord_string_t& x) {if(this!=&x) { H_P_AccrualRecord_string_t next(x);*this=static_cast<H_P_AccrualRecord_string_t&&>(next); }return *this;}
H_P_AccrualRecord_string_t& H_P_AccrualRecord_string_t::operator=(H_P_AccrualRecord_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_AccrualRecord_string_t::~H_P_AccrualRecord_string_t() {delete[] data_;delete[] buckets_;}
void H_P_AccrualRecord_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_AccrualRecord_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_AccrualRecord_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_AccrualRecord_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_AccrualRecord_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_AccrualRecord_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_AccrualRecord_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_AccrualRecord& H_P_AccrualRecord_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_AccrualRecord& H_P_AccrualRecord_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_AccrualRecord& H_P_AccrualRecord_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_AccrualRecord_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_AccrualRecord{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_BankMatch_string_t::H_P_BankMatch_string_t(const H_P_BankMatch_string_t& x) {
    auto *next=new E_H_P_BankMatch_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_BankMatch_string_t::H_P_BankMatch_string_t(H_P_BankMatch_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_BankMatch_string_t& H_P_BankMatch_string_t::operator=(const H_P_BankMatch_string_t& x) {if(this!=&x) { H_P_BankMatch_string_t next(x);*this=static_cast<H_P_BankMatch_string_t&&>(next); }return *this;}
H_P_BankMatch_string_t& H_P_BankMatch_string_t::operator=(H_P_BankMatch_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_BankMatch_string_t::~H_P_BankMatch_string_t() {delete[] data_;delete[] buckets_;}
void H_P_BankMatch_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_BankMatch_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_BankMatch_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_BankMatch_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_BankMatch_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_BankMatch_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_BankMatch_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_BankMatch& H_P_BankMatch_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_BankMatch& H_P_BankMatch_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_BankMatch& H_P_BankMatch_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_BankMatch_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_BankMatch{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_BankStatementLine_string_t::H_P_BankStatementLine_string_t(const H_P_BankStatementLine_string_t& x) {
    auto *next=new E_H_P_BankStatementLine_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_BankStatementLine_string_t::H_P_BankStatementLine_string_t(H_P_BankStatementLine_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_BankStatementLine_string_t& H_P_BankStatementLine_string_t::operator=(const H_P_BankStatementLine_string_t& x) {if(this!=&x) { H_P_BankStatementLine_string_t next(x);*this=static_cast<H_P_BankStatementLine_string_t&&>(next); }return *this;}
H_P_BankStatementLine_string_t& H_P_BankStatementLine_string_t::operator=(H_P_BankStatementLine_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_BankStatementLine_string_t::~H_P_BankStatementLine_string_t() {delete[] data_;delete[] buckets_;}
void H_P_BankStatementLine_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_BankStatementLine_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_BankStatementLine_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_BankStatementLine_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_BankStatementLine_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_BankStatementLine_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_BankStatementLine_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_BankStatementLine& H_P_BankStatementLine_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_BankStatementLine& H_P_BankStatementLine_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_BankStatementLine& H_P_BankStatementLine_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_BankStatementLine_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_BankStatementLine{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_FixedAsset_string_t::H_P_FixedAsset_string_t(const H_P_FixedAsset_string_t& x) {
    auto *next=new E_H_P_FixedAsset_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_FixedAsset_string_t::H_P_FixedAsset_string_t(H_P_FixedAsset_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_FixedAsset_string_t& H_P_FixedAsset_string_t::operator=(const H_P_FixedAsset_string_t& x) {if(this!=&x) { H_P_FixedAsset_string_t next(x);*this=static_cast<H_P_FixedAsset_string_t&&>(next); }return *this;}
H_P_FixedAsset_string_t& H_P_FixedAsset_string_t::operator=(H_P_FixedAsset_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_FixedAsset_string_t::~H_P_FixedAsset_string_t() {delete[] data_;delete[] buckets_;}
void H_P_FixedAsset_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_FixedAsset_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_FixedAsset_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_FixedAsset_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_FixedAsset_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_FixedAsset_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_FixedAsset_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_FixedAsset& H_P_FixedAsset_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_FixedAsset& H_P_FixedAsset_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_FixedAsset& H_P_FixedAsset_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_FixedAsset_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_FixedAsset{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_LedgerAccount_string_t::H_P_LedgerAccount_string_t(const H_P_LedgerAccount_string_t& x) {
    auto *next=new E_H_P_LedgerAccount_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_LedgerAccount_string_t::H_P_LedgerAccount_string_t(H_P_LedgerAccount_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_LedgerAccount_string_t& H_P_LedgerAccount_string_t::operator=(const H_P_LedgerAccount_string_t& x) {if(this!=&x) { H_P_LedgerAccount_string_t next(x);*this=static_cast<H_P_LedgerAccount_string_t&&>(next); }return *this;}
H_P_LedgerAccount_string_t& H_P_LedgerAccount_string_t::operator=(H_P_LedgerAccount_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_LedgerAccount_string_t::~H_P_LedgerAccount_string_t() {delete[] data_;delete[] buckets_;}
void H_P_LedgerAccount_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_LedgerAccount_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_LedgerAccount_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_LedgerAccount_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_LedgerAccount_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_LedgerAccount_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_LedgerAccount_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_LedgerAccount& H_P_LedgerAccount_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_LedgerAccount& H_P_LedgerAccount_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_LedgerAccount& H_P_LedgerAccount_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_LedgerAccount_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_LedgerAccount{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_PayableRecord_string_t::H_P_PayableRecord_string_t(const H_P_PayableRecord_string_t& x) {
    auto *next=new E_H_P_PayableRecord_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_PayableRecord_string_t::H_P_PayableRecord_string_t(H_P_PayableRecord_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_PayableRecord_string_t& H_P_PayableRecord_string_t::operator=(const H_P_PayableRecord_string_t& x) {if(this!=&x) { H_P_PayableRecord_string_t next(x);*this=static_cast<H_P_PayableRecord_string_t&&>(next); }return *this;}
H_P_PayableRecord_string_t& H_P_PayableRecord_string_t::operator=(H_P_PayableRecord_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_PayableRecord_string_t::~H_P_PayableRecord_string_t() {delete[] data_;delete[] buckets_;}
void H_P_PayableRecord_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_PayableRecord_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_PayableRecord_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_PayableRecord_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_PayableRecord_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_PayableRecord_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_PayableRecord_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_PayableRecord& H_P_PayableRecord_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_PayableRecord& H_P_PayableRecord_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_PayableRecord& H_P_PayableRecord_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_PayableRecord_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_PayableRecord{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_PrepaymentSchedule_string_t::H_P_PrepaymentSchedule_string_t(const H_P_PrepaymentSchedule_string_t& x) {
    auto *next=new E_H_P_PrepaymentSchedule_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_PrepaymentSchedule_string_t::H_P_PrepaymentSchedule_string_t(H_P_PrepaymentSchedule_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_PrepaymentSchedule_string_t& H_P_PrepaymentSchedule_string_t::operator=(const H_P_PrepaymentSchedule_string_t& x) {if(this!=&x) { H_P_PrepaymentSchedule_string_t next(x);*this=static_cast<H_P_PrepaymentSchedule_string_t&&>(next); }return *this;}
H_P_PrepaymentSchedule_string_t& H_P_PrepaymentSchedule_string_t::operator=(H_P_PrepaymentSchedule_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_PrepaymentSchedule_string_t::~H_P_PrepaymentSchedule_string_t() {delete[] data_;delete[] buckets_;}
void H_P_PrepaymentSchedule_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_PrepaymentSchedule_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_PrepaymentSchedule_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_PrepaymentSchedule_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_PrepaymentSchedule_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_PrepaymentSchedule_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_PrepaymentSchedule_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_PrepaymentSchedule& H_P_PrepaymentSchedule_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_PrepaymentSchedule& H_P_PrepaymentSchedule_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_PrepaymentSchedule& H_P_PrepaymentSchedule_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_PrepaymentSchedule_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_PrepaymentSchedule{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_SalesInvoice_string_t::H_P_SalesInvoice_string_t(const H_P_SalesInvoice_string_t& x) {
    auto *next=new E_H_P_SalesInvoice_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_SalesInvoice_string_t::H_P_SalesInvoice_string_t(H_P_SalesInvoice_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_SalesInvoice_string_t& H_P_SalesInvoice_string_t::operator=(const H_P_SalesInvoice_string_t& x) {if(this!=&x) { H_P_SalesInvoice_string_t next(x);*this=static_cast<H_P_SalesInvoice_string_t&&>(next); }return *this;}
H_P_SalesInvoice_string_t& H_P_SalesInvoice_string_t::operator=(H_P_SalesInvoice_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_SalesInvoice_string_t::~H_P_SalesInvoice_string_t() {delete[] data_;delete[] buckets_;}
void H_P_SalesInvoice_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_SalesInvoice_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_SalesInvoice_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_SalesInvoice_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_SalesInvoice_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_SalesInvoice_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_SalesInvoice_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_SalesInvoice& H_P_SalesInvoice_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_SalesInvoice& H_P_SalesInvoice_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_SalesInvoice& H_P_SalesInvoice_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_SalesInvoice_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_SalesInvoice{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_StockItem_string_t::H_P_StockItem_string_t(const H_P_StockItem_string_t& x) {
    auto *next=new E_H_P_StockItem_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_StockItem_string_t::H_P_StockItem_string_t(H_P_StockItem_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_StockItem_string_t& H_P_StockItem_string_t::operator=(const H_P_StockItem_string_t& x) {if(this!=&x) { H_P_StockItem_string_t next(x);*this=static_cast<H_P_StockItem_string_t&&>(next); }return *this;}
H_P_StockItem_string_t& H_P_StockItem_string_t::operator=(H_P_StockItem_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_StockItem_string_t::~H_P_StockItem_string_t() {delete[] data_;delete[] buckets_;}
void H_P_StockItem_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_StockItem_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_StockItem_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_StockItem_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_StockItem_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_StockItem_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_StockItem_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_StockItem& H_P_StockItem_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_StockItem& H_P_StockItem_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_StockItem& H_P_StockItem_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_StockItem_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_StockItem{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_StockReservation_string_t::H_P_StockReservation_string_t(const H_P_StockReservation_string_t& x) {
    auto *next=new E_H_P_StockReservation_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_StockReservation_string_t::H_P_StockReservation_string_t(H_P_StockReservation_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_StockReservation_string_t& H_P_StockReservation_string_t::operator=(const H_P_StockReservation_string_t& x) {if(this!=&x) { H_P_StockReservation_string_t next(x);*this=static_cast<H_P_StockReservation_string_t&&>(next); }return *this;}
H_P_StockReservation_string_t& H_P_StockReservation_string_t::operator=(H_P_StockReservation_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_StockReservation_string_t::~H_P_StockReservation_string_t() {delete[] data_;delete[] buckets_;}
void H_P_StockReservation_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_StockReservation_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_StockReservation_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_StockReservation_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_StockReservation_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_StockReservation_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_StockReservation_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_StockReservation& H_P_StockReservation_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_StockReservation& H_P_StockReservation_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_StockReservation& H_P_StockReservation_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_StockReservation_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_StockReservation{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_P_SupplierRecord_string_t::H_P_SupplierRecord_string_t(const H_P_SupplierRecord_string_t& x) {
    auto *next=new E_H_P_SupplierRecord_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_P_SupplierRecord_string_t::H_P_SupplierRecord_string_t(H_P_SupplierRecord_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_P_SupplierRecord_string_t& H_P_SupplierRecord_string_t::operator=(const H_P_SupplierRecord_string_t& x) {if(this!=&x) { H_P_SupplierRecord_string_t next(x);*this=static_cast<H_P_SupplierRecord_string_t&&>(next); }return *this;}
H_P_SupplierRecord_string_t& H_P_SupplierRecord_string_t::operator=(H_P_SupplierRecord_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_P_SupplierRecord_string_t::~H_P_SupplierRecord_string_t() {delete[] data_;delete[] buckets_;}
void H_P_SupplierRecord_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_P_SupplierRecord_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_P_SupplierRecord_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_P_SupplierRecord_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_P_SupplierRecord_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_P_SupplierRecord_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_P_SupplierRecord_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
P_SupplierRecord& H_P_SupplierRecord_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const P_SupplierRecord& H_P_SupplierRecord_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
P_SupplierRecord& H_P_SupplierRecord_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_P_SupplierRecord_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=P_SupplierRecord{};data_[i].key=string_t{};--count_;return bool_t(true);
}
H_bool_t_I::H_bool_t_I(const H_bool_t_I& x) {
    auto *next=new E_H_bool_t_I[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_bool_t_I::H_bool_t_I(H_bool_t_I&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_bool_t_I& H_bool_t_I::operator=(const H_bool_t_I& x) {if(this!=&x) { H_bool_t_I next(x);*this=static_cast<H_bool_t_I&&>(next); }return *this;}
H_bool_t_I& H_bool_t_I::operator=(H_bool_t_I&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_bool_t_I::~H_bool_t_I() {delete[] data_;delete[] buckets_;}
void H_bool_t_I::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_bool_t_I)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_bool_t_I[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_bool_t_I&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_bool_t_I::find(const I& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_bool_t_I::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_bool_t_I::contains(const I& k) const {return find(k)!=SIZE_MAX;}
bool_t& H_bool_t_I::at(const I& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const bool_t& H_bool_t_I::at(const I& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
bool_t& H_bool_t_I::operator[](const I& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    I stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<I&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_bool_t_I::remove(const I& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=bool_t{};data_[i].key=I{};--count_;return bool_t(true);
}
H_bool_t_string_t::H_bool_t_string_t(const H_bool_t_string_t& x) {
    auto *next=new E_H_bool_t_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}
}
H_bool_t_string_t::H_bool_t_string_t(H_bool_t_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_),count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_) {x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}
H_bool_t_string_t& H_bool_t_string_t::operator=(const H_bool_t_string_t& x) {if(this!=&x) { H_bool_t_string_t next(x);*this=static_cast<H_bool_t_string_t&&>(next); }return *this;}
H_bool_t_string_t& H_bool_t_string_t::operator=(H_bool_t_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;delete[] buckets_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;
    x.data_=nullptr;x.used_=x.capacity_=0;x.count_=x.bucket_count_=0;x.buckets_=nullptr;}return *this;
}
H_bool_t_string_t::~H_bool_t_string_t() {delete[] data_;delete[] buckets_;}
void H_bool_t_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_H_bool_t_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_H_bool_t_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_H_bool_t_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
size_t H_bool_t_string_t::find(const string_t& k) const {
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}
    return SIZE_MAX;
}
void H_bool_t_string_t::rehash(size_t count) {
    auto *next=new size_t[count]{};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}
bool H_bool_t_string_t::contains(const string_t& k) const {return find(k)!=SIZE_MAX;}
bool_t& H_bool_t_string_t::at(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
const bool_t& H_bool_t_string_t::at(const string_t& k) const {size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}
bool_t& H_bool_t_string_t::operator[](const string_t& k) {
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    string_t stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}
    data_[used_].key=static_cast<string_t&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}
bool_t H_bool_t_string_t::remove(const string_t& k) {size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value=bool_t{};data_[i].key=string_t{};--count_;return bool_t(true);
}
V_I::V_I(const V_I& x) {
    auto *next=new E_V_I[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_I::V_I(V_I&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_I& V_I::operator=(const V_I& x) {if(this!=&x) { V_I next(x);*this=static_cast<V_I&&>(next); }return *this;}
V_I& V_I::operator=(V_I&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_I::~V_I() {delete[] data_;}
void V_I::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_I)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_I[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_I&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_I::push_back(I v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<I&&>(v);}
V_P_BankStatementLine::V_P_BankStatementLine(const V_P_BankStatementLine& x) {
    auto *next=new E_V_P_BankStatementLine[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_BankStatementLine::V_P_BankStatementLine(V_P_BankStatementLine&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_BankStatementLine& V_P_BankStatementLine::operator=(const V_P_BankStatementLine& x) {if(this!=&x) { V_P_BankStatementLine next(x);*this=static_cast<V_P_BankStatementLine&&>(next); }return *this;}
V_P_BankStatementLine& V_P_BankStatementLine::operator=(V_P_BankStatementLine&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_BankStatementLine::~V_P_BankStatementLine() {delete[] data_;}
void V_P_BankStatementLine::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_BankStatementLine)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_BankStatementLine[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_BankStatementLine&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_BankStatementLine::push_back(P_BankStatementLine v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_BankStatementLine&&>(v);}
V_P_BudgetLine::V_P_BudgetLine(const V_P_BudgetLine& x) {
    auto *next=new E_V_P_BudgetLine[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_BudgetLine::V_P_BudgetLine(V_P_BudgetLine&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_BudgetLine& V_P_BudgetLine::operator=(const V_P_BudgetLine& x) {if(this!=&x) { V_P_BudgetLine next(x);*this=static_cast<V_P_BudgetLine&&>(next); }return *this;}
V_P_BudgetLine& V_P_BudgetLine::operator=(V_P_BudgetLine&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_BudgetLine::~V_P_BudgetLine() {delete[] data_;}
void V_P_BudgetLine::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_BudgetLine)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_BudgetLine[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_BudgetLine&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_BudgetLine::push_back(P_BudgetLine v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_BudgetLine&&>(v);}
V_P_BudgetVarianceRow::V_P_BudgetVarianceRow(const V_P_BudgetVarianceRow& x) {
    auto *next=new E_V_P_BudgetVarianceRow[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_BudgetVarianceRow::V_P_BudgetVarianceRow(V_P_BudgetVarianceRow&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_BudgetVarianceRow& V_P_BudgetVarianceRow::operator=(const V_P_BudgetVarianceRow& x) {if(this!=&x) { V_P_BudgetVarianceRow next(x);*this=static_cast<V_P_BudgetVarianceRow&&>(next); }return *this;}
V_P_BudgetVarianceRow& V_P_BudgetVarianceRow::operator=(V_P_BudgetVarianceRow&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_BudgetVarianceRow::~V_P_BudgetVarianceRow() {delete[] data_;}
void V_P_BudgetVarianceRow::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_BudgetVarianceRow)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_BudgetVarianceRow[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_BudgetVarianceRow&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_BudgetVarianceRow::push_back(P_BudgetVarianceRow v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_BudgetVarianceRow&&>(v);}
V_P_JournalEntry::V_P_JournalEntry(const V_P_JournalEntry& x) {
    auto *next=new E_V_P_JournalEntry[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_JournalEntry::V_P_JournalEntry(V_P_JournalEntry&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_JournalEntry& V_P_JournalEntry::operator=(const V_P_JournalEntry& x) {if(this!=&x) { V_P_JournalEntry next(x);*this=static_cast<V_P_JournalEntry&&>(next); }return *this;}
V_P_JournalEntry& V_P_JournalEntry::operator=(V_P_JournalEntry&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_JournalEntry::~V_P_JournalEntry() {delete[] data_;}
void V_P_JournalEntry::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_JournalEntry)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_JournalEntry[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_JournalEntry&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_JournalEntry::push_back(P_JournalEntry v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_JournalEntry&&>(v);}
V_P_JournalLine::V_P_JournalLine(const V_P_JournalLine& x) {
    auto *next=new E_V_P_JournalLine[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_JournalLine::V_P_JournalLine(V_P_JournalLine&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_JournalLine& V_P_JournalLine::operator=(const V_P_JournalLine& x) {if(this!=&x) { V_P_JournalLine next(x);*this=static_cast<V_P_JournalLine&&>(next); }return *this;}
V_P_JournalLine& V_P_JournalLine::operator=(V_P_JournalLine&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_JournalLine::~V_P_JournalLine() {delete[] data_;}
void V_P_JournalLine::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_JournalLine)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_JournalLine[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_JournalLine&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_JournalLine::push_back(P_JournalLine v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_JournalLine&&>(v);}
V_P_PaymentProposalRow::V_P_PaymentProposalRow(const V_P_PaymentProposalRow& x) {
    auto *next=new E_V_P_PaymentProposalRow[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_PaymentProposalRow::V_P_PaymentProposalRow(V_P_PaymentProposalRow&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_PaymentProposalRow& V_P_PaymentProposalRow::operator=(const V_P_PaymentProposalRow& x) {if(this!=&x) { V_P_PaymentProposalRow next(x);*this=static_cast<V_P_PaymentProposalRow&&>(next); }return *this;}
V_P_PaymentProposalRow& V_P_PaymentProposalRow::operator=(V_P_PaymentProposalRow&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_PaymentProposalRow::~V_P_PaymentProposalRow() {delete[] data_;}
void V_P_PaymentProposalRow::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_PaymentProposalRow)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_PaymentProposalRow[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_PaymentProposalRow&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_PaymentProposalRow::push_back(P_PaymentProposalRow v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_PaymentProposalRow&&>(v);}
V_P_StockLot::V_P_StockLot(const V_P_StockLot& x) {
    auto *next=new E_V_P_StockLot[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_StockLot::V_P_StockLot(V_P_StockLot&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_StockLot& V_P_StockLot::operator=(const V_P_StockLot& x) {if(this!=&x) { V_P_StockLot next(x);*this=static_cast<V_P_StockLot&&>(next); }return *this;}
V_P_StockLot& V_P_StockLot::operator=(V_P_StockLot&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_StockLot::~V_P_StockLot() {delete[] data_;}
void V_P_StockLot::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_StockLot)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_StockLot[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_StockLot&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_StockLot::push_back(P_StockLot v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_StockLot&&>(v);}
V_P_TrialBalanceRow::V_P_TrialBalanceRow(const V_P_TrialBalanceRow& x) {
    auto *next=new E_V_P_TrialBalanceRow[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_P_TrialBalanceRow::V_P_TrialBalanceRow(V_P_TrialBalanceRow&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_P_TrialBalanceRow& V_P_TrialBalanceRow::operator=(const V_P_TrialBalanceRow& x) {if(this!=&x) { V_P_TrialBalanceRow next(x);*this=static_cast<V_P_TrialBalanceRow&&>(next); }return *this;}
V_P_TrialBalanceRow& V_P_TrialBalanceRow::operator=(V_P_TrialBalanceRow&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_P_TrialBalanceRow::~V_P_TrialBalanceRow() {delete[] data_;}
void V_P_TrialBalanceRow::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_P_TrialBalanceRow)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_P_TrialBalanceRow[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_P_TrialBalanceRow&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_P_TrialBalanceRow::push_back(P_TrialBalanceRow v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<P_TrialBalanceRow&&>(v);}
V_string_t::V_string_t(const V_string_t& x) {
    auto *next=new E_V_string_t[x.used_];
    try { for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; } catch(...) {delete[] next;throw;}
    data_=next;used_=capacity_=x.used_;
}
V_string_t::V_string_t(V_string_t&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_) {x.data_=nullptr;x.used_=x.capacity_=0;}
V_string_t& V_string_t::operator=(const V_string_t& x) {if(this!=&x) { V_string_t next(x);*this=static_cast<V_string_t&&>(next); }return *this;}
V_string_t& V_string_t::operator=(V_string_t&& x) noexcept {
    if(this!=&x) {delete[] data_;data_=x.data_;used_=x.used_;capacity_=x.capacity_;
    x.data_=nullptr;x.used_=x.capacity_=0;}return *this;
}
V_string_t::~V_string_t() {delete[] data_;}
void V_string_t::grow() {
    if(capacity_>SIZE_MAX/2/sizeof(E_V_string_t)) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_V_string_t[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_V_string_t&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}
void V_string_t::push_back(string_t v) {if(used_==capacity_) grow();data_[used_++].value=static_cast<string_t&&>(v);}
}
