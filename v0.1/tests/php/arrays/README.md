# PHP array semantic suite
Doc Status: supporting
This suite pressure-tests `hash_t` semantics from PHP-facing inputs.

## Level 01
Foundational array behavior:
- packed append and readback
- explicit integer and string keys
- overwrite semantics
- next-index behavior
- nested arrays
- row append/update
- copy-by-value behavior
- small loop growth

## Level 02
Higher-risk semantic behavior:
- by-value vs by-reference function passing
- root/row/leaf aliasing
- duplicate refs to the same row or leaf
- large growth and post-growth patching
- key-casting edge cases
- sibling isolation
- deep nested alias chains
- slot-to-slot copy behavior
