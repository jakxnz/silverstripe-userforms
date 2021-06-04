<?php

namespace SilverStripe\UserForms\Model\Submission;

use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField;

/**
 * A file uploaded on a {@link UserDefinedForm} and attached to a single
 * {@link SubmittedForm}.
 *
 * @property int UploadFieldID
 * @method File UploadedFile()
 * @package userforms
 */

class SubmittedFileField extends SubmittedFormField
{
    /**
     * @config
     * @var string
     */
    private static $table_name = 'SubmittedFileField';

    /**
     * @config
     * @var array
     */
    private static $has_one = [
        'UploadedFile' => File::class
    ];

    /**
     * @config
     * @var string
     */
    private static $submitted_form_field_type = SubmittedFormField::class;

    /**
     * Return the value of this field for inclusion into things such as
     * reports.
     *
     * @return string
     */
    public function getFormattedValue()
    {
        $name = $this->getFileName();
        $link = $this->getLink();
        $title = _t(__CLASS__.'.DOWNLOADFILE', 'Download File');

        if ($link) {
            return DBField::create_field('HTMLText', sprintf(
                '%s - <a href="%s" target="_blank">%s</a>',
                $name,
                $link,
                $title
            ));
        }

        return false;
    }

    /**
     * Return the value for this field in the CSV export.
     *
     * @return string
     */
    public function getExportValue()
    {
        return ($link = $this->getLink()) ? $link : '';
    }

    /**
     * Return the link for the file attached to this submitted form field.
     *
     * @return string
     */
    public function getLink()
    {
        if ($file = $this->UploadedFile()) {
            if (trim($file->getFilename(), '/') != trim(ASSETS_DIR, '/')) {
                return $this->UploadedFile()->AbsoluteLink();
            }
        }
    }

    /**
     * Return the name of the file, if present
     *
     * @return string
     */
    public function getFileName()
    {
        if ($this->UploadedFile()) {
            return $this->UploadedFile()->Name;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function populateFromData($data)
    {
        $field = $this->getEditableField();

        if (!empty($data[$this->Name])) {
            // Scrape data for this field (only when it is a single set of file data)
            if (!empty($_FILES[$this->Name]['name']) && is_string($_FILES[$this->Name]['name'])) {
                $foldername = $field->getFormField()->getFolderName();
                // create the file from post data
                $upload = Upload::create();
                try {
                    $upload->loadIntoFile($_FILES[$this->Name], null, $foldername);
                } catch (ValidationException $e) {
                    $validationResult = $e->getResult();
                    foreach ($validationResult->getMessages() as $message) {
                        $form->sessionMessage($message['message'], ValidationResult::TYPE_ERROR);
                    }
                    Controller::curr()->redirectBack();
                    return;
                }
                /** @var AssetContainer|File $file */
                $file = $upload->getFile();
                $file->ShowInSearch = 0;
                $file->write();

                // generate image thumbnail to show in asset-admin
                // you can run userforms without asset-admin, so need to ensure asset-admin is installed
                if (class_exists(AssetAdmin::class)) {
                    AssetAdmin::singleton()->generateThumbnails($file);
                }

                // write file to form field
                $this->UploadedFileID = $file->ID;

                // attach a file only if lower than 1MB
                if ($file->getAbsoluteSize() < 1024 * 1024 * 1) {
                    $this->Parent()->addAttachment($file);
                }
            }
        }

        return parent::populateFromData($data);
    }
}
