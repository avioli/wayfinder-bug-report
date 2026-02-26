# Wayfinder doesn't resolve camelCase route handler parameters

## Description

When a route handler uses a parameter variable that uses camelCase - the resulting route type does not include the Model's key name when generated.


## Required software

 - PHP version: 8.5.3
 - Laravel version: 12.53.0
 - WayFinder version: 0.1.14


## Steps to reproduce:

Reproduction repo: https://github.com/avioli/wayfinder-bug-report

Just clone above repo, then:
 - run `npm install && npm run build`
 - review `./resources/js/routes/wayfinder-bugs/index.ts` and look at the signatures of `show.url` and `bugFix.url`
 - `show.url` doesn't support an object + primary key, since the route handler uses camelCase `$wayfinderBug` param
 - `bugFix.url` does support an object + primary key, since the route handler uses snake_case `$wayfinder_bug` param

To recreate from scratch:

```sh
# make sure to have php, composer, laravel/installer, and npm pre-installed
laravel new wayfinder-bug-report --vue -n
cd wayfinder-bug-report
npm install
php artisan make:model -cmr WayfinderBug # A model with a resource-controller and a migration
npm run build
```

Review `./resources/js/routes/wayfinder-bugs/index.ts` and every handler that takes `wayfinder_bug` as a route param only handles a string or number value. For example - `show`:

```ts
/**
* @see \App\Http\Controllers\WayfinderBugController::show
* @see app/Http/Controllers/WayfinderBugController.php:37
* @route '/wayfinder-bugs/{wayfinder_bug}'
*/
export const show = (args: { wayfinder_bug: string | number } | [wayfinder_bug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
```

The `show.url` function is "correctly" handling only the possible variants of string and number:

```ts
/**
* @see \App\Http\Controllers\WayfinderBugController::show
* @see app/Http/Controllers/WayfinderBugController.php:37
* @route '/wayfinder-bugs/{wayfinder_bug}'
*/
show.url = (args: { wayfinder_bug: string | number } | [wayfinder_bug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { wayfinder_bug: args }
    }

    if (Array.isArray(args)) {
        args = {
            wayfinder_bug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        wayfinder_bug: args.wayfinder_bug,
    }

    return show.definition.url
            .replace('{wayfinder_bug}', parsedArgs.wayfinder_bug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}
```

Change any handler's parameter from `$wayfinderBug` to `$wayfinder_bug` and run `npm run build` - now that route handler also takes an object with an `id`, since the model and its key were resolved correctly.

I've added a `bugFix` route handler to demonstrate. The resulting wayfinder route has the expected signature:

```ts
/**
* @see \App\Http\Controllers\WayfinderBugController::bugFix
* @see app/Http/Controllers/WayfinderBugController.php:42
* @route '/wayfinder-bugs/{wayfinder_bug}/bugfix'
*/
export const bugFix = (args: { wayfinder_bug: string | number | { id: string | number } } | [wayfinder_bug: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bugFix.url(args, options),
    method: 'get',
})
```

The `bugFix.url` function handles the `object` case as well:

```ts
/**
* @see \App\Http\Controllers\WayfinderBugController::bugFix
* @see app/Http/Controllers/WayfinderBugController.php:42
* @route '/wayfinder-bugs/{wayfinder_bug}/bugfix'
*/
bugFix.url = (args: { wayfinder_bug: string | number | { id: string | number } } | [wayfinder_bug: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { wayfinder_bug: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { wayfinder_bug: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            wayfinder_bug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        wayfinder_bug: typeof args.wayfinder_bug === 'object'
        ? args.wayfinder_bug.id
        : args.wayfinder_bug,
    }

    return bugFix.definition.url
            .replace('{wayfinder_bug}', parsedArgs.wayfinder_bug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}
```
