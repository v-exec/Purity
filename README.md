# Purity

_Purity_ is a specialized wiki / authoring engine written in PHP. A custom variant of it is used as the back-end for [V-OS](http://v-os.ca).

Text files written in the appropriate format are parsed through _Purity_'s multi-layered parser into PHP objects called `artifacts`. Each `artifact` corresponds to a page, and once populated, is parsed into its html counterpart.

In short, _Purity_ is a single-template content management system, made for (me, but also) programmers / people minimally experienced in web dev who:
1. Don't want to deal with creating their own parsing system.
2. Want to create a wiki-site / have a consistent page layout.
3. Want to use a lightweight, simple system.
4. Want to retain control over how things look and behave.

## Features and Functionality

_Purity_ is a content management system, made out of `artifacts`. An `artifact` is a data structure represening a template page, making _Purity_ an appropriate engine for wiki-sites, as wiki pages are typically template-based with unique content displayed on a modular interface.

A PHP/HTML file that acts as a template with placeholders for an `artifact`'s data is called each time a page is requested, and then that template is populated with the `artifact`'s data.

`Artifacts` contain a name, image, image name, title, text, tags, custom links, and a file path. These `artifacts` are generated through text files written in a human-friendly format, with the intent of making writing, editing, and adding new content easy (more information on syntax below).

Using predominantly HTML and CSS, and very limited PHP, one can make a template that displays their content as they please, whilst having _Purity_ deal with parsing the text files.

The parser allows for seamless integration of jpg, png, svg, and gif images, videos, audio files, creation of links (both pointing towards other `artifacts` and other sites, lists grouped by tags, access to other artifacts' information, embedded media, and custom styling of these elements through CSS).

For the more tech-oriented, `artifacts` can also be created procedurally in _Purity_, if ever one wants auto-generated pages, for instance. Furthermore, PHP and _Purity_'s intuitive writing system can be mixed; that is to say, code can be called from the text - making it very useful to expand and implement custom content formatting and generation.

_Purity_ has a simple API allowing basic information to be dynamically requested through client-side javascript.

## Syntax

The syntax for _Purity_'s writing system is quite simple.

#### Attributes

It contains `attributes`, which are the primary constituents of an `artifact`.

These are declared through the attribute name, followed by a colon, and the information to be attributed to said attribute.

All `attributes`, aside from `name` are optional, and can be omitted without issue. They can also be declared in any order.

Some attributes are collected automatically, like the `file path`. The artifact declaration `.txt` files inside folders have their path to the root of _Purity_ saved in their `path` property.

```
name: text

image: directory>imagename

image name: text

tags: tag, tag, tag

links: linkname>link, linkname>link, linkname>link

title: text

content: text
```

#### Rules

It also contains `rules`, which are inline elements used to format text according to predetermined functions.

These are declared through a 'rune' syntax, where the text one wishes to be formatted is between square brackets `[]`, with a symbol before these brackets denoting the type of formatting that should be executed.

Any `attributes` that take 'text' (as seen above) are capable of containing any given number of number of elements formatted through `rules`, and any given degree of nested `rules`.

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

&[directory>image name (optional) ++ text] image (with optional annotation)

^[directory>audio name (optional) ++ text] audio (with optional annotation)

<[directory>video name (optional) ++ text] video (with optional annotation)

*[text] bold

_[text] italic

%[] divider
```

#### Special

Special syntax (syntax that does not conform to the `symbol[data]` format) is very minimal.

The text written for _Purity_  is _not_ parsed using whitespace, therefore, deliberate line breaks for content and titles must be declared through `+`.

`+ line break`

`++ list divider`

`> accessor`

`// comment`

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

In the case of running _Purity_ in a subfolder, rather than the root directory of a site, the main/root `.htaccess` must be modified so that the following lines:

```
RewriteRule ^(.*)$ $1.php
RewriteRule ^(.+)$ /page.php?v=$1 [NC,L]
```

include the path to the directory _Purity_ is in, like so:

```
RewriteRule ^(.*)$ /subfolder/$1.php
RewriteRule ^(.+)$ /subfolder/page.php?v=$1 [NC,L]
```

#### Creating Procedural `Artifacts`

To create an `artifact` procedurally, simply write `$var = new CustomArtifact();`. This data structure is identical to a regular `artifact`, but its attributes are all empty.

It is recommended to understand the anatomy of an `artifact` before creating custom ones. Simply take a look at `assets/artifact.php`.