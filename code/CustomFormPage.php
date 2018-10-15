<?php

class CustomFormPage extends Page
{

    private static $db = [
        'FormDescription' => 'Text',
        'SendToEmailInternal' => 'Varchar(500)',
        'EmailSubjectInternal' => 'Varchar(120)',
        'FromEmail' => 'Varchar(500)',
        'MessageIfSubmissionSuccessful' => 'Text',
        'MessageIfSubmissionFailed' => 'Text',
        'DisplayResetButton' => 'Boolean',
    ];

    private static $has_many = [
        'Submissions' => 'CustomFormPageSubmission',
    ];

    private static $has_one = [
        'PageOnSuccess' => \SilverStripe\CMS\Model\SiteTree::class,
    ];

    private static $icon = "vendor/pstaender/silverstripe-customformpage/images/list.svg";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $tabNameForm = _t('CustomFormPage.TabForm', 'Form');
        $tabNameOnSubmission = _t('CustomFormPage.TabOnSubmission', 'On Submission');
        $tabNameSubmissions = _t('CustomFormPage.TabSubmissions', 'Submissions');
        $fields->addFieldsToTab('Root.' . $tabNameForm, [
            \SilverStripe\Forms\TextareaField::create('FormDescription', _t('CustomFormPage.FormDescription', 'Form template'))->setRows(50),
            \SilverStripe\Forms\CheckboxField::create('DisplayResetButton', _t('CustomFormPage.DisplayResetButton', 'Display reset button')),
        ]);
        $fields->addFieldsToTab('Root.' . $tabNameOnSubmission, [
            \SilverStripe\Forms\TextField::create('EmailSubjectInternal', _t('CustomFormPage.EmailSubjectInternal', 'Internal email subject')),
            \SilverStripe\Forms\TextField::create('SendToEmailInternal', _t('CustomFormPage.SendToEmailInternal', 'Send to internal email address')),
            \SilverStripe\Forms\TextField::create('EmailSubjectExternal', _t('CustomFormPage.EmailSubjectExternal', 'External email subject')),
            \SilverStripe\Forms\TextField::create('FromEmail', _t('CustomFormPage.FromEmail', 'From email')),
            \SilverStripe\Forms\TreeDropdownField::create("PageOnSuccessID", _t('CustomFormPage.PageOnSuccessID', 'Redirect to the following Page after submitting'), \SilverStripe\CMS\Model\SiteTree::class),
            \SilverStripe\Forms\TextareaField::create('MessageIfSubmissionSuccessful', _t('CustomFormPage.MessageIfSubmissionSuccessful', 'Display this message if submission was successful')),
            \SilverStripe\Forms\TextareaField::create('MessageIfSubmissionFailed', _t('CustomFormPage.MessageIfSubmissionFailed', 'Display this message if submission failed')),
        ]);
        $config = \SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor::create();
        $submissions = \SilverStripe\Forms\GridField\GridField::create('CustomFormPageSubmission', _t('CustomFormPage.Submission', 'Submission'), $this->Submissions());
        $submissions->setConfig($config);
        $fields->addFieldToTab('Root.' . $tabNameSubmissions, $submissions);
        return $fields;
    }

    public function FormFields()
    {
        if ($this->FormDescription) {
            $fields = $this->formFieldsFromDescription();
            return [
                'fields' => $fields['fields'],
                'required' => $fields['required'],
            ];
        } else {
            return [
                'fields' => null,
                'required' => null,
            ];
        }
    }

    private function extractFormDefintionFromLine($line, array &$formFields, array &$requiredFields, array &$keys)
    {
        $parts = $this->extractParts($line);
        $key = $this->extractKeyFromParts($parts);
        if ($isRequiredKey = $this->requiredFieldKey($key)) {
            $requiredFields[] = $key = $isRequiredKey;
        }
        if (!preg_match('/^[a-zA-Z0-9\_\-]+$/', $key)) {
            if (!\SilverStripe\Control\Director::isLive()) {
                user_error("Invalid key name '$key', please use alphanumeric latin letters only", E_USER_ERROR);
            }
        } elseif (!$key) {
            if (!\SilverStripe\Control\Director::isLive()) {
                user_error("You have to set a key for each form field, e.g. Name", E_USER_ERROR);
            }
        } else {
            $keys[] = $key;
            $formFields[] = $this->formFieldsFromParts($key, $parts);
        }
    }

    private static function html_to_form_field($html)
    {
        $html = trim(implode("\n", $html));
        return (strlen($html) > 0) ? \SilverStripe\Forms\LiteralField::create('CustomFormHTMLField' . rand(1, 99999), $html) : null;
    }

    public function formFieldsFromDescription()
    {
        $description = $this->FormDescription;
        $formFields = [];
        $requiredFields = [];
        $keys = [];
        $lines = [];
        $html = [];
        foreach (explode("\n", $description) as $line) {
            $lines[] = $line = trim($line);
            if (preg_match('/^#/', $line)) {
                // skip comments
                continue;
            } elseif (preg_match('/^\<.+?\>$/', $line, $parts)) {
                // html code
                $html[] = $line;
            } else {
                if (preg_match("/^{{(.+?)}}/", $line, $parts)) {
                    $this->extractFormDefintionFromLine($parts[1], $formFields, $requiredFields, $keys);
                }
                if ($literalFormField = self::html_to_form_field($html)) {
                    $formFields[] = $literalFormField;
                    $html = [];
                }
            }
        }
        if ($literalFormField = self::html_to_form_field($html)) {
            $formFields[] = $literalFormField;
            $html = [];
        }
        return [
            'fields' => $formFields,
            'required' => $requiredFields ? \SilverStripe\Forms\RequiredFields::create($requiredFields) : null,
            'keys' => $keys,
        ];
    }

    private function requiredFieldKey($key)
    {
        if ($key[strlen($key) - 1] === '*') {
            return trim(substr($key, 0, -1));
        } else {
            return null;
        }
    }

    private function extractParts($field)
    {
        return preg_split("/\\|/", $field);
    }

    private function extractKeyFromParts($parts)
    {
        return trim($parts[0]);
    }

    private function formFieldsFromParts($key, $parts)
    {
        $name = (isset($parts[1]) && ($parts[1])) ? $parts[1] : $parts[0];
        $name = trim($name);
        $formFieldName = (isset($parts[2]) && ($parts[2])) ? $parts[2] : 'Text';
        $formFieldName = trim($formFieldName);
        if ($formFieldName[0] !== '\\') {
            // automatic put into silverstripe form namespace if no absolute namespace is used
            $formFieldClassName = '\\SilverStripe\\Forms\\' . $formFieldName;
            preg_match("/Field$/", $formFieldName, $matches);
            if (sizeof($matches) === 0) {
                $formFieldClassName .= 'Field';
            }
        } else {
            $formFieldClassName = $formFieldName;
        }
        $arguments = $this->extractArguments($parts);
        if (class_exists($formFieldClassName)) {
            array_unshift($arguments, $name);
            array_unshift($arguments, $key);
            $formField = new $formFieldClassName(...$arguments);
            // TODO: options
        } else {
            throw new Exception("The class {$formFieldClassName} doesn't exists (given class name: {$formFieldName})");
        }
        return $formField;
    }

    private function extractArguments($fieldTextParts)
    {
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

    public function CustomForm($controller)
    {
        $formFields = $this->FormFields();
        if ($formFields['fields']) {
            $fields = $formFields['fields'];
            $required = $formFields['required'];
            $submit = \SilverStripe\Forms\FormAction::create('doSubmitForm', _t('CustomFormPage.Submit', 'Submit'));

            $actions = \SilverStripe\Forms\FieldList::create();
            if ($this->DisplayResetButton) {
                $actions->push(
                    \SilverStripe\Forms\FormAction::create('')->setAttribute('type', _t('CustomFormPage.Reset', 'Reset'))
                );
            }
            $actions->push($submit);
            $form = \SilverStripe\Forms\Form::create(
                $controller,
                'CustomForm',
                \SilverStripe\Forms\FieldList::create($fields),
                $actions
            );
            if ($required) {
                $form->setValidator($required);
            }
            return $form;
        }
    }

}
