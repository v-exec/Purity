# Purity

_Purity_ is a specialized wiki / authoring engine written in PHP. A custom variant of it is used as the back-end for [V-OS](http://v-os.ca).

Generic text files written under appropriate format are parsed through _Purity_'s multi-layered parser into PHP objects called `artifacts`. Each `artifact` corresponds to a page, and once populated, is parsed into its html counterpart, with the capability to apply custom styling to the parsed results.

## Features

At the moment, _Purity_ features:

- A series of attributes per artifact (name, image, image name, title, content, tags, links, file path), easily customizeable and expandable for a different layout through minimal code changes.

- A custom text format for creating artifacts, made for intuitive human-level writing (more information on syntax below).

- A parser for the text format, allowing seamless integration of jpg, png, svg, and gif images, links, lists grouped by tags, access to other artifacts' information, and custom styling of these elements through minimal code changes.

- An object system separate from the layout of each page, allowing _Purity_ to be used as a content management system for various layouts.

- A simple API allowing basic information to be dynamically requested through local javascript.

- The option to create a static instance of _Purity_ - allowing for light hosting.

## Syntax

The syntax for _Purity_'s writing system is quite simple.

#### Attributes

It contains `attributes`, which are declared through the attribute name, followed by a colon, and the information to be attributed to said attribute.

```
name: text

image: directory>imagename

image name: text

tags: tag, tag, tag

links: link, link, link

title: text

content: text
```

All `attributes`, aside from `name` are optional, and can be omitted without issue. They can also be declared in any order.

Some attributes are collected automatically, like the `file path`. Artifact declaration `.txt` files inside folders have their path to the root of _Purity_ saved in their `path` property.

#### Rules

It also contains `rules`, which are inline elements used to format text according to predetermined functions. Most `rules`' formatting can have a custom style, which is applied in the parser.

```
=[tag] link list

-[tag] title list

=[text ++ text0] compact custom list

-[text ++ text0] spacious custom list

?[text] indented quote

~[text] monospaced note

>[code] executable PHP code

![text] subtitle

$[artifact>attribute] reference to artifact's attribute

#[artifact] local link

@[text>link] custom link

&[directory>image name] image

*[text] bold

_[text] italic

%[] divider
```

Any `attributes` that take 'text' (as seen above) are capable of containing any given number of number of elements formatted through `rules`, and any given degree of nested `rules`.

#### Special

Special syntax (syntax that does not conform to the `symbol[data]` format) is very minimal.

`+ line break`

`++ list divider`

`> accessor`

`// comment`

The text written for _Purity_  is _not_ parsed using whitespace, therefore, deliberate line breaks for content and titles must be declared through `+`.

## Additional Information

#### Example

For a real-world example of an entire instance of _Purity_, take a look at the files found in this repo: they showcase a functional and tested example of a working instance. The example instance was made exclusively using HTML (`assets/template.php`), CSS (`/assets/styles/style.css`), and text files (`/pages`), without touching any part of _Purity_ itself.

#### Using the API

_Purity_ has a simple API for requesting basic `artifact` information using client-side Javascript. Inside `/assets`, `requestscript.js` contains a function that formats a request that corresponds to `api.php`'s request standards. Currently, the following can be done through this method:

- Verification of whether or not an `artifact` exists.

- Request to format text into a link if it corresponds to an existing `artifact`.

- Request for a select `artifact`'s `attribute`.

To ensure functionaliy, be sure your server allows AJAX requests.

#### Running _Purity_ In A Subfolder

In the case of running _Purity_ in a subfolder, rather than the root directory of a site, the main/root `.htaccess` must be modified so that:

```
RewriteRule ^(.*)$ $1.php
RewriteRule ^(.+)$ /page.php?v=$1 [NC,L]
```

include the path to the directory _Purity_ is in, like so:

```
RewriteRule ^(.*)$ /subfolder/$1.php
RewriteRule ^(.+)$ /subfolder/page.php?v=$1 [NC,L]
```

#### Exporting a Static Site

To export a static instance of _Purity_, any WAMP/LAMP environment will do.

Simply change the `static` variable in `page.php` to `true`, and all pages' `.html` files will be exported to a `/static` folder. Be sure to make this option `false` if you are hosting a dynamic instance _Purity_ on a site. The path of their links to scripts and images will be from the root directory.

Be sure to double-check your filenames: operating systems' filename encoding may differ for non-standard characters.