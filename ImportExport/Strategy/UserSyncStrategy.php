<?php

namespace OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\ZendeskBundle\Entity\UserRole as ZendeskUserRole;
use OroCRM\Bundle\ZendeskBundle\Entity\User as ZendeskUser;

use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\ContactProvider;
use OroCRM\Bundle\ZendeskBundle\ImportExport\Strategy\Provider\OroUserProvider;

class UserSyncStrategy extends AbstractSyncStrategy
{
    /**
     * @var ContactProvider
     */
    protected $contactProvider;

    /**
     * @var OroUserProvider
     */
    protected $oroUserProvider;

    /**
     * @param ContactProvider $contactProvider
     * @param OroUserProvider $oroUserProvider
     */
    public function __construct(
        ContactProvider $contactProvider,
        OroUserProvider $oroUserProvider
    ) {
        $this->contactProvider = $contactProvider;
        $this->oroUserProvider = $oroUserProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof ZendeskUser) {
            throw new InvalidArgumentException('Imported entity must be instance of Zendesk User');
        }

        if (!$this->validateOriginId($entity)) {
            return null;
        }

        $this->refreshDictionaryField($entity, 'role', 'userRole');

        $existingUser = $this->findExistingEntity($entity, 'originId');
        if ($existingUser) {
            $this->syncProperties($existingUser, $entity, array('relatedUser', 'relatedContact', 'id'));
            $entity = $existingUser;

            $this->getLogger()->debug($this->buildMessage("Update found record.", $entity));
            $this->getContext()->incrementUpdateCount();
        } else {
            $this->getLogger()->debug($this->buildMessage("Add new record.", $entity));
            $this->getContext()->incrementAddCount();
        }

        $this->syncRelatedEntities($entity);

        return $entity;
    }

    /**
     * @param ZendeskUser $entity
     */
    protected function syncRelatedEntities(ZendeskUser $entity)
    {
        if ($this->isRelativeWithUser($entity) && $entity->getRelatedContact()) {
            $relatedId = $entity->getRelatedContact()->getId();
            $this->getLogger()->info(
                $this->buildMessage(
                    "Unset related contact [id=$relatedId] due to incompatible role change.",
                    $entity
                )
            );
            $entity->setRelatedContact(null);
        }

        if ($this->isRelativeWithContact($entity) && $entity->getRelatedUser()) {
            $relatedId = $entity->getRelatedUser()->getId();
            $this->getLogger()->info(
                $this->buildMessage(
                    "Unset related user [id=$relatedId] due to incompatible role change.",
                    $entity
                )
            );
            $entity->setRelatedUser(null);
        }

        if ($entity->getRelatedUser() || $entity->getRelatedContact() || !$entity->getEmail()) {
            return;
        }

        if ($entity->isRoleIn(array(ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT))) {
            $relatedUser = $this->oroUserProvider->getUser($entity);
            if ($relatedUser) {
                $this->getLogger()->debug(
                    $this->buildMessage(
                        "Related user found [id={$relatedUser->getId()}]",
                        $entity
                    )
                );
                $entity->setRelatedUser($relatedUser);
            }
        } elseif ($entity->isRoleEqual(ZendeskUserRole::ROLE_END_USER)) {
            $relatedContact = $this->contactProvider->getContact($entity);
            if ($relatedContact) {
                $this->getLogger()->debug(
                    $this->buildMessage(
                        "Related contact found [id={$relatedContact->getId()}]",
                        $entity
                    )
                );
                $entity->setRelatedContact($relatedContact);
            }
        }
    }

    /**
     * @param ZendeskUser $entity
     * @return bool
     */
    protected function isRelativeWithUser(ZendeskUser $entity)
    {
        return $entity->isRoleIn(array(ZendeskUserRole::ROLE_ADMIN, ZendeskUserRole::ROLE_AGENT));
    }

    /**
     * @param ZendeskUser $entity
     * @return bool
     */
    protected function isRelativeWithContact(ZendeskUser $entity)
    {
        return $entity->isRoleEqual(ZendeskUserRole::ROLE_END_USER);
    }
}