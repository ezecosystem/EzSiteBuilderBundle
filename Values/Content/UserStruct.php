<?php

namespace EdgarEz\SiteBuilderBundle\Values\Content;

use eZ\Publish\API\Repository\Values\ValueObject;

class UserStruct extends ValueObject
{
    public $userType;
    public $userFirstName;
    public $userLastName;
    public $userEmail;
}
