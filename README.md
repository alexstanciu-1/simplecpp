# Simple C++

Simple C++ is a programming language concept that combines the ease of scripting languages with the performance and control of compiled C++.

It allows writing code in familiar languages (starting with PHP 8.4+, with JavaScript and Python planned) while targeting regular C++ code compiled Ahead-Of-Time using standard compilers like GCC, Clang, and MSVC.

## Core Idea

- write in a familiar language  
- generate regular C++ code  
- compile with existing toolchains  
- keep dynamic capabilities, but within controlled boundaries  

## Design Goals

- familiarity (reuse existing languages)  
- compilation (Ahead-Of-Time binaries)  
- implicit strictness (no unsafe implicit behavior)  
- controlled dynamic typing  
- leverage existing compiler ecosystems  
- efficient memory usage through compilation  

## What Simple C++ Brings Over Scripting Languages

- controllable memory size for data  
- efficient multithreading without data-copy constraints  
- error detection at compile time  
- performance of AOT binaries via GCC, Clang, MSVC  
- predictable and explicit behavior (no hidden type juggling)  

## What Simple C++ Brings to the C++ Model

- higher-level authoring (less low-level concerns)  
- structured dynamic layer (`mixed_t`, `hash_t`)  
- spec-driven behavior (JSON-defined rules)  
- no implicit overload surprises  
- generator-enforced discipline  

## Documentation

- Getting Started → docs/getting_started.md  
- Installation → docs/installation.md  
- Examples → docs/examples.md  

## Architecture Overview

### Language Frontend
- parses source language (PHP for now)  
- applies lowering rules  
- generates regular C++ code  

### Runtime (Simple C++)
- encapsulates generated code in a controlled namespace  
- ensures interaction with regular C++ is explicit  
- defines operator behavior based on spec  
- provides dynamic types (`mixed_t`, `hash_t`)  

## Repository Structure

- runtime/ → C++ runtime and specs  
- php_generator/ → PHP parsing and lowering  
- specs/ → design and rule definitions  
- public_html/test/ → test interface  

## Spec Model

- Markdown → human-readable intent  
- JSON → machine-enforced rules  

If a behavior is not defined → it is not allowed.

## Current Status

- PHP frontend: in progress  
- runtime: active development  
- operator semantics: under refinement  
- JavaScript / Python: planned  

## Roadmap

- stabilize runtime and config alignment  
- complete operator coverage  
- formalize edge cases  
- improve generator guarantees  
- expand test coverage  

## Summary

Simple C++ brings scripting-style development into a compiled, strict, and predictable model using standard C++ as the execution target.

## Installer Scripts

Starter installer scripts are available in:

- `install/windows.cmd`
- `install/macos.sh`
- `install/ubuntu.sh`

They install the required toolchain, create the user launcher directory (`~/.d-app` or `%USERPROFILE%\.d-app`), and run the project installer.
