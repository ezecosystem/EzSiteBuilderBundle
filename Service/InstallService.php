<?php

namespace Smile\EzSiteBuilderBundle\Service;

use Smile\EzToolsBundle\Service\Content;
use Smile\EzToolsBundle\Service\ContentType;
use Smile\EzToolsBundle\Service\ContentTypeGroup;
use Smile\EzToolsBundle\Service\Role;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\LimitationValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\Limitation\LocationLimitation;
use eZ\Publish\Core\Repository\Values\User\Policy;
use eZ\Publish\Core\Repository\Values\User\PolicyDraft;
use eZ\Publish\Core\Repository\Values\User\PolicyUpdateStruct;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class InstallService
 * @package Smile\EzSiteBuilderBundle\Service
 */
class InstallService
{
    /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup $ctg */
    protected $ctg;

    /** @var Kernel $kernel symfony kernel interface */
    private $kernel;

    /** @var ContentTypeService $contentTypeService eZ ContentType Service */
    private $contentTypeService;

    /** @var ContentTypeGroup $contentTypeGroup SmileEz content type group service */
    private $contentTypeGroup;

    /** @var RoleService eZ Role Service */
    private $roleService;

    /** @var LocationService $locationService eZ Location Service */
    private $locationService;

    /** @var UserService $userService User Service */
    private $userService;

    /** @var LanguageService $languageService */
    private $languageService;

    /** @var ContentType $contentType SmileEz ContentType Service */
    private $contentType;

    /** @var Content $content SmileEz Content Service */
    private $content;

    /** @var Role $role SmileEz Role Service */
    private $role;

    /**
     * InstallService constructor.
     *
     * @param Kernel $kernel symfony kernel interface
     * @param ContentTypeService $contentTypeService eZ ContentType Service
     * @param RoleService $roleService eZ Role Service
     * @param LocationService $locationService eZ Location Service
     * @param UserService $userService eZ User Service
     * @param ContentTypeGroup $contentTypeGroup SmileEz ContentTypeGroup service
     * @param ContentType $contentType SmileEz ContentType Service
     * @param Content $content SmileEz Content Service
     * @param Role $role SmileEz Role Service
     */
    public function __construct(
        Kernel $kernel,
        ContentTypeService $contentTypeService,
        RoleService $roleService,
        LocationService $locationService,
        UserService $userService,
        LanguageService $languageService,
        ContentTypeGroup $contentTypeGroup,
        ContentType $contentType,
        Content $content,
        Role $role
    ) {
        $this->kernel = $kernel;
        $this->contentTypeService = $contentTypeService;
        $this->roleService = $roleService;
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->languageService = $languageService;

        $this->contentTypeGroup = $contentTypeGroup;
        $this->contentType = $contentType;
        $this->content = $content;
        $this->role = $role;
    }

