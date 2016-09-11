<?php

namespace EdgarEz\SiteBuilderBundle\Controller;

use EdgarEz\SiteBuilderBundle\Data\Mapper\SiteMapper;
use EdgarEz\SiteBuilderBundle\Data\Mapper\SitesMapper;
use EdgarEz\SiteBuilderBundle\Data\Site\SitesData;
use EdgarEz\SiteBuilderBundle\Form\Type\CustomerType;
use EdgarEz\SiteBuilderBundle\Form\Type\InstallType;
use EdgarEz\SiteBuilderBundle\Form\Type\ModelType;
use EdgarEz\SiteBuilderBundle\Form\Type\SitesType;
use EdgarEz\SiteBuilderBundle\Form\Type\SiteType;
use EdgarEz\SiteBuilderBundle\Form\Type\UserType;
use EdgarEz\SiteBuilderBundle\Generator\CustomerGenerator;
use EdgarEz\SiteBuilderBundle\Generator\ProjectGenerator;
use EdgarEz\SiteBuilderBundle\Service\InstallService;
use EdgarEz\SiteBuilderBundle\Values\Content\Site;
use EdgarEz\SiteBuilderBundle\Values\Content\Sites;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\User\User;
use EzSystems\PlatformUIBundle\Controller\Controller;

class SbController extends Controller
{
    /** @var LocationService $locationService */
    protected $locationService;

    /** @var SearchService $searchService */
    protected $searchService;

    /** @var LanguageService $languageService */
    protected $languageService;

    /** @var InstallService $installService */
    protected $installService;

    protected $tabItems;

    public function __construct(
        LocationService $locationService,
        SearchService $searchService,
        LanguageService $languageService,
        InstallService $installService,
        $tabItems
    ) {
        $this->locationService = $locationService;
        $this->searchService = $searchService;
        $this->languageService = $languageService;
        $this->installService = $installService;
        $this->tabItems = $tabItems;
    }

    public function sbAction($tabItem)
    {
        $installed = $this->container->hasParameter('edgar_ez_site_builder.installed')
            ? $this->container->getParameter('edgar_ez_site_builder.installed')
            : false;
        $tabItems = $this->tabItems;

        if (!$installed) {
            $tabItems = array($tabItems[0], $tabItems[1]);
        } else {
            unset($tabItems[0]);
        }

        return $this->render('EdgarEzSiteBuilderBundle:sb:index.html.twig', [
            'installed' => $installed,
            'tab_items' => $tabItems,
            'tab_item_selected' => $tabItem,
            'params' => array(),
            'hasErrors' => false
        ]);
    }

    public function tabAction($tabItem, $paramsTwig = array(), $hasErrors = false)
    {
        $params = array();
        $tabItemMethod = 'tabItem' . ucfirst($tabItem);
        $params = $this->{$tabItemMethod}($paramsTwig);

        return $this->render('EdgarEzSiteBuilderBundle:sb:tab/' . $tabItem . '.html.twig', [
            'tab_items' => $this->tabItems,
            'tab_item' => $tabItem,
            'params' => $params
        ]);
    }

    protected function tabItemInstall($paramsTwig)
    {
        if (isset($paramsTwig['install'])) {
            $params['installForm'] = $paramsTwig['install'];
        } else {
            $params['installForm'] = $this->createForm(
                new InstallType($this->installService)
            )->createView();
        }

        return $params;
    }

    protected function tabItemDashboard($paramsTwig)
    {
        $params['user_id'] = $this->getUser()->getAPIUser()->getUserId();
        return $params;
    }

    protected function tabItemCustomergenerate($paramsTwig)
    {
        if (isset($paramsTwig['customergenerate'])) {
            $params['customerForm'] = $paramsTwig['customergenerate'];
        } else {
            $params['customerForm'] = $this->createForm(
                new CustomerType()
            )->createView();
        }

        return $params;
    }

    protected function tabItemModelgenerate($paramsTwig)
    {
        if (isset($paramsTwig['modelgenerate'])) {
            $params['modelForm'] = $paramsTwig['modelgenerate'];
        }
        $params['modelForm'] = $this->createForm(
            new ModelType()
        )->createView();

        return $params;
    }

