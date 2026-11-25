#pragma once
#include <initializer_list>
#include <cstddef>
#include <cstdint>
#include "scpp/string.hpp"
namespace bench {
using I = scpp::int64;
struct owned_input {};
struct Counts { std::uint64_t allocations=0, bytes=0, live=0, peak=0, copies=0, moves=0, buffers=0, growth=0; };
#ifdef BENCH_INSTRUMENT
extern Counts counts;
extern bool counting;
void reset_counts();
extern long allocation_budget;
#endif
struct IVec {
    void* p=nullptr;
    IVec() noexcept;
    ~IVec();
    IVec(const IVec&); IVec(IVec&&) noexcept;
    IVec& operator=(const IVec&); IVec& operator=(IVec&&) noexcept;
    IVec(std::initializer_list<I>);
    IVec(const I*,std::size_t);
    IVec(owned_input,I*,std::size_t);
    static IVec from(std::initializer_list<I>);
    static IVec from(const I*,std::size_t);
    static IVec from_owned(I*,std::size_t);
    void reserve(std::size_t); void append(I);
    std::size_t capacity() const; std::size_t size() const; const I& at(std::size_t) const; I& at(std::size_t);
};
struct SVec {
    void* p=nullptr;
    SVec() noexcept;
    ~SVec();
    SVec(const SVec&); SVec(SVec&&) noexcept;
    SVec& operator=(const SVec&); SVec& operator=(SVec&&) noexcept;
    SVec(std::initializer_list<scpp::string>);
    SVec(const scpp::string*,std::size_t);
    SVec(owned_input,scpp::string*,std::size_t);
    static SVec from(std::initializer_list<scpp::string>);
    static SVec from(const scpp::string*,std::size_t);
    static SVec from_owned(scpp::string*,std::size_t);
    void reserve(std::size_t); void append(scpp::string);
    std::size_t capacity() const; std::size_t size() const; const scpp::string& at(std::size_t) const; scpp::string& at(std::size_t);
};
struct Matrix {
    void* p=nullptr;
    Matrix() noexcept;
    ~Matrix();
    Matrix(const Matrix&); Matrix(Matrix&&) noexcept;
    Matrix& operator=(const Matrix&); Matrix& operator=(Matrix&&) noexcept;
    Matrix(std::initializer_list<IVec>);
    Matrix(const IVec*,std::size_t);
    Matrix(owned_input,IVec*,std::size_t);
    static Matrix from(std::initializer_list<IVec>);
    static Matrix from(const IVec*,std::size_t);
    static Matrix from_owned(IVec*,std::size_t);
    void reserve(std::size_t); void append(IVec);
    std::size_t capacity() const; std::size_t size() const; const IVec& at(std::size_t) const; IVec& at(std::size_t);
};
std::uint64_t digest(const IVec&); std::uint64_t digest(const SVec&); std::uint64_t digest(const Matrix&);
std::uint64_t observe(const IVec&); std::uint64_t observe(const SVec&); std::uint64_t observe(const Matrix&);
}

namespace bench { bool presized(const IVec&); bool presized(const SVec&); bool presized(const Matrix&); }
