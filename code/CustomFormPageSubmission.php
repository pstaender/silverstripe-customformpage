<?php

class CustomFormPageSubmission extends \SilverStripe\ORM\DataObject
{

    private static $db = [
        'SubmittedData' => 'Text',
        'SendToEmail' => 'Varchar(500)',
        'IsSended' => 'Boolean',
    ];

    private static $summary_fields = [
        'Created',
        'IsSended',
        'SendToEmail',
        'ListOfSubmittedData',
    ];

    private static $casting = array(
        'ListOfSubmittedData' => 'HTMLText',
    );

    private static $has_one = [
        'Page' => \SilverStripe\CMS\Model\SiteTree::class,
    ];

    private static $belongs_to = [
        'Page' => \SilverStripe\CMS\Model\SiteTree::class,
    ];

    private static $default_sort = "\"ID\" DESC";

    public function addAllowedFormData($data)
    {
        #{{ Captcha |  | Recaptcha }}
        $excluded = $this->config()->excludeParameters ? $this->config()->excludeParameters : [];
        $allowedKeys = $this->Page()->formFieldsFromDescription()['keys'];
        $filteredData = [];
        foreach ($allowedKeys as $key) {
            if (!in_array($key, $excluded)) {
                $filteredData[$key] = $data[$key] ?? null;
            }
        }
        $this->SerializeData($filteredData);
    }

    public function SerializeData($data)
    {
        $this->SubmittedData = json_encode($data, JSON_PRETTY_PRINT);
    }

    public function DeserializeData()
    {
        if ($this->SubmittedData) {
            return json_decode($this->SubmittedData, true);
        } else {
            return null;
        }
    }

    public function DataAsList()
    {
        $data = \SilverStripe\ORM\ArrayList::create();
        if ($this->SubmittedData) {
            foreach ($this->DeserializeData() as $key => $value) {
                $data->push(\SilverStripe\View\ArrayData::create(['Key' => $key, 'Value' => $value]));
            }
        }
        return $data;
    }

    public function ListOfSubmittedData()
    {
        $list = [];
        if ($data = $this->DeserializeData()) {
            foreach ($data as $key => $value) {
                $list[] = "<li><strong>" . $key . ": </strong><code>" . $value . "</code></li>";
            }
        }

        $html = \SilverStripe\ORM\FieldType\DBHTMLText::create();
        $html->setValue("<ul>" . join("\n", $list) . "</ul>");
        return $html;
    }

    public function Data()
    {
        return \SilverStripe\View\ArrayData::create($this->DeserializeData());
    }

}
