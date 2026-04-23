# Transitional Behavior: Implicit mixed_t Conversions
Doc Status: planning
Status: Active transitional note

This document records the currently accepted transitional behavior around implicit `mixed_t` to native conversion paths.

## Current accepted implementation behavior

The current runtime permits implicit conversion from `mixed_t` to native types in certain contexts, including:
- assignment to typed locals
- typed property assignment
- typed returns
- typed function argument binding

This behavior is:
- accepted in the current implementation
- not considered part of the long-term architectural guarantee
- subject to tightening or removal as generator-controlled explicit boundaries become complete

## Design intent

The architectural model remains:

- dynamic expressions remain `mixed_t` by default
- conversion to native types occurs at explicit typed boundaries or explicit narrowing points
- a typed function or method call counts as an explicit typed boundary

Implicit conversions currently act as a fallback mechanism, but should not be relied upon as the long-term design guarantee.

## Future direction

A stricter mode may be introduced where implicit `mixed_t` conversions are disabled, requiring all dynamic-to-native transitions to be explicitly emitted by the generator.
