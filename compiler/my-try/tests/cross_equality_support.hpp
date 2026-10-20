#pragma once
#include "cross_identity_support.hpp"

// Ordinary equality has its own state oracle; strict identity remains separate.
namespace cross_equality_probe {
using namespace identity_probe;

template<class P, class Q> bool oracle(State<P> left, State<Q> right) {
 static_assert(std::is_same_v<P,Q> && !std::is_same_v<P,bool>);
 if(left.kind != right.kind) return false;
 if(left.kind == state_kind::payload) return left.value == right.value;
 // Nullable absence matches absence; Result failure matches failure, regardless
 // of error metadata. Matching bool sentinel states compare equal; a sentinel
 // never equals a numeric payload, even when its truth conversion would match.
 return true;
}

template<int L, int R, class V, class P, class W, class Q> long runtime_pair() {
 static_assert(L >= 0 && L < 4 && R >= 0 && R < 4);
 long count = 0;
 for(auto a : states<L, P>()) for(auto b : states<R, Q>()) {
  auto left = make<L, V>(a);
  auto right = make<R, W>(b);
  bool expected = cross_equality_probe::oracle(a, b);
  if((left == right).native_value() != expected || (left != right).native_value() == expected
   || !same(left, a) || !same(right, b)) throw std::runtime_error("cross equality/state mismatch");
  ++count;
 }
 return count;
}
}
