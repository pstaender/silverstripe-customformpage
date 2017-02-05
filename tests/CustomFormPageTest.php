<?php

class CustomFormPageTest extends SapphireTest {

	protected static $fixture_file = 'CustomFormPageFixture.yml';

	function setUp() {
		parent::setUp();
		$this->logInWithPermission('ADMIN');
	}

	function testCreatingFormWithArbitraryField() {
		$page = $this->objFromFixture('CustomFormPage', 'empty');
		$page->FormDescription = '{{ MyField | Description | Text }}';
		$formFields = $page->FormFields();
		$this->assertArrayHasKey('fields', $formFields);
		$this->assertArrayHasKey('required', $formFields);
		$this->assertCount(1, $formFields['fields']);
		$this->assertNull($formFields['required']);
		$textField = $formFields['fields'][0];
		$this->assertInstanceOf('TextField', $textField);
		$this->assertEquals($textField->getName(), 'MyField');
		$this->assertEquals($textField->Title(), 'Description');
	}

	function testCreatingFormWithRequiredField() {
		$page = $this->objFromFixture('CustomFormPage', 'empty');
		$page->FormDescription = '{{ MyField * | Description | Text }}';
		$formFields = $page->FormFields();
		$this->assertArrayHasKey('fields', $formFields);
		$this->assertArrayHasKey('required', $formFields);
		$required = $formFields['required'];
		$this->assertCount(1, $formFields['fields']);
		$this->assertInstanceOf('RequiredFields', $formFields['required']);
		$textField = $formFields['fields'][0];
		$this->assertInstanceOf('TextField', $textField);
		$this->assertEquals($textField->getName(), 'MyField');
		$this->assertEquals($textField->Title(), 'Description');
		$this->assertInstanceOf('RequiredFields', $required);
		$this->assertCount(1, $required->getRequired());
		$this->assertEquals($required->getRequired()[0], 'MyField');
	}

	function testCreatingFormWithOptions() {
		$page = $this->objFromFixture('CustomFormPage', 'empty');
		$page->FormDescription = '{{ MyField | Description | Dropdown | {"option1": "value1", "option2": "value2"} }}';
		$formFields = $page->FormFields();
		$this->assertArrayHasKey('fields', $formFields);
		$this->assertArrayHasKey('required', $formFields);
		$this->assertCount(1, $formFields['fields']);
		$this->assertNull($formFields['required']);
		$dropDownField = $formFields['fields'][0];
		$this->assertInstanceOf('DropDownField', $dropDownField);
		$this->assertEquals($dropDownField->getSource(), ['option1' => 'value1', 'option2' => 'value2']);
	}

	function testCreatingFormWithMoreThanOneField() {
		$page = $this->objFromFixture('CustomFormPage', 'someFields');
		$formFields = $page->FormFields();
		$this->assertArrayHasKey('fields', $formFields);
		$this->assertArrayHasKey('required', $formFields);
		$required = $formFields['required'];
		$this->assertCount(2, $formFields['fields']);
		$this->assertInstanceOf('RequiredFields', $formFields['required']);
		$textField = $formFields['fields'][0];
		$dropDownField = $formFields['fields'][1];
		$this->assertInstanceOf('TextField', $textField);
		$this->assertInstanceOf('DropDownField', $dropDownField);
		$this->assertEquals($textField->getName(), 'MyField');
		$this->assertEquals($textField->Title(), 'Description');
		$this->assertEquals($dropDownField->getName(), 'MyRequiredField');
		$this->assertEquals($dropDownField->Title(), 'Some Description');
		$this->assertEquals($dropDownField->getSource(), ['option1' => 'value1', 'option2' => 'value2']);
		$this->assertCount(1, $required->getRequired());
		$this->assertEquals($required->getRequired()[0], 'MyRequiredField');
	}

	// TODO: test for not existing Field, html tags and comments


}