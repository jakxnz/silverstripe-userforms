<?php

namespace SilverStripe\UserForms\Model\Submission;

use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\UserDefinedForm;

/**
 * Data received from a UserDefinedForm submission
 *
 * @property string Name
 * @property string Value
 * @property string Title
 * @property int ParentID
 * @package userforms
 */
class SubmittedFormField extends DataObject
{
    private static $db = [
        'Name' => 'Varchar',
        'Value' => 'Text',
        'Title' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Parent' => SubmittedForm::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'FormattedValue' => 'Value'
    ];

    private static $table_name = 'SubmittedFormField';

    /**
     * An in-memory fallback to assist submission processing
     * when submission saving is disabled
     *
     * @var SubmittedForm
     */
    protected $parent;

    /**
     * A surrogate parent mutator, used to avoid reliance
     * on in-database records
     *
     * @param SubmittedForm $parent
     * @return SubmittedFormField
     */
    public function setParent($parent)
    {
        if (!$parent->Parent()->DisableSaveSubmissions && $parent->isInDB()) {
            $this->ParentID = $parent->ID;
        } else {
            $this->parent = $parent;
            $this->parent->Values()->add($this);
        }

        return $this;
    }

    /**
     * Supplement the parent component to also retrieve
     * parent record from memory
     *
     * @return SubmittedForm
     */
    public function Parent()
    {
        $parent = $this->ParentID
            ? $this->getComponent('Parent')
            : $this->parent;

        return $parent;
    }

    /**
     * @param Member $member
     * @param array $context
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return $this->Parent()->canCreate();
    }

    /**
     * @param Member $member
     *
     * @return boolean
     */
    public function canView($member = null)
    {
        return $this->Parent()->canView();
    }

    /**
     * @param Member $member
     *
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return $this->Parent()->canEdit();
    }

    /**
     * @param Member $member
     *
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return $this->Parent()->canDelete();
    }

    /**
     * Generate a formatted value for the reports and email notifications.
     * Converts new lines (which are stored in the database text field) as
     * <brs> so they will output as newlines in the reports.
     *
     * @return DBField
     */
    public function getFormattedValue()
    {
        return $this->dbObject('Value');
    }

    /**
     * Return the value of this submitted form field suitable for inclusion
     * into the CSV
     *
     * @return DBField
     */
    public function getExportValue()
    {
        return $this->Value;
    }

    /**
     * Find equivalent editable field for this submission.
     *
     * Note the field may have been modified or deleted from the original form
     * so this may not always return the data you expect. If you need to save
     * a particular state of editable form field at time of submission, copy
     * that value to the submission.
     *
     * @return EditableFormField
     */
    public function getEditableField()
    {
        return $this->Parent()->Parent()->Fields()->filter([
            'Name' => $this->Name
        ])->First();
    }

    /**
     * Hydrate the submitted form field
     *
     * @param array $data
     *
     * @return SubmittedFormField
     */
    public function populateFromData($data)
    {
        $field = $this->getEditableField();

        if (!$field) {
            throw new Exception(
                sprintf('Can\'t find editable field with name "%s" to submit data for', $this->Name)
            );
        }

        // Save the value from the data
        if ($field->hasMethod('getValueFromData')) {
            $this->Value = $field->getValueFromData($data);
        } else if (isset($data[$field->Name])) {
            $this->Value = $data[$field->Name];
        }

        return $this;
    }

}
