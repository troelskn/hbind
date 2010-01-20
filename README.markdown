hbind -- `sprintf` for HTML
===

API
---

For most part, you'll use just one function of the library:

    hbind($template, $parameters = array())

* `$template` is a string of HTML that is used as the source.
* `$parameters` array is an associative array of *selector* => *replacement data*

Selector syntax
---

A hbind selector can scan html and replace parts of it. It's a bit like a regular expression, but tailored to HTML.

Syntax for selectors are similar to [CSS selectors](http://www.w3.org/TR/CSS2/selector.html), popularised by Javascript libraries such as [jQuery](http://jquery.com/). To select an element, you can use:

* `#foo`    Selects an element with an `id` or `name` attribute equal to "foo"
* `.foo`    Selects elements with a `class` attribute containing "foo"
* `img`     Selects elements of type `img`
* `p.foo`   Selects elements of type `p`, with a `class` attribute containing "foo"

Selector target
---

A hbind selector has a target to manipulate. By default, input data is bound to a text-node, as the sole content of the selected node (Eg. the `innerText` property of the selected element).

A selector will default to escape HTML special characters, but some times you want to mix in raw HTML markup. You can do this by prefixing the selector with `!`.

If you want to replace the element entirely, rather than just the contents, you can prefixing the selector with `+`. When using this modifier, the replacement is always raw (Eg. it implies `!`).

The selector may contain an *attribute*, following the identifier. If an attribute is supplied, the value of this is manipulated ratehr than the element it self.

To summarise the available targets, the syntax is:

* `foo`          Targets the `innerText` of elements of type `foo`
* `foo!`         Targets the `innerHTML` of elements of type `foo`
* `foo+`         Targets the `outerHTML` of elements of type `foo`
* `foo:value`    Targets the `value` attribute of elements of type `foo`

Replacement data
---

Each selector is given some data to replace/update with. This can either be a string or an array of strings. If given an array, the element will be duplicated.

You can also pass `NULL` (or an empty array) as data, in which case, the element is removed from the template.

Examples
===

Basic replacement
---

    $html = "<html><head><title>No Title</title></head><body><h1>No title</h1></body></html>";
    echo hbind($html, array('title' => 'Example', 'h1' => 'This is an example'));

###Output

    <html><head><title>Example</title></head><body><h1>This is an example</h1></body></html>

Repetition
---

    $data = array("Red", "Green", "Blue");
    $html = '<ul><li></li></ul>';
    echo hbind($html, array('li' => $data));

###Output

    <ul><li>Red</li><li>Green</li><li>Blue</li></ul>

Form
---

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

    echo hbind(
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