<?php
/**
 * Contents of an UserDefinedForm save
 *
 * @package userforms
 */

class IncompleteSubmittedForm extends SubmittedForm
{
    private static $db = [
        'Secret' => 'Varchar'
    ];

    /**
     * Generate a fairly unique hash
     * @return string
     */
    public static function generateHash()
    {
        return md5(Member::currentUserID() . microtime() . rand());
    }

    /**
     * Check if the current user can view the stored form
     * @return boolean
     */
    public function canView($member = null)
    {
        if (Permission::check('CMS_ACCESS', 'any', $member)) {
            return true;
        }

        if ($this->SubmittedByID) {
            if ($member) {
                return $member->ID == $this->SubmittedByID;
            }

            return false;
        }

        return true;
    }
}
