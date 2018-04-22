<?php

use SilverStripe\Control\Email\Email;
use SilverStripe\View\ArrayData;

class CustomFormPage_Controller extends PageController
{

    private static $allowed_actions = [
        'CustomForm' => true,
    ];

    public function CustomForm()
    {
        return $this->dataRecord->CustomForm($this);
    }

    public function doSubmitForm($data, SilverStripe\Forms\Form $form)
    {
        $this->addFormSubmission($data, $form);
        if ($this->dataRecord->PageOnSuccessID > 0) {
            return $this->redirect($this->dataRecord->PageOnSuccess()->Link());
        } else {
            return [
                "FormWasSuccessfullySended" => $this->FormWasSuccessfullySended,
                "FormWasSubmitted" => $this->FormWasSubmitted,
            ];
        }
    }

    private function addFormSubmission($data, SilverStripe\Forms\Form $form)
    {
        $submission = CustomFormPageSubmission::create();
        $submission->PageID = $this->dataRecord->ID;
        $submission->addAllowedFormData($data);
        $submission->write();

        $this->FormWasSubmitted = true;
        $this->FormWasSuccessfullySended = false;

        if ($this->SendToEmailInternal) {
            $email = new Email();
            $from = ($this->FromEmail) ? $this->FromEmail : $this->SendToEmailInternal;
            $to = $this->SendToEmailInternal;
            $subject = ($this->EmailSubjectInternal) ? $this->EmailSubjectInternal : 'New Form Submission';
            $email
                ->setFrom($from)
                ->setTo($to)
                ->setSubject($subject)
                ->setHTMLTemplate('Email\\InternalSubmissionEmail')
                ->setData(new ArrayData([
                    'Submission' => $submission,
                ]));

            $submission->SendToEmail = $to;
            $submission->IsSended = $email->send();
            $submission->write();
        }

        $this->FormWasSuccessfullySended = $submission->IsSended;

        if ($submission->IsSended && $this->dataRecord->MessageIfSubmissionSuccessful) {
            $form->sessionMessage($this->dataRecord->MessageIfSubmissionSuccessful, 'good');
        } else if ($this->SendToEmailInternal && !$submission->IsSended) {
            $form->sessionMessage($this->MessageIfSubmissionFailed ? $this->MessageIfSubmissionFailed : _t('CustomFormPage.MessageOnSendingFailed', 'There was a problem submitting your data. Please get in touch with us via email.'), 'bad');
        }
        return $submission;
    }

}
