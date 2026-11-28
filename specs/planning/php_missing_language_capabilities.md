# Missing PHP language capabilities in v0.2

Doc Status: planning

Audit target is stock PHP CLI 8.5.7 and the Simple C++ PHP flavor at e30c35ecb. Each row identifies an operation or guarantee that the tested surface cannot express directly. An intentional safety or ahead-of-time compilation boundary can qualify. This is an inventory for discussion, not an implementation backlog.

For a **more detailed compatibility pass**, use the [previous language-surface report](php_language_surface_audit.md), which records runnable source, ordinary behavior differences and migration details. The present inventory groups confirmed gaps from selected probes. It does not claim to enumerate every PHP grammar production. The scope follows the [PHP language reference](https://www.php.net/manual/en/langref.php).

## Evidence

Classic snippets ran locally as ordinary PHP CLI scripts. The .phs probes used the same compiler revision and native layout facts with the compiler’s cpp command. Where the signature would hide the relevant operation, probes used v0.2 types such as Iterator<int>, function<int()> or vector<int>. A quoted diagnostic below identifies the tested boundary. Union and object equality also required running native executables. Examples show the decisive fragment; omitted class declarations, arguments and fixture setup were present in the probes.

The bootstrap compiler itself contains concrete consumers of array union, destructuring, spread and persistent function-local storage. [Main-first union](../../compiler/03_parse/parser.php#L152) · [Queue destructuring](../../compiler/01_prepare_inputs/manifest.php#L213) · [Metadata spread](../../compiler/01_prepare_inputs/changes.php#L44) · [One-time warning](../../compiler/02_tokenize/tokens.php#L102)

The comparison column names languages that express the operation and identifies narrower forms. **Partial** marks a related operation with different guarantees. **Runtime** marks reflection. **None** refers to the PHP capability in that row. The original PHP and v0.2 probe results are retained, with the destructuring entry refreshed at d1fbaa1b0.

## Arrays and binding

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Left-biased keyed union | `$both=$left+$right;` | With left keys 0,x and right keys 0,7, PHP retained the left 0, appended 7, and preserved order. The boxed v0.2 expression lowered and failed when executed. `exit 1: boxed_scalar_kind`. | Partial — Swift Dictionary.merging can keep the left value on collisions, but does not guarantee PHP insertion order. |
| Unpacking arrays into literals | `$a=[0,...$parts];` | Splice entries while constructing a collection. Call-site argument unpacking does not construct a collection. `Expected variable name`. | Partial — C# collection expressions spread sequence elements with `..`. Associative keys are not carried through. |
| Collection destructuring in foreach | `foreach ($pairs as [$x,$y]) { … }` | Flat positional and literal-key assignments to locals shipped in d1fbaa1b0. The loop binding still requires a variable. | Rust destructures fixed arrays in a for pattern. Swift and C# destructure tuple elements in loops. C# also accepts types with Deconstruct. These patterns do not look up arbitrary literal keys in a row. |
| Reference-valued array entries | `$a=[&$n]; $a[0]=7;` | The entry itself aliases an independent scalar slot; writing it changes that slot. `Expected variable name`. | Partial — Rust can store mutable references and Go can store pointers to scalar slots. Both require explicit indirection on write. Rust also enforces exclusive borrowing. |
| Borrow a property or indexed slot for a call | `bump($item->n); bump($a[0]);` | Both a nominal field and a typed vector element failed with the same writable-slot diagnostic. This is an intentional lifetime boundary. `Reference argument requires stable variable storage: a named local or parameter`. | Rust can borrow fields and indexed mutable places. Go can take pointers to addressable fields and slice elements. Swift inout supports writable properties and subscripts with writeback. C# supports ref fields/array elements and ref-returning properties/indexers. |
| Object handles inside a heterogeneous PHP array | `$items=[new Item(), 3];` | Place an application object and an unrelated scalar in the same ordinary boxed array. `Unsupported boxing into mixed`. | Go (`[]any`), Swift (`[Any]`) and C# (`object[]`) admit both. Rust can use explicit type erasure, e.g. a vector of boxed dyn Any values. |
| Loose and strict comparison of boxed PHP arrays | `$a == $b; $a === $c;` | Loose comparison of reversed-order keyed arrays and strict comparison of independently created identical keyed arrays both failed. PHP returned true in each respective probe. `Unsupported boxed binary operands or operator`. | None provides both PHP array equality modes, including their key-order and coercion rules. |

See the [Rust pattern reference](https://doc.rust-lang.org/reference/patterns.html), [Go specification](https://go.dev/ref/spec), [Swift control flow](https://docs.swift.org/swift-book/documentation/the-swift-programming-language/controlflow/) and [C# collection expressions](https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/operators/collection-expressions) for collection and loop syntax.

## Types, functions and storage

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Scalar union in an authored signature | `function id(int\|string $v):int\|string` | Declare precisely these alternatives for callers and return values. Widening to mixed loses the static contract. `Union requires two distinct required classes with a common class base`. | C# 15 has named union types. Rust and Swift can declare enums with payloads for the alternatives and wrap them explicitly. Go union constraints apply to type arguments. |
| Intersection of independent interfaces | `function useBoth(A&B $v):int` | Demand both interfaces from the same argument without requiring an extra nominal subtype. `Intersection requires one required class and one interface`. | Rust trait bounds, Go embedded interfaces, Swift protocol composition and C# generic interface constraints can all require the same argument to satisfy A and B. |
| Persistent function-local storage | `function nextId():int { static $i=0; return ++$i; }` | One private slot survives across invocations. A namespace global changes its scope and owner. `Expected variable name`. | Rust permits a block-local static item. A changing counter needs interior mutability, e.g. an atomic. Swift can scope a helper type with a static stored property inside a non-generic function. |
| Unpack string keys as named arguments | `sub(...["b"=>2,"a"=>5])` | Resolve parameter names from a table at the call site. PHP prints 3 even though b comes first. `Unpacking requires one vector after all fixed arguments of a variadic target`. | None expands a string-keyed table into statically named parameters. |
| Construct a fresh default object on omission | `function make(P $p=new P()):P {return $p;}` | An omitted argument constructs a fresh instance for that call. `Parameter defaults require literals`. | Swift evaluates a default such as `p: P = P()` each time the caller omits it. |
| Conditionally declare a named function | `if ($enabled) { function answer():int {return 4;} }` | Executing a branch installs a callable name. A closure assigned to a local has different lookup and visibility. `Nested functions are not supported`. | None installs a new named function into the enclosing program when a branch executes. |
| Variadic parameters by writable reference | `function bumpAll(int &...$xs):void` | Each variadic argument preserves the identity of a writable caller slot. `Cannot write to read-only variadic pack: xs`. | Partial — Go accepts variadic pointers (`xs ...*int`). Callers pass addresses and the callee writes through them. |
| Variadic collection of unmatched named arguments | `function opts(...$opts) {return $opts["debug"];}` | Collect surplus named arguments under their string keys. `Vector index requires int64`. | Partial — Swift @dynamicCallable can collect arbitrary argument labels and values for an opted-in callable type. |
| Escaping by-reference closure capture | `return function() use (&$n) {return ++$n;};` | Retain the original local slot after its declaring call returns. `Owned callable cannot erase a borrowed environment`. | Go, Swift and C# can capture a mutable local that survives the creating call. Rust can borrow a captured slot only as long as its owner lives. |

See [C# union types](https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/builtin-types/union), [Rust closure captures](https://doc.rust-lang.org/reference/types/closure.html) and [Swift declarations](https://docs.swift.org/latest/documentation/the-swift-programming-language/declarations/) for type and storage details.

## Objects, properties and declarations

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Shallow object cloning | `$b=clone $a;` | Allocate a new instance of the actual class and copy its properties; ordinary class assignment retains identity. `Expected ;`. | C# provides the protected MemberwiseClone operation. A record class also has a shallow copy through `with {}` by default. |
| Anonymous class expressions | `$p=new class { public int $n=3; };` | Construct an instance of a class declared at the expression site. `Expected identifier`. | Partial — Go has anonymous structs and C# has anonymous data types. Neither form defines an anonymous class with its own method body. |
| Property access hooks | `public int $n { get => 4; }` | Attach user code to ordinary property access. An explicit getter changes the caller contract. `Expected ;`. | Swift computed properties and property observers, and C# property accessors. |
| Computed property selection on nominal objects | `$p->{$name}` | Select a declared member of a class by runtime string. Computed table keys on boxed dynamic receivers do not provide this on a class. `Computed member read requires a dynamic receiver`. | Runtime — Go reflection can select exported fields by name. C# reflection can select properties or fields by name. |
| Computed method dispatch | `$p->{$name}()` | Resolve the method name from data on a nominal receiver. `Computed member calls are not supported`. | Runtime — Go reflection can select exported methods by name. C# reflection can select a method by name and signature. |
| Choosing between conflicting trait methods | `use A,B { A::n insteadof B; }` | Choose a winning trait contribution in the consuming class. `Trait aliases require one direct use in a class or struct`. | Partial — Rust can qualify a call by trait. C# can implement conflicting interface members explicitly. Neither installs a winning trait method under the PHP spelling. |
| User-defined declaration attributes | `#[Route("/items")] class Items {}` | Attach an application-defined attribute with arguments to a declaration. The compiler rejected the attribute syntax. `Expected variable name`. | C# has user-defined metadata attributes. Rust attribute macros and Swift attached macros act at compile time. Go struct tags attach metadata only to fields. |
| Object value equality across distinct instances | `(new P()) == (new P())` | Compare same-class instances by their property values. The tested v0.2 class comparison does not perform that traversal. Native output was `0`; PHP printed `1`. | Partial — C# record classes provide member-wise equality by default. Rust derives PartialEq and Swift synthesizes Equatable for eligible value types. Go compares structs with comparable fields. None makes PHP-style property comparison the default for ordinary class instances. |
| Invoke an instance through a user-defined call operator | `$f=new Twice(); $f(3);` | A user class defines what calling its instances means. `Invocation requires a typed callable local: f`. | Swift supports callAsFunction on a nominal type. C# can opt into dynamic invocation with DynamicObject.TryInvoke. |
| Object properties created at runtime | `$p->extra=2;` | Attach a previously undeclared property to a nominal instance. The classic probe created it and emitted the PHP 8.5 deprecation for an unmarked class. `Unknown field P.extra`. | Partial — C# supports expandable instances through ExpandoObject. Swift can route dynamic member writes into application-owned storage. Both require a suitable receiver. |
| Magic fallback for unknown properties and methods | `$proxy->absent; $proxy->missing();` | An unresolved property read never invokes the declared __get; an unresolved method call never invokes __call. The read failed with Unknown field P.absent. `Unknown method P::missing`. | C# DynamicObject can intercept missing reads and calls on a dynamic receiver. Swift @dynamicMemberLookup intercepts member access and can return a callable proxy for a subsequent call. |
| Different read and write visibility | `public private(set) int $id=1;` | Expose a property for public reading and allow repeated writes only inside the declaring class. `Expected identifier`. | Swift and C# support a more restrictive setter on a publicly readable property. |
| Use enum cases through an application interface | `enum State:string implements Named { … }` | Use a case wherever the interface is required, with dispatch to the enum method. `Expected {`. | Rust enums can implement traits. Swift enums can conform to protocols. Go can attach methods to a named integer type with constants, but the constants do not form a closed enum. |

See [Swift attributes](https://docs.swift.org/latest/documentation/the-swift-programming-language/attributes/), [Go reflection](https://pkg.go.dev/reflect) and [C# dynamic objects](https://learn.microsoft.com/en-us/dotnet/csharp/advanced-topics/interop/walkthrough-creating-and-using-dynamic-objects) for the opt-in dispatch mechanisms.

## Control flow and failure handling

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Jump to a named label | `goto done; … done:` | Transfer control to a label in the current scope. `Expected variable name`. | Go and C# have goto within a function, subject to scope restrictions. |
| A throw expression in a function argument | `consume($ok ? 1 : throw new Exception("bad"));` | An arbitrary conditional arm may throw in expression position. The bounded v0.2 contract specifies direct coalescing fallback and match default. `Throw expression requires a ?? fallback or match default`. | C# permits a throw expression in a conditional arm. Rust permits a diverging expression there, but panic and error returns have different failure contracts. |
| Catch several exception classes in one binding | `catch (A\|B $e) { … }` | Select only a union of exception types with a single handler body. `Expected variable name`. | Swift supports several patterns in one catch. C# can bind one exception and use a catch filter to select either type. |
| Expression-local diagnostic suppression | `@$a["missing"]` | Suppress diagnostics for the evaluation of one operand. `Unsupported token`. | None has a general expression-level PHP diagnostic suppression operator. |

See [C# exception handling](https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/statements/exception-handling-statements) and [Swift error handling](https://docs.swift.org/swift-book/documentation/the-swift-programming-language/errorhandling/) for handler selection.

## Generators and suspension

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Delegated yields | `yield from [3,5];` | Forward another producer’s iteration, including keys. The v0.2 producer contract explicitly defers delegation. `Expected ]`. | Partial — Go can forward the same yield callback to a nested iterator, including a key/value iterator. C# can forward elements with an explicit loop. |
| Receive a value at a suspended yield | `$reply=yield 1;` | Resume the producer with a caller-supplied value. The v0.2 yield statement has no incoming expression value. `Expected variable name`. | None in the stable generator constructs compared here. Experimental Rust coroutines accept a resume argument. |
| Return a terminal generator value | `yield 1; return 7;` | Expose a final result separate from the last yielded item. A v0.2 producer can only complete with a bare return. `Void function cannot return a value`. | None in the stable generator constructs compared here. Experimental Rust coroutines expose a separate completion value. |
| Suspend within exception cleanup regions | `try { yield 1; } finally { echo "end"; }` | Keep an active finally region across suspension. The generator body contract excludes exception regions. `Yield slice supports locals, writes, conditionals, loops, yields, echo, calls and bare return only`. | C# permits yield return in a try with finally and no catch. Go defer can span calls to a push iterator’s yield callback. |
| Generator instance methods | `public function ids():iterable {yield $this->id;}` | Suspend and resume an instance method that uses its receiver. `Yield producers must be free functions`. | C# supports yield in instance iterator methods. Go methods can serve as push iterators by taking a yield callback. |
| Suspend a nested ordinary call stack | `Fiber::suspend(3)` | An ordinary nested callee can suspend the entire caller stack; the caller can later resume the suspension. `Unknown static method: Fiber::suspend`. | Partial — Go can suspend a goroutine inside an ordinary nested call and coordinate resumption through a channel. Scheduling and resumption differ from PHP Fiber. |

See [Go range functions](https://go.dev/blog/range-functions), [C# iterator restrictions](https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/statements/yield) and [Rust experimental coroutine states](https://doc.rust-lang.org/nightly/std/ops/enum.CoroutineState.html) for the different producer contracts.

## Runtime names, code and execution

| Missing capability | Small PHP example | v0.2 boundary | Rust / Go / Swift / C# support |
| --- | --- | --- | --- |
| Variable variables | `$$key` | Resolve a local slot by its runtime name. `Expected variable name`. | None resolves an arbitrary lexical local from a runtime string. |
| Call a named function from a runtime string | `$fn="answer"; $fn();` | Resolve a free function from a string. A typed callback already holds a callable target. `Invocation requires a typed callable local: fn`. | None directly resolves a free function this way. C# reflection can resolve a named static method on a known type. |
| Instantiate a class selected by runtime name | `new $className()` | Select a constructor using a runtime class name. `Expected identifier`. | Runtime — C# can resolve a type name and invoke its constructor with .NET reflection. |
| Load code from a runtime-selected path | `include $path;` | Execute a chosen source file in the current scope. The compiler directs authors to a fixed module source inventory. `include is unsupported; select project sources in module.json`. | None executes a runtime-selected source file in the current lexical scope. |
| Evaluate generated source text | `eval($source)` | Parse and execute a PHP program fragment computed at runtime. `Unknown function: eval`. | None provides general source eval as an operator or builtin of the compiled language. External scripting engines are outside this comparison. |
| Interleave program statements with literal page markup | `?>Hello <?= $name ?>` | Switch between compiled PHP-flavor source and literal output within one file. `Expected variable name`. | None in the four core languages. C# Razor and Go templates are separate template systems. |

See [.NET runtime construction](https://learn.microsoft.com/en-us/dotnet/api/system.activator.createinstance) for the name-based activation API.

Boundary references include the [boxed storage contract](../mixed_storage.md), [native reference safety](../native_reference_safety.md), [closure lifetime](../language/closures.md), [trait composition](trait_composition.md), [exception expressions](../exceptions_v0_2.md) and [generator restrictions](../generators_v0_2.md). PHP specifies key-preserving union and delegated yields in the [array operators](https://www.php.net/manual/en/language.operators.array.php) and [generator syntax](https://www.php.net/manual/en/language.generators.syntax.php) references.
