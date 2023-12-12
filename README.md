# Pretzel Image for Craft CMS v3.5+

Pretzel Image generates image transforms at browser request time. The resulting image is stored in a public folder. 
Because the controller route is the same as the actual file, we let the web server serve the actual file before passing
it off to the plugin to process the request.

The generated file can be deleted from the server at any time because it will just be recreated if it no longer exists.

The images are organized into a two character directory, then asset ID subdirectory. This is done to mitigate the 
possibility, of too many directories/files in a single folder. 

The original purpose of this plugin is to place a CDN in front of the site so that these 
generated images would only live a short time on the actual Craft CMS server. 

In practice, this plugin works well for multi-instance Craft CMS setups on high traffic sites. 
By utilizing an NFS server, you can reuse the same generated images between all the servers, then load balance the CMS 
for image generation.

## Configuration

Pretzel should work out of the box, but you can customize it.

### PRETZEL_PATH

By default images are stored in the @web/_imgs directory,  but you can override it in your .env file with PREZTEL_PATH

> PRETZEL_PATH="images"

### PRETZEL_HOSTS

Pretzel will allow images to be generated by any host when in Craft CMS is in Dev Mode, otherwise it will only accept 
referrers from the current defined site url, or hosts you define in:

> PRETZEL_HOSTS="www.somesite.com,help.somesite.com"


## How to use

There are 3 arguments you pass into a *url* method.

1. The Craft CMS Asset
2. Object (or Array) of transformations
3. *(Optional)* Default object of transformations that will be applied to all images

```php
craft.pretzel.url(asset, { width: 100, height: 50 }, { position: asset.getFocalPoint() });
```

#### Twig

```html
{% set asset = craft.assets().one() %}

{% set images = craft.pretzel.url(asset, [
    { width: 600, ratio: 1/1},
    { width: 1024, ratio: 16/9 }
], { position:asset.getFocalPoint()|default('50% 50%') }) %}

<img src="{{ images[0].getUrl() }}" alt="{{ asset.title }}" />
```

or 

```html
{% set asset = craft.assets().one() %}
{% set image = craft.pretzel.url(asset,{ width: 1024, height: 1024 } }) %}

<img src="{{ image.getUrl() }}" alt="{{ asset.title }}" />
```

If multiple transformation configurations are passed, an array is returned instead of a URL string.

#### PHP (Element API)

```php
use adevendorf\pretzelimage\Plugin;

$asset = $entry->image->one();
$image = Plugin::$plugin->pretzelService->url($asset, [
  'width' => 600, 'height' => 360, 'position' => $asset->getFocalPoint()
]);
```

## Image Options

width

---

height:

---

ratio

---

position: 50-50 (default)

---

mode: crop (default), fill


## Requirements

This plugin requires Craft CMS 3.5 or later, and PHP 8.0.2 or later.

### TODO

* Automatically remove old images from the file space based on age of the file.
* Provide an option to never store the generated file.
* Test GraphQL Support

