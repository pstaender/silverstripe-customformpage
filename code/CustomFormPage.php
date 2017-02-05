<?php

class CustomFormPage extends Page {

	private static $db = [
		'FormDescription'               => 'Text',
		'SendToEmailInternal'           => 'Varchar(500)',
		'EmailSubjectInternal'          => 'Varchar(120)',
		'FromEmail'                     => 'Varchar(500)',
		'MessageIfSubmissionSuccessful' => 'Text',
		'MessageIfSubmissionFailed'     => 'Text',
		'DisplayResetButton'            => 'Boolean',
	];

	private static $has_many = [
		'Submissions' => 'CustomFormPageSubmission',
	];

	private static $has_one = [
		'PageOnSuccess' => 'SiteTree',
	];

	private static $icon = "silverstripe-customformpage/images/list.svg";

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$tabNameForm = _t('CustomFormPage.TabForm', 'Form');
		$tabNameOnSubmission = _t('CustomFormPage.TabOnSubmission', 'On Submission');
		$tabNameSubmissions = _t('CustomFormPage.TabSubmissions', 'Submissions');
		$fields->addFieldsToTab('Root.' . $tabNameForm, [
			TextareaField::create('FormDescription', _t('CustomFormPage.FormDescription', 'Form template'))->setRows(50),
			CheckboxField::create('DisplayResetButton', _t('CustomFormPage.DisplayResetButton', 'Display reset button')),
		]);
		$fields->addFieldsToTab('Root.' . $tabNameOnSubmission, [
			TextField::create('EmailSubjectInternal', _t('CustomFormPage.EmailSubjectInternal', 'Internal email subject')),
			TextField::create('SendToEmailInternal', _t('CustomFormPage.SendToEmailInternal', 'Send to internal email address')),
			TextField::create('EmailSubjectExternal', _t('CustomFormPage.EmailSubjectExternal', 'External email subject')),
			TextField::create('FromEmail', _t('CustomFormPage.FromEmail', 'From email')),
			TreeDropdownField::create("PageOnSuccessID", _t('CustomFormPage.PageOnSuccessID', 'Redirect to the following Page after submitting'), "SiteTree"),
			TextareaField::create('MessageIfSubmissionSuccessful', _t('CustomFormPage.MessageIfSubmissionSuccessful', 'Display this message if submission was successful')),
			TextareaField::create('MessageIfSubmissionFailed', _t('CustomFormPage.MessageIfSubmissionFailed', 'Display this message if submission failed')),
		]);
		$config = GridFieldConfig_RecordEditor::create();
		$submissions = GridField::create('CustomFormPageSubmission', _t('CustomFormPage.Submission', 'Submission'), $this->Submissions());
		$submissions->setConfig($config);
		$fields->addFieldToTab('Root.' . $tabNameSubmissions, $submissions);
		return $fields;
	}

	function FormFields() {
		if ($this->FormDescription) {
			$fields = $this->formFieldsFromDescription($this->FormDescription);
			return [
				'fields'   => $fields['fields'],
				'required' => $fields['required'],
			];
		} else {
			return [
				'fields'   => null,
				'required' => null,
			];
		}
	}

	private function formFieldsFromDescription($description) {
		$formFields = null;
		$requiredFields = null;
		if (preg_match_all("/{{(.+?)}}/", $description, $fields)) {
			$formFields = [];
			$requiredFields = [];
			foreach ($fields[1] as $fieldText) {
				$parts = $this->extractParts($fieldText);
				$key = $this->extractKeyFromParts($parts);
				if ($isRequiredKey = $this->requiredFieldKey($key)) {
					$requiredFields[] = $key = $isRequiredKey;
				}
				if (!$key) {
					user_error("You have to set a key for each form field, e.g. Name", E_USER_ERROR);
				}
				$formFields[] = $this->formFieldsFromParts($key, $parts);
			}
		}
		return [
			'fields'   => $formFields,
			'required' => $requiredFields ? RequiredFields::create($requiredFields) : null,
		];
	}

	private function requiredFieldKey($key) {
		if ($key[strlen($key) - 1] === '*') {
			return trim(substr($key, 0, -1));
		} else {
			return null;
		}
	}

	private function extractParts($field) {
		return preg_split("/\\|/", $field);
	}

	private function extractKeyFromParts($parts) {
		return trim($parts[0]);
	}

	private function formFieldsFromParts($key, $parts) {
		$name = (isset($parts[1]) && ($parts[1])) ? $parts[1] : $parts[0];
		$name = trim($name);
		$formFieldName = (isset($parts[2]) && ($parts[2])) ? $parts[2] : 'Text';
		$formFieldClassName = trim($formFieldName) . 'Field';
		$arguments = $this->extractArguments($parts);
		if (class_exists($formFieldClassName)) {
			array_unshift($arguments, $name);
			array_unshift($arguments, $key);
			$formField = new $formFieldClassName(...$arguments);
			// TODO: options
		} else {
			$formField = LiteralField::create('warning_' . $formFieldName, "The class {$formFieldClassName} doesn't exists (given class name: {$formFieldName})");
		}
		return $formField;
	}

	private function extractArguments($fieldTextParts) {
		$arguments = [];
		if (sizeof($fieldTextParts) > 3) {
			for ($i = 3; $i < sizeof($fieldTextParts); $i++) {
				$argument = trim($fieldTextParts[$i]);
				if ($argument[0] === '{' && $argument[strlen($argument) - 1] === '}') {
					#JSON
					$arguments[] = json_decode($argument, true);
				} else if ($argument[0] === '[' && $argument[strlen($argument) - 1] === ']') {
					$arguments[] = json_decode($argument, true);
				} else if (is_numeric($argument)) {
					$arguments[] = (int)$argument;
				} else {
					$arguments[] = $argument;
				}
			}
		}
		return $arguments;
	}

}
