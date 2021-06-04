<?php

namespace SilverStripe\UserForms\Model;

use Page;

use SilverStripe\UserForms\UserForm;
use SilverStripe\UserForms\Control\UserDefinedFormController;

/**
 * A page with an editable form, defined by the user (aka a User Defined Form)
 *
 * @mixin UserForm
 */
class UserDefinedForm extends Page
{
    use UserForm;

    /**
     * @var string
     */
    private static $icon_class = 'font-icon-p-list';

    /**
     * @var string
     */
    private static $description = 'Adds a customizable form.';

    /**
     * @var string
     */
    private static $table_name = 'UserDefinedForm';

    public function getControllerName()
    {
        return UserDefinedFormController::class;
    }
}
