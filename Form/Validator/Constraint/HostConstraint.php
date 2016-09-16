<?php

namespace Smile\EzSiteBuilderBundle\Form\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

class HostConstraint extends Constraint
{
    public $message = 'The string "%string%" is not a valid host.';

    public function validatedBy()
    {
        return 'smile_ez_site_builder.validator.host';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
