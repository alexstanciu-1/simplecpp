
// ONLY NOTES ... on how to do a getter setter for dynamic props


// The C++20 Getter Macro
#define GET_PROP(obj_ptr, prop_name) \
    [&](auto* ptr) -> decltype(auto) { \
        if constexpr (requires { ptr->prop_name; }) { \
            /* Native property exists! Return a direct reference to it. */ \
            return (ptr->prop_name); \
        } else { \
            /* Dynamic property. Search the Swisstable. */ \
            auto [found, val_ptr] = ptr->find(#prop_name); \
            return val_ptr; /* Returns value_type* (or nullptr if missing) */ \
        } \
    }(obj_ptr)

// THE C++20 MACRO
#define SET_PROP(obj_ptr, prop_name, value) \
    [&](auto* ptr) { \
        if constexpr (requires { ptr->prop_name = value; }) { \
            /* The native property exists! Assign it. */ \
            ptr->prop_name = value; \
        } else { \
            /* Native property doesn't exist. Fallback to dynamic insert. */ \
            ptr->insert(#prop_name, value); \
        } \
    }(obj_ptr)

// Alternative: Safe Dynamic Return
#define GET_PROP_SAFE(obj_ptr, prop_name) \
    [&](auto* ptr) ENGINE_FORCE_INLINE -> decltype(auto) { \
        if constexpr (requires { ptr->prop_name; }) { \
            return (ptr->prop_name); \
        } else { \
            static value_type null_fallback{}; /* Represents JS 'undefined' */ \
            auto [found, val_ptr] = ptr->find(#prop_name); \
            return found ? *val_ptr : null_fallback; \
        } \
    }(obj_ptr)

/*
In modern C++, an Immediately Invoked Lambda [&](){}() is considered a zero-cost abstraction.
Because you are compiling with -O3, the compiler's intermediate representation (IR) looks at that lambda, 
sees that it is created, invoked, and destroyed all on the exact same line, and completely erases the function boundary. 
It just copy-pastes the winning if constexpr branch directly into your main execution path. 
There is no stack frame created, and no function pointer jump.
*/

// ===============================================================================

// Define this at the top of your file
#define SET_PROP(obj_ptr, prop_name, value) obj_ptr->insert(#prop_name, value)

int main() {
    mem_container* memc = new mem_container();

    // Your code generator just prints this:
    SET_PROP(memc, number, 1);
    SET_PROP(memc, name, "my name");
    
    // The C++ Preprocessor automatically rewrites it to this before compiling:
    // memc->insert("number", 1);
    // memc->insert("name", "my name");
}

// DANGER: This will not compile!
#define SET_PROP(obj, prop, val) \
    if constexpr (std::is_same_v<std::decay_t<decltype(*obj)>, mem_container>) { \
        obj->insert(#prop, val); \
    } else { \
        obj->prop = val; \
    }

// THE C++17 FORK MACRO
#define SET_PROP(obj_ptr, prop_name, value) \
    [&](auto* ptr) { \
        using T = std::decay_t<decltype(*ptr)>; \
        if constexpr (std::is_same_v<T, mem_container>) { \
            /* It's our dynamic engine. Use string injection. */ \
            ptr->insert(#prop_name, value); \
        } else { \
            /* It's a standard C++ struct/class. Use native assignment. */ \
            ptr->prop_name = value; \
        } \
    }(obj_ptr) // Instantly invoke the lambda with your object

#define GET_PROP(obj_ptr, prop_name, value) \
    [&](auto* ptr) { \
        using T = std::decay_t<decltype(*ptr)>; \
        if constexpr (std::is_same_v<T, mem_container>) { \
            /* It's our dynamic engine. Use string injection. */ \
            return ptr->find(#prop_name); \
        } else { \
            /* It's a standard C++ struct/class. Use native assignment. */ \
            return ptr->prop_name; \
        } \
    }(obj_ptr) // Instantly invoke the lambda with your object


#include <type_traits>

// Detect the compiler and set the brutal inline attribute
#if defined(__clang__) || defined(__GNUC__)
    #define ENGINE_FORCE_INLINE [[gnu::always_inline]]
#elif defined(_MSC_VER)
    #define ENGINE_FORCE_INLINE [[msvc::forceinline]]
#else
    #define ENGINE_FORCE_INLINE inline
#endif

// The C++17 Macro with Forced Inlining
#define SET_PROP(obj_ptr, prop_name, value) \
    [&](auto* ptr) ENGINE_FORCE_INLINE { \
        using T = std::decay_t<decltype(*ptr)>; \
        if constexpr (std::is_same_v<T, mem_container>) { \
            ptr->insert(#prop_name, value); \
        } else { \
            ptr->prop_name = value; \
        } \
    }(obj_ptr)

