# Custom Forms with SilverStripe

Define you custom form fields in your created `CustomFormPage` with this straight forward syntax:

```
    {{ FieldName | My Field: | Dropdown | { "Option1": "Foo", "Option2": "Bar" } }}
    {{ FieldName * | My Required Field: | Textarea }}
    {{ Captcha |  | Recaptcha }}
```

You can use as many form fields as you like.

## Requirements

  * SilverStripe 4+

## Arguments

The following arguments are listed separated by `|` in each `{{ }}` form field block (sequence of arguments is *not* arbitrary):

  1. Name: String as CamelCase describing the name of your field; e.g.: `Email`, `FirstName` …
    * the field is required, if ends with `*`; e.g.: `Email *`
  2. Title: String as title, can contain every character except the `|` seperator; e.g. `Your eMail:`
  3. Class of Field: Can be every existing field class, without the `Field` appendix; e.g.: `Text`, `Textarea`, `Email`, `Dropdown` …
  4. (assoc.) Array as option for the field
    * optional
    * must be JSON
    * required for DropDownField for instance

## Usage in Templates

You can access via `$Form` if you are on a `CustomFormPage` or via `$CustomForm`:

```erb
  <% with $MyCustomFormPage %>
    <h1>$Title</h1>
    $CustomForm
  <% end_with %>
```

## Comments and HTML tags

You can use comments `#` and using html tags. HTML tags will be converted to SilverStripe LiteralFields:

```
    # My advanced Poll Form Desctiption
    {{ Email * | Your eMail (required): | Email }}
    <hr />
    <h2>Additional Information</h2>
    # Description of something
    {{ Age | Your age: | Dropdown | { "Under20": "younger or just 20", "Over20": "older than 20" } }}
```

## Exclude Fields in form submission

You can explicitly prevent fields to be written in the submission. Just define them in your config:

```yml
CustomFormPageSubmission:
  excludeParameters:
    - Foo
    - Bar
```

## Template variables

You can check `$FormWasSuccessfullySended` and `$FormWasSubmitted` in your template.

## Problems

If an `Uncaught Error: Call to a member function setForm() on null (SilverStripe\Forms\FieldList->setForm(SilverStripe\Forms\Form))` occurs, please ensure that you have at least one form field defined ;)

## License

(C) 2017 by Philipp Staender, MIT Licence
