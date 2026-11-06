# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Craft CMS 4 plugin (`adevendorf/craft-pretzelimage`, handle `pretzelimage`) that generates image transforms on first browser request and writes them under the public webroot (default `_imgs/`). Later requests for the same URL are served directly by the web server (or a CDN) without touching PHP. Uses Intervention Image v4 (GD or Imagick driver; `PRETZEL_DRIVER=gd|imagick` forces one).

## Commands

There is no test suite and no build step. This is a library plugin, so it is exercised by installing it into a Craft 4 site (e.g. via a composer path repository).

- `composer install` — install dependencies
- `vendor/bin/ecs check` / `vendor/bin/ecs check --fix` — code style (`ecs.php`, `SetList::CRAFT_CMS_4`, scans `src/`). This requires `craftcms/ecs`, which is not in `composer.json`, so install it first.

## Architecture

The key idea is that the **URL is the transform spec**, and the generated file's path must match the URL so the web server can serve it directly next time.

1. **URL generation** (Twig `craft.pretzel.url(asset, transforms, defaults)` via `variables/PretzelVariable`, or `Plugin::$plugin->pretzelService->url()` from PHP). `PretezelService::url` merges transforms with defaults and returns an `ImageModel` (or an array of them if a list of transforms is passed). `ImageModel` computes `url` and `path` through `PretzelHelper::makeFilename` / `folderPath`, giving `/{PRETZEL_PATH}/{md5(first 2 chars of "image_{id}")}/{assetId}/{filename}~{W600_H400_P50-50...}.{ext}`.
2. **Routing** (`Plugin.php`): two site URL rules, `copy` for `.svg` (served unmodified) and `generate` for jpg/png/gif with `~<transforms>`. Both are anonymous routes to `controllers/ImageController`.
3. **Generation** (`ImageController::actionGenerate` → `PretezelService::generateImage`): the transform string in the URL is parsed back into an array by `TransformationHelper::convertStringToArray`, wrapped in `TransformModel`, then `ensureDimensions` and `runCropResize` (adapted from Glide) produce the image. `PretzelHelper::saveImage` writes it to the webroot and the controller streams it.
4. **Transform encoding** lives in `TransformationHelper`. `TRANSFORM_MAPPINGS` maps option names to single-letter prefixes (`W`, `H`, `P`, `Q`, `M`, `F`, `R`, `B`), and `convertArrayToString` / `convertStringToArray` must stay inverses. Changing the format changes every generated URL and cached file. Positions use `-` instead of `.` in the filename (`50-50`, `33-5-20`).

## Configuration

Configuration is **env-only**, read with `getenv()` in `PretzelSettingHelper` / `PretzelHelper`: `PRETZEL_PATH`, `PRETZEL_HOSTS`, `PRETZEL_CDN`, `PRETZEL_SAVE_IMG`. In devMode, `isValidHost` allows any referrer. Otherwise it checks the `Referer` host against `PRETZEL_HOSTS` and the site host.

`models/Settings.php` and `templates/settings.twig` are not wired into `Plugin` (no `createSettingsModel` / `settingsHtml`), so they currently have no effect.

## Known security issues (remaining)

Found in a review; keep in mind when touching the controller or host check:

- URLs are unsigned: width/height are capped at 5000, but anyone can still fill the disk or burn CPU with unique transform combinations.
- `isValidHost` passes a missing `Referer` and uses a substring match; it is hotlink protection only, not CORS. Responses set cache headers but no CORS headers.

## Gotchas

- Class and file are spelled `PretezelService` (typo is established; don't "fix" without updating the `pretzelService` component and its usages).
- Both `saveImage` and `actionCopy` write under `@webroot`.
- `actionGenerate` saves under exactly the requested URL (filename, transform string, lowercased ext) so the web server finds the static file next time. SVGs are never transformed: `actionCopy` only serves `.svg` assets, URLs have no `~` segment.
