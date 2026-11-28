# PHP language surface in Simple C++ v0.2

Doc Status: planning

## Inventory before the audit

Use stock PHP 8.5 CLI as the comparison target for the PHP flavor. The unit of comparison is a source construct, including its observable behavior. The earlier [33-task developer scorecard](php_flavor_developer_qos_reaudit.md) measures application tasks that often combine several constructs and helpers. This inventory follows the [PHP language reference](https://www.php.net/manual/en/langref.php) and includes [PHP 8.5 syntax](https://www.php.net/manual/en/migration85.new-features.php).

| Area | Questions to check |
| --- | --- |
| Source, entry and modules | Opening tags, embedded HTML, includes, strict types, file paths and entry return |
| Values and storage | Local inference, reassignment, scope, constants, globals and predefined variables |
| Types and conversions | Scalar declarations, numeric strings, casts, nullable, unions, mixed and result carriers |
| Expressions | Arithmetic, comparison, truth, concatenation, assignment, precedence, lazy evaluation and effects |
| Text and output | Byte indexing, interpolation, echo, print, multiline literals |
| Collections | PHP array literals and keys, destructuring, missing and null entries, copy behavior, iteration and removal |
| Control flow | Branches, switch and match, loop forms, break and continue, early exit |
| Functions | Signatures, default and named arguments, variadics, unpacking, callable values and captures |
| References | Parameter slots, return aliases, lifetime across iteration and storage in properties |
| Classes and objects | Construction, properties, visibility, dispatch, inheritance, interfaces, traits and magic behavior |
| Names and enums | Namespaces, imports, lookup, constants and backed enums |
| Failure handling | Exceptions, catch and finally, absence, checked conversion and error suppression |
| Advanced and dynamic syntax | Attributes, generators, fibers, dynamic names, runtime code, and PHP 8.5 pipe expressions |

## Findings at e30c35ecb

Comparison used 64-bit PHP CLI 8.5.7 with its default weak call-site scalar typing. Each paired expression used identical source after removing the classic opening tag. The appendix records whether the .phs file lowered to C++, was rejected by the frontend, or ran as a native executable. A successful lowering alone establishes admission. The ledger contains 71 paired probes, including 21 with observed native execution. These are representative cases across the inventory, not a percentage of PHP implemented.

### Code that runs and gives a different answer

| Same source | Stock PHP | Simple C++ PHP flavor | Consequence |
| --- | --- | --- | --- |
| `echo 7 / 2;` | `3.5` | `3` | Integer operands select integer division. Make one operand floating point for a fractional result. |
| `$s="AZ"; echo $s[0];` | `A` | `65` | String indexing produces a byte value. A one-byte string and its numeric byte require different formatting. |
| `echo (int)("01" == "1");` | `1` | `0` | Two strings compare as strings in v0.2. A numeric-looking identifier can change a branch without any diagnostic. |
| `$b=true; echo $b, "\|$b";` | `1\|1` | `1\|true` | Direct echo and interpolation have distinct formatting contracts. Refactoring a template into an echo list can change its output. |
| `$s="0"; echo (int)empty($s);` | `1` | `0` | v0.2 measures string emptiness by byte length. The same string still selects the false branch of a condition. [Presence contract](../count_empty_isset_contract.md) |

### Family-level audit

| Area | What a PHP developer can carry across | Boundary, consequence or alternative |
| --- | --- | --- |
| Script and module | Write an ordinary sequence of statements and let it reach the end. | Use a .phs entry, a source inventory and explicit inputs. A leading PHP tag warns once. Includes and strict_types have migration diagnostics. $_GET is an unknown name even inside coalescing. __FILE__ and __DIR__ use relocatable source paths. __LINE__ failed lookup. [Frontend contract](../compiler_frontends.md) |
| Local storage | Simple assignment infers a concrete type. | A later assignment must fit it. A new branch or loop local stays in its scope. Declare an outer value when it must survive. An unknown name on the left of ?? is still an error. A bare null initializer needs a declared type. |
| Globals and constants | Named constants and shared global storage have source declarations. | Declare a typed constant, for example `const int COST=4;`. Globals are declared at file or namespace scope. A PHP function-local static counter and a function-local global import both failed. [Name and storage contract](../namespaces_v0_2.md) |
| Types and calls | A nullable scalar parameter accepts a value or null. | A PHP weak call to an int parameter with "21" failed in v0.2. Explicit string-to-int conversion checks the whole input. The int-or-string union declaration failed; the compiler diagnostic identifies a restricted common-base class union. A PHP mixed declaration also has a much wider domain than v0.2 boxed storage. [Conversions](../checked_conversions.md) · [Mixed storage](../mixed_storage.md) |
| Operators | Numeric power, bitwise operations and integer spaceship comparison lowered. False short-circuited an effectful RHS in both executables. | The division and loose equality cases above change values. Concatenating an arithmetic result with a string failed. Eager binary operand order has no portable source guarantee. [Evaluation contract](../expression_evaluation.md) |
| Text and output | Echo, scalar interpolation and quoted keys in supported paths are available. | A PHP print expression and a heredoc failed with generic parser diagnostics. Interpolation has its own formatting rules. [Output](../output_v0_2.md) · [Interpolation](../string_interpolation.md) |
| Arrays and tables | Literal entries, append and foreach have typed and boxed forms. Typed hash ?? and ??= distinguish presence from truth. | The same boxed literal expression `[1]+[2]` compiled, then failed with boxed_scalar_kind. The literal `["1"=>"s",1=>"i"]` produced one overwritten entry in PHP and failed with duplicate_initializer_key in v0.2. Bracket destructuring and indexed unset failed at preparation or parsing. Typed vectors are dense. Typed hash removal and vector removal have distinct key/index effects. [Array semantics](../array_semantics.md) · [Traversal](../collection_traversal.md) |
| Conditions and loops | A single-statement if, switch with break, match with default, and nested break 2 all ran. The string "0" was false in both. | The colon/endif form failed. New foreach bindings have lexical scope. Typed match result arms have a shared result-type contract. [Match](../match_v0_2.md) · [Loops](../loop_statements_v0_2.md) |
| Functions and closures | Named arguments reordered correctly, defaults were admitted, typed arrow capture retained its value, and unpack into a typed variadic lowered. | A standalone first-class function reference without a callback destination failed. An untyped PHP declaration requires an explicit signature in ordinary contexts. Exact lookup rejected ANSWER() for answer(). [Functions](../../compiler/docs/functions_slice.md) · [Closures](../language/closures.md) |
| References | A named mutable integer parameter updated the original slot. Binding to a function returning its parameter by reference lowered. | Passing a field to that same mutable parameter failed. Typed collection borrows and writable foreach have explicit lifetimes. PHP's lingering foreach reference is a migration hazard. [Reference safety](../native_reference_safety.md) |
| Classes and dispatch | Classes, typed fields, construction and ordinary trait methods are usable. A nullable method receiver with a coalescing fallback produced the same result for absent and present receivers. | A routine PHP subclass override failed because the base method was nonvirtual. An interface implementation required an override marker. Promotion already explains how to declare the field. Hooks, dynamic member names, clone and __get fallback all failed in the examples. Unsetting a required class handle also failed. [Inheritance and dispatch](../inheritance_v0_2.md) · [Destruction](../destructors_v0_2.md) |
| Namespaces and enums | A namespaced function imported with an alias lowered. Backed enum declarations are recognized. | Exact lookup and file-local imports apply. Reading a backed enum's PHP ->value pseudo-property failed. The existing enum QoS probe shows the explicit enum_value spelling. [Namespaces](../namespaces_v0_2.md) · [Enums](../../compiler/docs/enums.md) |
| Failures and absence | Typed catch and finally both ran in the expected order. | Suppression with @ failed. Exception delivery and checked runtime failures are separate contracts. Nullable and result carriers introduce presence, success and structured failure beyond ordinary PHP unions. In particular, a successful zero payload and a failed result can drive different branches. [Exceptions](../exceptions_v0_2.md) · [Take](../error_values_and_take.md) |
| Advanced and dynamic code | v0.2 has specific declaration attributes, typed yield producers and cooperative async. | An arbitrary user attribute, variable variables, eval, Fiber construction and a PHP 8.5 pipe expression failed. The PHP iterable producer failed at the signature. v0.2 spells a producer Iterator<Value> and documents its allowed body and cursor lifetime. The iterable probe alone says nothing about yield-from in an admitted producer. [Yield](../generators_v0_2.md) · [Async](../async_await.md) |

### Suggested order for discussion

1. **Document the silent value changes together.** A small “predict the output” example for division, byte indexing, numeric strings, boolean text and empty("0") would help more than a general warning about strict typing. The numeric, byte and presence cases already have explicit rules. Decide separately whether boolean interpolation and echo are meant to stay observably different.
2. **Improve the boundary between dynamic arrays and scalar operations.** Decide the intended contract for PHP-style array union before changing admission. The two literal operands make this case easy for a reader to recognize. For operands boxed at runtime, the same syntax can genuinely denote numeric addition. A diagnostic naming the operation and actual kinds would already improve that case. Treat duplicate literal keys as a separate decision.
3. **Improve diagnostics for common, statically recognizable PHP syntax.** Start with bracket destructuring, untyped constants and print. Each currently reports an expected token. Constructor promotion provides an existing model for an actionable explanation. A better diagnostic does not require accepting the syntax.
4. **Explain established safety and dispatch rules at the first encounter.** Show branch scope, stable slot references, virtual/override markers and explicit collection types in the onboarding example. Prioritize these over dynamic property hooks, eval, arbitrary attributes or Fiber compatibility.

## Paired probe ledger

Reproduce each row with PHP CLI (php -n -r) and the source cell. For v0.2, save the cell as main.phs and run php compiler/main.php cpp php main.phs build/clang/native-layout/facts.json out.cpp. Native observations additionally used a project build and executable, including a second run omitting the failing array union so later rows executed. Source cells display embedded line breaks as a backslash followed by n. “Lowered” means the executable was not run for that row.

| Probe | Source | PHP CLI | Simple C++ |
| --- | --- | --- | --- |
| division | `echo 7 / 2;` | `3.5` | `3` |
| string index | `$s="AZ"; echo $s[0];` | `A` | `65` |
| numeric equality | `echo (int)("01" == "1");` | `1` | `0` |
| cast prefix | `echo (int)"12 apples";` | `12` | `Invalid decimal integer text` |
| truth | `echo "0" ? "yes" : "no";` | `no` | `no` |
| booleans | `$b=true; echo $b, "\|$b";` | `1\|1` | `1\|true` |
| block local | `if (true) { $n=4; } echo $n;` | `4` | `Unknown or not-yet-initialized local/global: n` |
| retag local | `$n=4; $n="abc"; echo $n;` | `abc` | `Unsupported implicit conversion from string to int64` |
| undefined default | `echo $missing ?? "guest";` | `guest` | `Unknown or not-yet-initialized local/global: missing` |
| constant | `const COST=4; echo COST;` | `4` | `Expected identifier` |
| static local | `function tick():int { static $n=0; return ++$n; } echo tick(),tick();` | `12` | `Expected variable name` |
| global | `$n=2; function f():int { global $n; return $n; } echo f();` | `2` | `Global declarations are only allowed at file/namespace level` |
| typed argument | `function twice(int $n):int { return $n*2; } echo twice("21");` | `42` | `Unsupported explicit conversion from string to int64` |
| union | `function f(int\|string $x):int {return 1;} echo f("a");` | `1` | `Union requires two distinct required classes with a common class base` |
| array union | `$a=[1]+[2]; echo (int)$a[0];` | `1` | `runtime error: boxed_scalar_kind` |
| list assignment | `[$a,$b]=[2,3]; echo $a,$b;` | `23` | `Expected variable name` |
| unset element | `$a=[2,3]; unset($a[0]); echo (int)$a[1];` | `3` | `Expected ) after a single whole-variable unset operand` |
| foreach scope | `foreach ([2] as $i) {} echo (int)$i;` | `2` | `Unknown or not-yet-initialized local/global: i` |
| switch | `$x=2; switch($x){case 2: echo "yes"; break; default: echo "no";}` | `yes` | `yes` |
| match | `$x=2; echo match($x){2=>"yes",default=>"no"};` | `yes` | `yes` |
| single statement | `if (true) echo "yes";` | `yes` | `yes` |
| alternate syntax | `if(true): echo "yes"; endif;` | `yes` | `Expected variable name` |
| nested break | `while(true){while(true){break 2;} break;} echo 1;` | `1` | `1` |
| named defaults | `function sub(int $a,int $b=1):int{return $a-$b;} echo sub(b:2,a:5);` | `3` | `3` |
| closure | `$x=2; $f=fn(int $n):int=>$n+$x; $x=6; echo $f(3);` | `5` | `5` |
| reference | `function inc(int &$x):void{$x+=1;} $n=2; inc($n); echo $n;` | `3` | `3` |
| property reference | `class P {public int $x=2;} function inc(int &$x):void{$x+=1;} $p=new P(); inc($p->x); echo $p->x;` | `3` | `Reference argument requires stable variable storage: a named local or parameter` |
| promotion | `class P {public function __construct(public int $x){}} $p=new P(2); echo $p->x;` | `2` | `Declare the field in the class body and assign it from a typed constructor parameter` |
| property hook | `class P { public int $x { get => 4; } } $p=new P(); echo $p->x;` | `4` | `Expected ;` |
| dynamic member | `class P {public int $x=2;} $p=new P(); $key="x"; echo $p->$key;` | `2` | `Expected identifier` |
| enum value | `enum E:string{case A="a";} echo E::A->value;` | `a` | `Member reads are not supported in this slice for type E` |
| print | `$n=print "ok"; echo $n;` | `ok1` | `Expected ;` |
| heredoc | `$x=<<<TXT\nHello\nTXT;\necho $x;` | `Hello` | `Expected variable name` |
| catch finally | `try {throw new Exception("x");} catch(Exception $e){echo 1;} finally {echo 2;}` | `12` | `12` |
| suppression | `$x=@$missing; echo 1;` | `1` | `Unsupported token` |
| attribute | `#[Example] function f():int{return 1;} echo f();` | `1` | `Expected variable name` |
| pipe | `function twice(int $n):int{return $n*2;} echo 3 \|> twice(...);` | `6` | `Expected variable name` |
| case lookup | `function answer():int{return 4;} echo ANSWER();` | `4` | `Unknown function: ANSWER` |
| variable variable | `$key="name"; $name=4; echo $$key;` | `4` | `Expected variable name` |
| late static | `class A {public static function id():int{return 1;} public static function run():int{return static::id();}} echo A::run();` | `1` | `1` |
| left-biased array union | `$a=[1]+[2]; echo "built";` | `built` | `runtime error: boxed_scalar_kind` |
| numeric key overwrite | `$a=["1"=>"s",1=>"i"]; foreach ($a as $k=>$v){echo (string)$v;}` | `i` | `runtime error: duplicate_initializer_key` |
| lazy RHS | `function mark(int &$n):bool{$n+=1;return true;} $n=0; if(false && mark($n)){echo 9;} echo $n;` | `0` | `0` |
| precedence | `echo 1+2 . "4";` | `34` | `Unsupported binary operands or operator` |
| Rooted predefined PHP constant | `namespace Audit; echo \PHP_VERSION_ID > 0 ? 1 : 0;` | `1` | `Unknown constant: \PHP_VERSION_ID` |
| ordinary override | `class BaseItem {public function id():int{return 1;}} class ChildItem extends BaseItem {public function id():int{return 2;}} function readId(BaseItem $x):int{return $x->id();} echo readId(new ChildItem());` | `2` | `Nonvirtual method hiding is forbidden: ChildItem::id` |
| PHP iterable producer | `function ids(): iterable {yield 4;} foreach(ids() as $i){echo $i;}` | `4` | `Unknown or unsupported return type: iterable` |
| first-class callable | `function one():int{return 1;} $cb=one(...); echo $cb();` | `1` | `Callable reference requires a typed callback argument` |
| nullsafe method | `class P {public function n():int{return 7;}} function score(?P $p):int{return $p?->n() ?? 9;} echo score(null),score(new P());` | `97` | `97` |
| attribute lines | `#[Example]\nfunction f():int{return 1;} echo f();` | `1` | `Expected variable name` |
| trait | `trait T {public function n():int{return 2;}} class C {use T;} $c=new C();echo $c->n();` | `2` | `2` |
| clone | `class P {public int $x=1;} $a=new P();$b=clone $a;$b->x=2;echo $a->x;` | `1` | `Expected ;` |
| magic get | `class P {public function __get(string $k):int{return 2;}} $p=new P();echo $p->absent;` | `2` | `Unknown field P.absent` |
| destructor | `class P {public function __destruct(){echo "x";}} $p=new P();unset($p);` | `x` | `Cannot unset storage of type P` |
| interface | `interface I {public function n():int;} class P implements I {public function n():int{return 2;}} $p=new P();echo $p->n();` | `2` | `Interface implementation requires override: P::n` |
| alias return | `function &pick(int &$n):int{return $n;} $x=2;$y=&pick($x);$y=5;echo $x;` | `5` | `Lowered` |
| weak null | `function f(?int $n):int{return $n ?? 7;}echo f(null),f(0);` | `70` | `Lowered` |
| function import | `namespace A; use function A\n as pick; function n():int{return 2;} echo pick();` | `2` | `Lowered` |
| magic line | `echo __LINE__;` | `1` | `Unknown constant: __LINE__` |
| null coalesce assign | `$n=null;$n??=3;echo $n;` | `3` | `Cannot infer a local type from null; write an explicit type` |
| include | `include_once "missing.php";echo 1;` | `2 warnings, then 1` | `include_once is unsupported; select project sources in module.json` |
| superglobal | `echo $_GET["q"] ?? "x";` | `x` | `Unknown or not-yet-initialized local/global: _GET` |
| eval | `echo eval("return 3;");` | `3` | `Unknown function: eval` |
| fiber | `$f=new Fiber(fn()=>4);$f->start();echo $f->getReturn();` | `4` | `Shared construction requires a record: Fiber` |
| yield from | `function ids():iterable{yield from [2];}foreach(ids() as $n){echo $n;}` | `2` | `Unknown or unsupported return type: iterable` |
| variadic | `function add(int ...$ns):int{$s=0;foreach($ns as $n){$s+=$n;}return $s;} echo add(...[1,2]);` | `3` | `Lowered` |
| bitwise | `echo (5 & 3) \| (1 << 3);` | `9` | `Lowered` |
| spaceship | `echo 2 <=> 4;` | `-1` | `Lowered` |
| power | `echo 2 ** 3;` | `8` | `Lowered` |
| goto | `goto done;echo 1;done:echo 2;` | `2` | `Expected variable name` |
| String emptiness | `$s="0"; echo (int)empty($s);` | `1` | `0` |

Coverage stops at the examples above and the cited contracts. This pass does not establish general operator-precedence parity, every magic method, every attribute, every catch hierarchy or every array key conversion. The CLI comparison does not exercise HTTP request population, autoloading or production SAPI behavior.
