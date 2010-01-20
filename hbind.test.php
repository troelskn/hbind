<?php
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  error_reporting(E_ALL | E_STRICT);
  require_once 'simpletest/autorun.php';
}

require_once 'lib/hbind.inc.php';

class TestOfHbindParseSelectors extends UnitTestCase {
  function test_can_parse_simple_element() {
    $selector = new hbind_Selector('form');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'form');
  }
  function test_can_parse_element_with_attribute() {
    $selector = new hbind_Selector('form:action');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'form');
    $this->assertEqual($values['attribute'], 'action');
  }
  function test_can_match_element_by_nodename() {
    $selector = new hbind_Selector('input');
    $this->assertTrue($selector->match('<input />'));
  }
  function test_can_parse_element_with_classname() {
    $selector = new hbind_Selector('p.error');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'p');
    $this->assertEqual($values['class'], 'error');
  }
  function test_can_match_element_with_classname() {
    $selector = new hbind_Selector('p.error');
    $this->assertTrue($selector->match('<p class="error">'));
  }
  function test_can_parse_id_selector() {
    $selector = new hbind_Selector('#name');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['id'], 'name');
  }
  function test_can_match_element_by_id() {
    $selector = new hbind_Selector('#name');
    $this->assertTrue($selector->match('<input type="text" name="name" value="" />'));
  }
}

class TestOfHbindBinding extends UnitTestCase {
  function setUp() {
    $this->htmlForm = '
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
';
  }
  function test_can_slice_input_by_classname() {
    $selector = new hbind_Selector('p.error');
    $parts = $selector->slice($this->htmlForm);
    $this->assertEqual(3, count($parts));
    $this->assertEqual($parts[1][1], '<p class="error"></p>');
  }
  function test_can_slice_input_by_name_attribute() {
    $selector = new hbind_Selector('#name');
    $parts = $selector->slice($this->htmlForm);
    $this->assertEqual(3, count($parts));
    $this->assertEqual($parts[1][1], '<input type="text" name="name" value="" />');
  }
  function test_can_bind_text_content() {
    $selector = new hbind_Selector('p.error');
    $this->assertPattern('~<p class="error">Lorem Ipsum</p>~', $selector->bind($this->htmlForm, 'Lorem Ipsum'));
  }
  function test_binding_text_content_escapes_input() {
    $selector = new hbind_Selector('p.error');
    $this->assertPattern('~<p class="error">&lt;b&gt;Lorem&lt;/b&gt; Ipsum</p>~', $selector->bind($this->htmlForm, '<b>Lorem</b> Ipsum'));
  }
  function test_binding_inner_html_embeds_input_unescaped() {
    $selector = new hbind_Selector('p.error!');
    $this->assertPattern('~<p class="error"><b>Lorem</b> Ipsum</p>~', $selector->bind($this->htmlForm, '<b>Lorem</b> Ipsum'));
  }
  function test_can_bind_multiple_values() {
    $selector = new hbind_Selector('p.error');
    $output = $selector->bind($this->htmlForm, array('Lorem', 'Ipsum'));
    $this->assertPattern('~<p class="error">Lorem</p>~', $output);
    $this->assertPattern('~<p class="error">Ipsum</p>~', $output);
  }
  function test_hbind_can_bind_multiple_selectors() {
    $expected = '
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
';
    $output = hbind(
      $this->htmlForm,
      array(
        'form:action' => 'http://example.org',
        'p.error!' => array('<b>Yay</b>', 'Fail'),
        '#name:value' => 'no name'));
    $this->assertEqual(trim($expected), trim($output));
  }
  function test_can_bind_to_singlet_tag() {
    $selector = new hbind_Selector('p');
    $output = $selector->bind('<div><p/></div>', 'Lorem Ipsum');
    $this->assertPattern('~<p>Lorem Ipsum</p>~', $output);
  }
  function test_can_bind_to_singlet_tag_with_attributes() {
    $selector = new hbind_Selector('p');
    $output = $selector->bind('<div><p foo="bar"/></div>', 'Lorem Ipsum');
    $this->assertPattern('~<p foo="bar">Lorem Ipsum</p>~', $output);
  }
  function test_can_bind_to_nested_tag() {
    $selector = new hbind_Selector('div.foo');
    $output = $selector->bind('<div><div class="foo"></div></div>', 'Lorem Ipsum');
    $this->assertEqual('<div><div class="foo">Lorem Ipsum</div></div>', $output);
  }
}

class TestOfHbindBindingToFormUseCase extends UnitTestCase {
  function setUp() {
    $this->htmlForm = '
<form method="post">
<p class="error"></p>
<p class="field"></p>
<p>
  <input type="submit" id="login" />
</p>
</form>
';
  }
  function test_bind_initial_values() {
    $name = "Troels";
    $email = "troelskn@gamil.com";
    $output = hbind(
      $this->htmlForm,
      array(
        'form:action' => 'http://example.org',
        'p.error' => null,
        'p.field!' => array(
          hbind(
            '<input type="text" name="name"/>',
            array(
              ':value' => $name)),
          hbind(
            '<input type="text" name="email"/>',
            array(
              ':value' => $email)),
          '<input type="password" name="password"/>')));
    // TODO: What to assert here???
    // var_dump($output);
  }
}
