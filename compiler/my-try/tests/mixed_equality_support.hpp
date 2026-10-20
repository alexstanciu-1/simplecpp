#pragma once
#include "cross_identity_support.hpp"

// Ordinary equality has its own state oracle; strict identity remains separate.
namespace mixed_equality_probe {
using namespace identity_probe;

template<class P, class Q> bool oracle(State<P> left, State<Q> right) {
 static_assert((std::is_same_v<P, std::int64_t> && std::is_same_v<Q, double>)
  || (std::is_same_v<P, double> && std::is_same_v<Q, std::int64_t>));
 if(left.kind != right.kind) return false;
 if(left.kind == state_kind::payload) return static_cast<double>(left.value) == static_cast<double>(right.value);
 // Nullable absence matches absence; Result failure matches failure, regardless
 // of error metadata. Matching bool sentinel states compare equal; a sentinel
 // never equals a numeric payload, even when its truth conversion would match.
 return true;
}

template<int L, int R, class V, class P, class W, class Q> long runtime_pair() {
 static_assert((L == R && L >= 0 && L <= 4)
  || (L == 4 && R >= 0 && R < 4) || (R == 4 && L >= 0 && L < 4));
 long count = 0;
 for(auto a : states<L, P>()) for(auto b : states<R, Q>()) {
  auto left = make<L, V>(a);
  auto right = make<R, W>(b);
  bool expected = mixed_equality_probe::oracle(a, b);
  if((left == right).native_value() != expected || (left != right).native_value() == expected
   || !same(left, a) || !same(right, b)) throw std::runtime_error("mixed equality/state mismatch");
  ++count;
 }
 return count;
}
}
