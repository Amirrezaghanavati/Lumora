# Routing System — Improvement Guide

> Goal: evolve the current Laravel 5.8–inspired router toward **Laravel 12 conventions**,
> applying PHP best practices (PSR-4, PSR-12, SOLID, DRY) along the way.
>
> This document is a **roadmap**, not a patch. Work through it in phases — each phase
> leaves the app in a working state.

---

## 1. Where the code stands today

**Flow:** `public/index.php` → `bootstrap/app.php` → loads config + `routes/web.php` + `routes/api.php` → `System\Router\Routing::run()`.

| File | Role |
|------|------|
| [`config/app.php`](../config/app.php) | Defines constants + the global `$routes` array |
| [`system/Router/Web/Route.php`](../system/Router/Web/Route.php) | Static registrar, web |
| [`system/Router/Api/Route.php`](../system/Router/Api/Route.php) | Static registrar, api (duplicated) |
| [`system/Router/Routing.php`](../system/Router/Routing.php) | Match + dispatch (God class) |
| [`routes/web.php`](../routes/web.php) | Route definitions |

The design works for simple cases, but it relies on global state, duplicates logic,
mixes responsibilities, and has a few outright bugs. The sections below rank the
problems and show the target shape.

---

## 2. Bugs to fix first (before any refactor)

These are correctness issues, independent of architecture:

1. **Typo in route param** — [`routes/web.php:10`](../routes/web.php) has `/destroy/{id]`
   (closing `]` instead of `}`). It will never match as a parameter.

2. **API router clobbers the web router.** `Web\Route::get()` appends with
   `$routes['get'][] = [...]`, but `Api\Route::get()` overwrites with
   `$routes['get']['url'] = ...`. Both write the **same** global key, so loading
   `api.php` corrupts the `get`/`post` buckets that `web.php` filled. The two
   registrars are structurally incompatible.

