# Upgrade Guide: Moving to Silverstripe 6

This guide outlines the necessary steps and breaking changes required to upgrade the `sunnysideup/page-favourites-bookmarker` module to be compatible with Silverstripe CMS 6.

## ⚠️ BREAKING CHANGE: Core Dependency Updates

The module has been updated to support Silverstripe 6. This requires updating your project's `composer.json` file.

-   **`silverstripe/framework`**: Upgraded from `^5.0` to `^6.0`.
-   **`silverstripe/admin`**: Upgraded from `^2.0` to `^3.0`.
-   **`sunnysideup/sswebpack_engine_only`**: Version constraint updated from `5.x-dev` to `^5.0-dev`.

## ⚠️ BREAKING CHANGE: BuildTask to Symfony Command

The `DeleteAllFavourites` BuildTask has been refactored into a Symfony command to align with Silverstripe 6 conventions.

-   **Old Class**: `Sunnysideup\PageFavouritesBookmarker\Tasks\DeleteAllFavourites`
-   **Execution Change**: The task is no longer executed via `/dev/tasks/deleteallfavourites`.
-   **New Command**: To run the task, you must now use the command line: `sake dev:deleteallfavourites`.

Key changes in `DeleteAllFavourites.php`:
-   The class now extends `SilverStripe\Dev\BuildTask` but uses the `execute` method signature of a Symfony command.
-   The `run` method has been replaced with `protected function execute(InputInterface $input, PolyOutput $output): int`.
-   Output is now handled via the `$output` object (e.g., `$output->writeln('done');`) instead of `echo`.

## API Changes

### PHP 8.1+ Typed Properties and `Override` Attribute

To improve code quality and adhere to modern PHP standards, the following changes have been made across the module's classes. These do not introduce breaking changes but align the code with stricter standards.

-   **Typed Properties**: Class properties that were previously untyped are now explicitly typed (e.g., `protected string $title`).
-   **`#[Override]` Attribute**: The `#[Override]` attribute has been added to methods that implement or override a method from a parent class. This helps ensure method signatures are correct and provides better static analysis.
-   **`use Override;`**: The `Override` class is now imported where necessary.
-   **Constant Typing**: Class constants are now strongly typed (e.g., `private const int DAYS_BACK_TRENDING = 7;`).

These changes have been applied to the following files:
-   `src/Admin/BookmarkAdmin.php`
-   `src/Control/BookmarkController.php`
-   `src/Model/Bookmark.php`
-   `src/Model/BookmarkList.php`
-   `src/Model/BookmarkUrl.php`
