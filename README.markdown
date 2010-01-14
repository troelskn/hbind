Domplate -- `sprintf` for HTML
===

API
---

For most part, you'll use just one function of the library:

    domplate_bind($template, $parameters = array())

* `$template` is a string of HTML that is used as the source.
* `$parameters` array is an associative array of *selector* => *replacement data*

Selector syntax
---

A domplate selector can scan html and replace parts of it. It's a bit like a regular expression, but tailored to HTML.

Syntax for selectors are similar to [CSS selectors](http://www.w3.org/TR/CSS2/selector.html), popularised by Javascript libraries such as [jQuery](http://jquery.com/). To select an element, you can use:

* `#foo`    Selects an element with an `id` or `name` attribute equal to "foo"
* `.foo`    Selects elements with a `class` attribute containing "foo"
* `img`     Selects elements of type `img`
* `p.foo`   Selects elements of type `p`, with a `class` attribute containing "foo"

Selector target
---

A domplate selector has a target to manipulate. By default, input data is bound to a text-node, as the sole content of the selected node (Eg. the `innerText` property of the selected element).

The selector may contain an *attribute axis*, following the identifier. If an axis is supplied, it denotes the attribute to manipulate.

Syntax for axis is:

* `foo:value`    Targets the `value` attribute of an element `foo`

A selector will default to escape HTML specialcharacters, but some times you want to mix in raw HTML markup. This is supported with the following syntax:

* `foo!`    Sets the `innerHTML` of elements of type `foo`

Replacement data
---

Each selector is given some data to replace/update with. This can either be a string or an array of strings. If given an array, the element will be duplicated.

You can also pass `NULL` (or an empty array) as data, in which case, the element is removed from the template.

Examples
===

###form.tpl.html

    <form method="post">
    <p class="error"></p>
    <p>
      <input type="text" name="name" value="" />
    </p>
    <p>
      <input type="password" name="password" />
    </p>
    <p>
      <input type="submit" id="login" />
    </p>
    </form>

###form.php

    echo domplate_bind(
      file_get_contents('form.tpl.html'),
      array(
        'form:action' => 'http://example.org',
        'p.error!' => array('<b>Yay</b>', 'Fail'),
        '#name:value' => 'no name'));

###Output

    <form action="http://example.org" method="post">
    <p class="error"><b>Yay</b></p><p class="error">Fail</p>
    <p>
      <input type="text" name="name" value="no name" />
    </p>
    <p>
      <input type="password" name="password" />
    </p>
    <p>
      <input type="submit" id="login" />
    </p>
    </form>