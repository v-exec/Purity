# Purity

_Purity_ is a specialized wiki / authoring engine written in PHP, and used as the back-end for [V-OS](http://v-os.ca).

Generic text files written under appropriate format are parsed through _Purity_'s multi-layered parser into PHP objects called `artifacts`. Each `artifact` corresponds to a page, and once populated, is parsed into its html counterpart, with the capability to apply custom styling to the parsed results.

## Features

At the moment, _Purity_ features:

- A series of attributes per artifact (name, image, image name, title, content, tags, custom boolean, custom link), easily customizeable and expandable for a different layout through minimal code changes.

- A custom text format for creating artifacts, made for intuitive human-level writing (more information on syntax below).

- A parser for the text format, allowing custom and seamless integration of jpg and png images, links, lists grouped by tags, access to other artifacts' information, and custom styling of these elements through minimal code changes.

- An object system separate from the layout of each page, allowing _Purity_ to be used as a content management system for various layouts.

## Syntax

The syntax for _Purity_'s writing system is quite simple.

#### Attributes

It contains `attributes`, which are declared through the attribute name, followed by a colon, and the information to be attributed to said attribute.

```
name: text

image: directory>imagename

image name: text

white: true/false

github: link

tags: tag, tag, tag...

title: text

content: text
```

All `attributes`, aside from `name` are optional, and can be omitted without issue. They can also be declared in any order.

The attributes `github` and `white` are specific to [V-OS](http://v-os.ca). They can be used as a custom link and a custom boolean.

#### Rules

And it contains `rules`, which are inline elements used to format text according to predetermined functions. Most `rules`' formatting can have a custom style, which is applied in the parser.

```
=[tag] link list

-[tag] title list

=[text ++ text0] compact custom list

-[text ++ text0] spacious custom list

?[text] indented quote

~[text] monospaced note

![text] subtitle

$[artifact>attribute] reference to artifact's attribute

#[project] local link

@[text>link] custom link

&[directory>image name] image

*[text] bold

_[text] italic

%[] div
```

Any `attributes` that take 'text' (as seen above) are capable of containing any given number of number of elements formatted through `rules`, and any given degree of nested `rules`.

#### Special

Special syntax (syntax that does not conform to the `symbol[data]` format) is very minimal.

`+ line break`

`++ list divider`

`> accessor`

`// comment`

The text written for _Purity_  is _not_ parsed using whitespace, therefore, deliberate line breaks for content and titles must be declared through `+`.

#### Example

For a real-world example of an entire instance of _Purity_, take a look at the files found in this repo: they showcase a functional and tested example of a working instance. The example instance was made exclusively using HTML (page.php), CSS (/assets/styles/style.css), and text files (/pages), without touching any part of _Purity_ itself.

Here is an example of an artifact that uses nearly all `rules`. At the moment, only one artifact can be declared per file. Meaning that multiple artifacts must each be declared in a separate file.

```
name: Artifact

image: images>artifact2

white: false

github: https://github.com/user/Project

tags: project, programming

title: An #[artifact] is an object created through #[Purity].

content: Here is a list of all my #[projects]:
+

//this creates a subtitle "Projects Title!", where "projects" is also a link
![#[Projects] Title!]
+

//this creates a link list of all artifacts with the tag 'project'
=[projects]

//and this creates a custom list of text elements, seperated by commas
=[text1, text2, text3]
+

//this fetches the 'title' attribute from the artifact 'projects'
$[projects>title]
+

Here is an @[external link>http://website.com].

+

_[Italic] and *[bold] text.

+

%[text inside a divider is removed during parsing]
+
&[image directory>other image directory>my image]
```