    /**
     * Create ContentType Group
     */
    public function createContentTypeGroup()
    {
        try {
            /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup ctg */
            $this->ctg = $this->contentTypeGroup->add('SiteBuilder');
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create Content Types
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup
     * @return array
     */
    public function createContentTypes(
        \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup,
        $languageCode
    ) {
        $contentTypes = array();
        $identifiers = array('customer', 'customersroot', 'model', 'modelsroot', 'user');

        try {
            foreach ($identifiers as $identifier) {
                $contentTypeDefinition = $this->kernel->locateResource(
                    '@SmileEzSiteBuilderBundle/Resources/datas/contenttype_' . $identifier . '.yml'
                );
                $contentTypeDefinition = Yaml::parse(file_get_contents($contentTypeDefinition));
                $contentTypeDefinition['contentTypeGroup'] = $contentTypeGroup;
                $contentTypeDefinition['contentTypeMainLanguage'] = $languageCode;
                $this->contentType->add($contentTypeDefinition);
            }

            return $contentTypes;
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (ParseException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Create Media ContentTypes
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup
     * @return array
     */
    public function createMediaContentTypes(
        \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup,
        $languageCode
    ) {
        $contentTypes = array();
        $identifiers = array('customer', 'customersroot', 'model', 'modelsroot');

        try {
            foreach ($identifiers as $identifier) {
                $contentTypeDefinition = $this->kernel->locateResource(
                    '@SmileEzSiteBuilderBundle/Resources/datas/mediacontenttype_' . $identifier . '.yml'
                );
                $contentTypeDefinition = Yaml::parse(file_get_contents($contentTypeDefinition));
                $contentTypeDefinition['contentTypeGroup'] = $contentTypeGroup;
                $contentTypeDefinition['contentTypeMainLanguage'] = $languageCode;
                $this->contentType->add($contentTypeDefinition);
            }

            return $contentTypes;
        } catch (ParseException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create contents
     *
     * @param $parentLocationID
     * @return array
     */
    public function createContents($parentLocationID, $languageCode)
    {
        $contents = array();
        $identifiers = array('customersroot', 'modelsroot');

        try {
            foreach ($identifiers as $identifier) {
                $contentDefinition = $this->kernel->locateResource(
                    '@SmileEzSiteBuilderBundle/Resources/datas/content_' . $identifier . '.yml'
                );
                $contentDefinition = Yaml::parse(file_get_contents($contentDefinition));
                $contentDefinition['parentLocationID'] = $parentLocationID;
                $contentDefinition['languageCode'] = $languageCode;
                $contentAdded = $this->content->add($contentDefinition);
                $contents[] = $contentAdded;
            }

            $modelsLocationID = false;
            $customersLocationID = false;
            foreach ($contents as $content) {
                /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType */
                $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
                switch ($contentType->identifier) {
                    case 'smile_ez_sb_modelsroot':
                        $modelsLocationID = $content->contentInfo->mainLocationId;
                        break;
                    case 'smile_ez_sb_customersroot':
                        $customersLocationID = $content->contentInfo->mainLocationId;
                        break;
                    default:
                        break;
                }
            }

            return array(
                'contents' => $contents,
                'modelsLocationID' => $modelsLocationID,
                'customersLocationID' => $customersLocationID
            );
        } catch (ParseException $e) {
            throw new \RuntimeException($e);
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create media contents
     *
     * @param $parentLocationID
     * @return array
     */
    public function createMediaContents($parentLocationID, $languageCode)
    {
        $contents = array();
        $identifiers = array('customersroot', 'modelsroot');

        try {
            foreach ($identifiers as $identifier) {
                $contentDefinition = $this->kernel->locateResource(
                    '@SmileEzSiteBuilderBundle/Resources/datas/mediacontent_' . $identifier . '.yml'
                );
                $contentDefinition = Yaml::parse(file_get_contents($contentDefinition));
                $contentDefinition['parentLocationID'] = $parentLocationID;
                $contentDefinition['languageCode'] = $languageCode;
                $contentAdded = $this->content->add($contentDefinition);
                $contents[] = $contentAdded;
            }

            $mediaModelsLocationID = false;
            $mediaCustomersLocationID = false;
            foreach ($contents as $content) {
                /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType */
                $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
                switch ($contentType->identifier) {
                    case 'smile_ez_sb_mediamodelsroot':
                        $mediaModelsLocationID = $content->contentInfo->mainLocationId;
                        break;
                    case 'smile_ez_sb_mediacustomersroot':
                        $mediaCustomersLocationID = $content->contentInfo->mainLocationId;
                        break;
                    default:
                        break;
                }
            }

            return array(
                'contents' => $contents,
                'modelsLocationID' => $mediaModelsLocationID,
                'customersLocationID' => $mediaCustomersLocationID
            );
        } catch (ParseException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create user groups
     *
     * @param $parentLocationID
     * @return array
     */
    public function createUserGroups($parentLocationID, $languageCode)
    {
        try {
            $userGroupDefinition = Yaml::parse(
                file_get_contents(
                    $this->kernel->locateResource('@SmileEzSiteBuilderBundle/Resources/datas/usergrouproot.yml')
                )
            );
            $userGroupDefinition['parentLocationID'] = $parentLocationID;
            $userGroupDefinition['languageCode'] = $languageCode;
            /** @var \eZ\Publish\API\Repository\Values\Content\Content $userGroup */
            $userGroup = $this->content->add($userGroupDefinition);

            $contents = array();
            $userGroupParenttLocationID = $userGroup->contentInfo->mainLocationId;
            $identifiers = array('creator', 'editor');

            foreach ($identifiers as $identifier) {
                $userGroupDefinition = $this->kernel->locateResource(
                    '@SmileEzSiteBuilderBundle/Resources/datas/usergroup_' . $identifier . '.yml'
                );
                $userGroupDefinition = Yaml::parse(file_get_contents($userGroupDefinition));
                $userGroupDefinition['parentLocationID'] = $userGroupParenttLocationID;
                $userGroupDefinition['languageCode'] = $languageCode;
                /** @var \eZ\Publish\Core\REST\Client\Values\Content\Content $contentAdded */
                $contentAdded = $this->content->add($userGroupDefinition);
                $contents[] = $contentAdded;
            }

            $userCreatorsLocationID = false;
            $userEditorsLocationID = false;
            foreach ($contents as $content) {
                /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType */
                $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
                switch ($contentType->identifier) {
                    case 'user_group':
                        if ($content->contentInfo->name == 'Creators') {
                            $userCreatorsLocationID = $content->contentInfo->mainLocationId;
                        } else {
                            $userEditorsLocationID = $content->contentInfo->mainLocationId;
                        }
                        break;
                    default:
                        break;
                }
            }

            return array(
                'userGroupParenttLocationID' => $userGroupParenttLocationID,
                'userCreatorsLocationID' => $userCreatorsLocationID,
                'userEditorsLocationID' => $userEditorsLocationID
            );
        } catch (ParseException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create Role
     *
     * @param $userGroupLocationID
     * @param array $locationIDs
     */
    public function createRole($userGroupLocationID, array $locationIDs)
    {
        try {
            /** @var \eZ\Publish\API\Repository\Values\User\Role $role */
            $role = $this->role->add('SiteBuilder');

            $userGroupLocation = $this->locationService->loadLocation($userGroupLocationID);
            $userGroup = $this->userService->loadUserGroup($userGroupLocation->contentId);

            $this->role->addPolicy($role->id, 'content', 'read');

            $roleDraft = $this->roleService->createRoleDraft($role);

            /** @var Policy[] $policies */
            $policies = $roleDraft->policies;
            foreach ($policies as $policy) {
                if ($policy->module == 'content' && $policy->function == 'read') {
                    $locationLimitation = new LocationLimitation(
                        array(
                            'limitationValues' => $locationIDs
                        )
                    );

                    $policyUpdateStruct = new PolicyUpdateStruct();
                    $policyUpdateStruct->addLimitation($locationLimitation);
                    $policyDraft = new PolicyDraft(
                        [
                            'innerPolicy' => new Policy(
                                [
                                    'id' => $policy->id,
                                    'module' => 'content',
                                    'function' => 'read',
                                    'roleId' => $roleDraft->id
                                ]
                            )
                        ]
                    );

                    $this->roleService->updatePolicyByRoleDraft(
                        $roleDraft,
                        $policyDraft,
                        $policyUpdateStruct
                    );
                    $this->roleService->publishRoleDraft($roleDraft);
                }
            }

            $this->roleService->assignRoleToUserGroup(
                $role,
                $userGroup
            );
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (LimitationValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create content structure
     *
     * @param int $parentLocationID content root location ID
     * @return array models and customers content root location IDs
     */
    public function createContentStructure($parentLocationID, $languageCode)
    {
        try {
            $this->createContentTypes($this->ctg, $languageCode);
            $contents = $this->createContents($parentLocationID, $languageCode);

            return array(
                'modelsLocationID' => $contents['modelsLocationID'],
                'customersLocationID' => $contents['customersLocationID']
            );
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create media structure
     *
     * @param int $parentLocationID media root location ID
     * @return array models and customers media root location IDs
     */
    public function createMediaContentStructure($parentLocationID, $languageCode)
    {
        try {
            $this->createMediaContentTypes($this->ctg, $languageCode);
            $contents = $this->createMediaContents($parentLocationID, $languageCode);

            return array(
                'mediaModelsLocationID' => $contents['modelsLocationID'],
                'mediaCustomersLocationID' => $contents['customersLocationID']
            );
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Create user structure
     *
     * @param int $userGroupParenttLocationID user root location ID
     * @return array global user group, creator/Editor user group location IDs
     */
    public function createUserStructure($userGroupParenttLocationID, $languageCode)
    {
        try {
            /** @var int[] $userGroups */
            $userGroups = $this->createUserGroups($userGroupParenttLocationID, $languageCode);

            return array(
                'userGroupParenttLocationID' => $userGroups['userGroupParenttLocationID'],
                'userCreatorsLocationID' => $userGroups['userCreatorsLocationID'],
                'userEditorsLocationID' => $userGroups['userEditorsLocationID']
            );
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * Return list of activated languages
     *
     * @return array List of activated languages
     */
    public function listLanguages()
    {
        $return = array();

        $languages = $this->languageService->loadLanguages();
        foreach ($languages as $language) {
            if ($language->enabled) {
                $return[$language->languageCode] = $language->name;
            }
        }

        return $return;
    }

    /**
     * Return default language code
     *
     * @return string default language code
     */
    public function getDefaultLanguageCode()
    {
        return $this->languageService->getDefaultLanguageCode();
    }
}
