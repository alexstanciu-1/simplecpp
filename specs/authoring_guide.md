# Typed boundaries in application code

Doc Status: supporting

Give a dynamic value a concrete type where the application first needs one. A typed destination already states the intended conversion. Use inference when the resulting type is clear.

Check or extract a wrapper when the consumer needs its payload. Returning the wrapper is appropriate when the caller owns failure handling. Choose a presence test to distinguish absence from a present zero, false or empty string. Coalescing with `??` preserves present zero and false. Use exact comparison when type identity matters.

Choose conversions explicitly where they express an application decision. In particular, a string of digits and an integer are different inputs at a typed boundary.
