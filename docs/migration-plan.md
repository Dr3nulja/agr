# Migration Plan

## Rule

Do not try to rewrite everything at once. Each stage must be small enough to understand, test, and fix independently.

## Stage 1: Project foundation

- Keep the Laravel skeleton clean and bootable.
- Confirm the app starts with a basic route and view.
- Prepare config files, storage folders, and testing setup.

## Stage 2: Legacy mapping

- Map old controllers, models, utils, and views to Laravel equivalents.
- Identify which classes are shared and which are page-specific.
- Keep a compatibility list for old endpoints.

## Stage 3: Core infrastructure

- Move database access into Laravel models and services.
- Replace the old autoloader and global helpers step by step.
- Introduce auth/session handling in Laravel style.

## Stage 4: Functional slices

- Migrate one workflow at a time.
- After each slice, verify routes, queries, and views.
- Keep the old behavior until the new slice is stable.

## Stage 5: Cleanup

- Remove remaining legacy shims.
- Simplify duplicated code.
- Tighten validation, tests, and UI polish.

## Current focus

Next implementation step should be one small vertical slice, not a broad rewrite.