<?php

class CustomFormPageSubmission extends DataObject {

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
		'ListOfSubmittedData' => 'HTMLText'
	);

	private static $has_one = [
		'Page' => 'SiteTree',
	];

	private static $belongs_to = [
		'Page' => 'SiteTree',
	];

	private static $default_sort = "\"ID\" DESC";

	function SerializeData($data) {
		foreach ($this->config()->excludeParameters as $exclude) {
			if (isset($data[$exclude])) unset($data[$exclude]);
		}
		$this->SubmittedData = json_encode($data, JSON_PRETTY_PRINT);
	}

	function DeserializeData() {
		if ($this->SubmittedData) {
			return json_decode($this->SubmittedData, true);
		} else {
			return null;
		}
	}

	function DataAsList() {
		$data = ArrayList::create();
		if ($this->SubmittedData) {
			foreach($this->DeserializeData() as $key => $value) {
				$data->push(ArrayData::create(['Key' => $key, 'Value' => $value]));
			}
		}
		return $data;
	}

	function ListOfSubmittedData() {
		$list = [];
		if ($data = $this->DeserializeData()) foreach($data as $key => $value) {
			$list[] = "<li><strong>" . $key.": </strong><code>".$value."</code></li>";
		}
		$html = HTMLText::create();
		$html->setValue("<ul>".join($list, "\n")."</ul>");
		return $html;
	}

	function Data() {
		return ArrayData::create($this->DeserializeData());
	}

}