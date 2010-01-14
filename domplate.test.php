<?php
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  error_reporting(E_ALL | E_STRICT);
  require_once 'simpletest/autorun.php';
}

require_once 'lib/domplate.inc.php';

class TestOfDomplateParseSelectors extends UnitTestCase {
  function test_can_parse_simple_element() {
    $selector = new domplate_Selector('form');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'form');
  }
  function test_can_parse_element_with_attribute() {
    $selector = new domplate_Selector('form:action');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'form');
    $this->assertEqual($values['attribute'], 'action');
  }
  function test_can_match_element_by_nodename() {
    $selector = new domplate_Selector('input');
    $this->assertTrue($selector->match('<input />'));
  }
  function test_can_parse_element_with_classname() {
    $selector = new domplate_Selector('p.error');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['name'], 'p');
    $this->assertEqual($values['class'], 'error');
  }
  function test_can_match_element_with_classname() {
    $selector = new domplate_Selector('p.error');
    $this->assertTrue($selector->match('<p class="error">'));
  }
  function test_can_parse_id_selector() {
    $selector = new domplate_Selector('#name');
    $values = $selector->getArrayCopy();
    $this->assertEqual($values['id'], 'name');
  }
  function test_can_match_element_by_id() {
    $selector = new domplate_Selector('#name');
    $this->assertTrue($selector->match('<input type="text" name="name" value="" />'));
  }
}

class TestOfDomplateBinding extends UnitTestCase {
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
    $selector = new domplate_Selector('p.error');
    $parts = $selector->slice($this->htmlForm);
    $this->assertEqual(3, count($parts));
    $this->assertEqual($parts[1][1], '<p class="error"></p>');
  }
  function test_can_slice_input_by_name_attribute() {
    $selector = new domplate_Selector('#name');
    $parts = $selector->slice($this->htmlForm);
    $this->assertEqual(3, count($parts));
    $this->assertEqual($parts[1][1], '<input type="text" name="name" value="" />');
  }
  function test_can_bind_text_content() {
    $selector = new domplate_Selector('p.error');
    $this->assertPattern('~<p class="error">Lorem Ipsum</p>~', $selector->bind($this->htmlForm, 'Lorem Ipsum'));
  }
  function test_binding_text_content_escapes_input() {
    $selector = new domplate_Selector('p.error');
    $this->assertPattern('~<p class="error">&lt;b&gt;Lorem&lt;/b&gt; Ipsum</p>~', $selector->bind($this->htmlForm, '<b>Lorem</b> Ipsum'));
  }
  function test_binding_inner_html_embeds_input_unescaped() {
    $selector = new domplate_Selector('p.error!');
    $this->assertPattern('~<p class="error"><b>Lorem</b> Ipsum</p>~', $selector->bind($this->htmlForm, '<b>Lorem</b> Ipsum'));
  }
  function test_can_bind_multiple_values() {
    $selector = new domplate_Selector('p.error');
    $output = $selector->bind($this->htmlForm, array('Lorem', 'Ipsum'));
    $this->assertPattern('~<p class="error">Lorem</p>~', $output);
    $this->assertPattern('~<p class="error">Ipsum</p>~', $output);
  }
  function test_domplate_can_bind_multiple_selectors() {
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
    $output = domplate_bind(
      $this->htmlForm,
      array(
        'form:action' => 'http://example.org',
        'p.error!' => array('<b>Yay</b>', 'Fail'),
        '#name:value' => 'no name'));
    $this->assertEqual(trim($expected), trim($output));
  }
}

class TestOfDomplateBindingToFormUseCase extends UnitTestCase {
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
    $output = domplate_bind(
      $this->htmlForm,
      array(
        'form:action' => 'http://example.org',
        'p.error' => null,
        'p.field!' => array(
          domplate_bind(
            '<input type="text" name="name"/>',
            array(
              ':value' => $name)),
          domplate_bind(
            '<input type="text" name="email"/>',
            array(
              ':value' => $email)),
          '<input type="password" name="password"/>')));
    // TODO: What to assert here???
  }
}
