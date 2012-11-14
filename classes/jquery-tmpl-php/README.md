jQuery-tmpl-PHP
===============

A PHP library for rendering jQuery templates server-side. Inspired by
[jQuery-tmpl.Net][1] and of course [jQuery Templates][2].

Usage
-----

jQuery-tmpl-PHP was written to mimic to a certain extent the jQuery Templates
method calls. An example file has been included showing various usage cases.

The `jQueryTmpl()` object supports the following method calls:

  * **`getHtml()`**
    * Parameters: None
    * Purpose: Returns the generated HTML in the buffer and clears it.
  * **`renderHtml()`**
    * Parameters: None
    * Purpose: Prints the generated HTML in the buffer and clears it.
  * **`template(name, jQueryTmpl_Markup)`**
    * Parameters:
      * String name for the template.
      * The markup to be compiled.
    * Purpose: Compiles the given template markup.
  * **`tmpl(name/jQueryTmpl_Markup, jQueryTmpl_Data)`**
    * Parameters:
      * String name of a precompiled template or the markup to be compiled.
      * The data to be applied to the template.
    * Purpose: Renders the template with the given data and stores it in the
      output buffer.

Supported Tags
--------------

* **`${property}` and `{{= property}}`**

  Both the shorthand `${}` and `{{= }}` are supported. Will print out the
  value of the indicated property on the provided data object. Nested property
  resolution is supported. However expression/function evaluation is not
  currently supported. (See roadmap.)

* **`{{html property}}`**

  Renders the value of the property without HTML encoding. Otherwise identical
  to `${}`.

* **`{{each(index, value) property}}...{{/each}}`**

  Renders an instance of the tag contents for each item in the property value
  on the provided data object. Custom index and value variables can be
  optionally passed in.

* **`{{if property}}...{{/if}`**

  Renders the content of the tag if the property value on the provided data
  object evaluates to `true`. This is javascript-style evaluation so 0, null,
  empty string are all `false`.

* **`{{else property}}`**

  Used within the `{{if}}` tag to evaluate else conditions. The property value
  is optional.

* **`{{tmpl(data, options) template}}`**

  This tag takes data and options as optional parameters. The tag will render a
  existing rendered template (using the `template()` method) in place. When a
  data property is passed in only the portion of data referenced by that
  property is passed to the template specified. Options is currently not
  supported.

* **`{{! comments}}`**

  This tag does not appear to be documented on the official jQuery site
  however it does exist in code. The same functionality is preserved here,
  comments are simply discarded in rendered output.

Roadmap
-------

The following is on my todo list.

* Support for JavaScript expresion evaluation.
* Support for `{{wrap}}`

  [1]: http://github.com/awhatley/jquery-tmpl.net
  [2]: http://github.com/jquery/jquery-tmpl