3. **Undefined-index risk.** [`Routing::match()`](../system/Router/Routing.php) does
   `$this->routes[$this->methodField]` with no guard. A `PATCH`/`OPTIONS` request
   (or any verb you haven't pre-seeded) triggers an undefined-key warning.

4. **Positional parameter binding.** `compare()` pushes matched segments into
   `$this->values[]` by position. It never binds them to the controller method's
   parameter *names*, and `run()` only checks the **count** of params
   (`count($this->values) >= $params`). Reordered or named params break silently.

5. **Dead `name` argument.** Every route accepts a `$name`, but nothing ever reads
   it — there is no `route('index')` URL generator.

---

## 3. Principle-level problems (the "why" behind the refactor)

| Smell | Where | Best-practice violated |
|-------|-------|------------------------|
| Global mutable state (`global $routes`) | everywhere | SOLID (DIP), testability |
| Two near-identical `Route` classes | Web + Api | **DRY** |
| `run()` does matching + I/O + reflection + 404 | `Routing.php` | **SRP** / God class |
| `echo` in controllers, `include` a view in router | `HomeController`, `Routing::error404()` | **No direct output** |
| Magic constants for config (`BASE_DIR`, `APP_NAME`) | `config/*.php` | **No hardcoding**, config-as-data |
| `function foo(){` brace on same line | `Web/Route.php` etc. | **PSR-12** (method brace on next line) |
| Untyped properties & params (`$url`, `$routes`) | all router files | Type safety |
| Manual `file_exists()` + path string-building | `Routing::run()` | Redundant — PSR-4 autoload already does this |
| No middleware, groups, prefixes | — | Missing Laravel core concepts |

---

## 4. Target architecture (Laravel 12 shape)

Laravel's router is an **object** resolved from the container, not a global. The
pieces you want to grow toward:

```
system/
└── Routing/
    ├── Router.php          # registers + dispatches (replaces global $routes + both Route classes)
    ├── Route.php           # a value object: method, uri, action, name, middleware, wheres
    ├── RouteCollection.php  # holds Route objects, finds matches
    ├── RouteGroup.php       # prefix / middleware / name groups
    └── Contracts/
        └── Dispatcher.php   # interface for dispatching a matched route
system/
└── Http/
    ├── Request.php         # wraps $_SERVER/$_GET/$_POST (no superglobals in logic)
    └── Response.php        # status, headers, body — controllers RETURN this
```

Key shifts from today:

- **One** `Route` facade/registrar, backed by a single `Router` instance.
- Controllers **return** a `Response` (or string/array); the router echoes — never the controller.
- Route params are **bound by name** via reflection, with optional constraints.
- `web.php` / `api.php` differ by **config** (middleware group, prefix), not by class.

---

## 5. Step-by-step plan

### Phase 0 — Hygiene (no behavior change)
- [ ] Fix the bugs in §2.
- [ ] Apply PSR-12: one statement per line, method opening brace on its own line,
      4-space indent. Add **type declarations** to every property, parameter, and
      return. Consider adding a `composer require --dev` of a linter
      (`squizlabs/php_codesniffer` with the `PSR12` standard, or `laravel/pint`).
- [ ] Add a `.gitignore` entry for `/vendor` and `.idea` if not already ignored.

### Phase 1 — Kill the global, unify the registrar
Replace `global $routes` and the two `Route` classes with a single `Router`.

```php
// system/Routing/Router.php
namespace System\Routing;

final class Router
{
    /** @var array<string, Route[]> */
    private array $routes = [];

    public function get(string $uri, array|string $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|string $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    // put(), patch(), delete() ...

    private function addRoute(string $method, string $uri, array|string $action): Route
    {
        $route = new Route($method, trim($uri, '/'), $action);
        $this->routes[$method][] = $route;

        return $route; // returned so you can chain ->name() / ->middleware()
    }
}
```

The `Route` becomes a small value object instead of an array:

```php
// system/Routing/Route.php
namespace System\Routing;

final class Route
{
    private ?string $name = null;
    /** @var string[] */
    private array $middleware = [];
    /** @var array<string, string> */
    private array $wheres = [];

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array|string $action, // [Controller::class, 'method'] OR 'Controller@method'
    ) {}

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(string ...$middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function where(string $param, string $pattern): static
    {
        $this->wheres[$param] = $pattern;
        return $this;
    }
}
```

Now the registration reads like Laravel 12:

```php
// routes/web.php
use System\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/edit/{id}', [HomeController::class, 'edit'])
    ->where('id', '[0-9]+')
    ->name('edit');
```

> Prefer the **array callable** `[HomeController::class, 'method']` (Laravel 8+) over
> the `'Controller@method'` string. Support the string too for back-compat, but make
> the array form the documented default.

`bootstrap/app.php` creates the single instance and injects it (no `global`):

```php
$router = new System\Routing\Router();
require __DIR__ . '/../routes/web.php'; // these reference the $router via a facade/closure
$router->dispatch(Request::capture())->send();
```

### Phase 2 — Match by name, add constraints
Split the God method into focused pieces:

- `RouteCollection::match(Request $request): ?Route` — pure matching.
- `Route::matches(string $uri): bool` + `Route::bindParameters(string $uri): array`
  — compile `{id}` to a regex (honoring `where()`), return an **associative**
  `['id' => 5]` map.
- Dispatch binds those named params to the controller method via `ReflectionMethod`
  → `$parameters[$name]`, so order/optional params work.

This replaces the positional `$this->values[]` logic and the count-only check.

### Phase 3 — Request / Response objects (no more `echo`)
- `System\Http\Request` wraps the superglobals **once** and exposes
  `method()`, `uri()`, `input()`. Handle `_method` spoofing here (read
  `$_POST['_method']` and uppercase it) — one place, all verbs, no nested `if`.
- `System\Http\Response` holds status/headers/body and has `send()`.
- Controllers **return** data:

```php
public function index(): Response
{
    return Response::make('index');      // or view(...), or json([...])
}
```

- `error404()` returns a `Response` with status 404 whose body is the rendered
  `404.php` (use output buffering: `ob_start(); include ...; return ob_get_clean();`),
  instead of `include` + `exit()` inside the router.

### Phase 4 — Middleware
Give `Route` (and groups) a middleware pipeline. A middleware is an invokable that
receives the `Request` and a `$next` closure — the Laravel onion model. This is where
auth, CSRF (the real home for `_method`/CSRF checks), and throttling live.

### Phase 5 — Groups, prefixes, and `route()` helper
- `Router::group(['prefix' => 'api', 'middleware' => 'api'], fn() => require 'routes/api.php')`.
  Now `api.php` is just a grouped set of web-style routes — the duplicate `Api\Route`
  class disappears entirely.
- Build a `UrlGenerator` so the long-dead `name` finally pays off:
  `route('edit', ['id' => 5])` → `/edit/5`.

### Phase 6 — Config instead of constants
Move `BASE_URL`, `APP_NAME`, DB creds out of `const`/`define()` into a `config()`
repository backed by an `.env` file (`vlucas/phpdotenv`). Never commit real
credentials — [`config/database.php`](../config/database.php) currently hardcodes
`root`/`password`; that should come from `env('DB_PASSWORD')`.

---

## 6. Suggested commit sequence

1. `fix: correct route param typos and undefined-index guards` (Phase 0 bugs)
2. `style: apply PSR-12 and add type declarations`
3. `refactor: introduce Router + Route value object, remove global $routes`
4. `feat: bind route parameters by name with where() constraints`
5. `feat: add Request/Response, stop echoing from controllers`
6. `feat: middleware pipeline`
7. `feat: route groups, prefixes, and route() url generation`
8. `refactor: move config/constants into env-based config repository`

Each step is independently shippable and testable.

---

## 7. Quick reference — before vs. after

**Today**
```php
Route::get('/edit/{id}', 'HomeController@edit', 'edit');   // string action, dead name
// controller:
public function edit($id) { echo 'edit'; }                 // echoes
```

**Target (Laravel 12 style)**
```php
Route::get('/edit/{id}', [HomeController::class, 'edit'])
    ->where('id', '[0-9]+')
    ->name('posts.edit');
// controller:
public function edit(int $id): Response
{
    return Response::view('posts.edit', ['id' => $id]);     // returns
}
```

---

## 8. Testing note

Once the global is gone and `Router`/`Request` are plain objects, you can unit-test
routing without a web server:

```php
$router = new Router();
$router->get('/edit/{id}', [HomeController::class, 'edit'])->where('id', '[0-9]+');

$response = $router->dispatch(Request::create('GET', '/edit/5'));
$this->assertSame(200, $response->status());
```

Add `phpunit/phpunit` as a dev dependency and create a `tests/` directory. The
inability to test the current router (because of `global $routes` and `exit()`) is
itself the strongest signal for why this refactor is worth doing.
