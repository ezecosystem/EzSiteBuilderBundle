<?php

namespace EdgarEz\SiteBuilderBundle\Data\Mapper;

use EdgarEz\SiteBuilderBundle\Data\Site\SitesData;
use eZ\Publish\API\Repository\Values\ValueObject;
use EzSystems\RepositoryForms\Data\Mapper\FormDataMapperInterface;

class SitesMapper implements FormDataMapperInterface
{
    public function mapToFormData(ValueObject $sites, array $params = [])
    {
        $data = new SitesData(['sites' => $sites]);

        return $data;
    }
}