    protected function tabItemModelactivate($paramsTwig)
    {
    }

    protected function tabItemSitegenerate($paramsTwig)
    {
        if (isset($paramsTwig['sitegenerate'])) {
            $params['sites'] = $paramsTwig['sitegenerate'];
            return $params;
        }

        $countActiveModels = $this->getCountActiveModels();
        if (!$countActiveModels) {
            $params['sites'] = false;
            return $params;
        }

        $customerName = $this->getCustomerName();

        $customerAlias = strtolower(
            ProjectGenerator::CUSTOMERS . '_' . $customerName . '_' . CustomerGenerator::SITES
        );

        $languages = $this->languageService->loadLanguages();
        $listSites = array();
        foreach ($languages as $language) {
            if (!$language->enabled)
                continue;

            $site = new Site([
                'languageCode' => $language->languageCode,
                'siteName' => '',
                'host' => '',
                'suffix' => '',
            ]);

            // $listSites[$language->name] = $site;

            $data = (new SiteMapper())->mapToFormData($site);
            $listSites[$language->name] = $this->createForm(
                new SiteType($language->languageCode),
                $data->getSite()
            )->createView();
        }

        $contentRootModelLocationID = $this->container->getParameter(
            'edgarez_sb.' . ProjectGenerator::MAIN . '.default.models_location_id'
        );
        $mediaRootModelLocationID = $this->container->getParameter(
            'edgarez_sb.' . ProjectGenerator::MAIN . '.default.media_models_location_id'
        );
        $contentRootCustomerLocationID = $this->container->getParameter(
            'edgarez_sb.customer.' . $customerAlias . '.default.customer_location_id'
        );
        $mediaRootCustomerLocationID = $this->container->getParameter(
            'edgarez_sb.customer.' . $customerAlias . '.default.media_customer_location_id'
        );

        $sites = new Sites([
            'listSites' => $listSites,
            'model' => '',
            'customerName' => $customerName,
            'customerContentLocationID' => $contentRootCustomerLocationID,
            'customerMediaLocationID' => $mediaRootCustomerLocationID,
        ]);
        /** @var SitesData $data */
        $data = (new SitesMapper())->mapToFormData($sites);
        // $data->setSites($sites);

        $params['sites'] = $this->createForm(
            new SitesType(
                $this->searchService,
                $contentRootModelLocationID,
                $mediaRootModelLocationID,
                $contentRootCustomerLocationID,
                $mediaRootCustomerLocationID, $customerName
            ),
            $data
        )->createView();

        return $params;
    }

    protected function tabItemSiteactivate($paramsTwig)
    {
        return array();
    }

    protected function tabItemUsergenerate($paramsTwig)
    {
        if (isset($paramsTwig['usergenerate'])) {
            $params['userForm'] = $paramsTwig['usergenerate'];
            return $params;
        }

        $params['userForm'] = $this->createForm(
            new UserType()
        )->createView();

        return $params;
    }

    protected function getCustomerName()
    {
        /** @var User $user */
        $user = $this->getUser();
        $userLocation = $this->locationService->loadLocation($user->getAPIUser()->contentInfo->mainLocationId);

        $parent = $this->locationService->loadLocation($userLocation->parentLocationId);
        return $parent->contentInfo->name;
    }

    protected function getCountActiveModels()
    {
        $query = new Query();
        $locationCriterion = new Query\Criterion\ParentLocationId(
            $this->container->getParameter('edgarez_sb.project.default.models_location_id')
        );
        $contentTypeIdentifier = new Query\Criterion\ContentTypeIdentifier('edgar_ez_sb_model');
        $activated = new Query\Criterion\Field('activated', Query\Criterion\Operator::EQ, true);

        $query->filter = new Query\Criterion\LogicalAnd(
            array($locationCriterion, $contentTypeIdentifier, $activated)
        );

        /** @var SearchResult $result */
        $result = $this->searchService->findContent($query);
        return $result->totalCount;
    }
}
