<?php

namespace OroCRM\Bundle\ZendeskBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ZendeskBundle\Entity\TicketComment;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\Ticket;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketPriority;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketStatus;
use OroCRM\Bundle\ZendeskBundle\Entity\TicketType;

class LoadTicketData extends AbstractZendeskFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = array(
        array(
            'reference' => 'orocrm_zendesk:ticket_43',
            'originId' => 43,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/43.json',
            'subject' => 'Zendesk Ticket 43',
            'description' => 'Zendesk Ticket 43 Description',
            'externalId' => '456546544564564564',
            'type' => TicketType::TYPE_PROBLEM,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_NORMAL,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'orocrm_zendesk:case_2',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:first_test_channel'
        ),
        array(
            'reference' => 'orocrm_zendesk:ticket_42',
            'originId' => 42,
            'url' => 'https://foo.zendesk.com/api/v2/tickets/42.json',
            'subject' => 'Zendesk Ticket 42',
            'description' => 'Zendesk Ticket 42 Description',
            'externalId' => '7e24caa0-87f7-44d6-922b-0330ed9fd06c',
            'problem' => 'orocrm_zendesk:ticket_43',
            'collaborators' => array('zendesk_user:fred.taylor@example.com', 'zendesk_user:alex.taylor@example.com'),
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_PENDING,
            'priority' => TicketPriority::PRIORITY_URGENT,
            'recipient' => 'test@mail.com',
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'hasIncidents' => true,
            'createdAt' => '2014-06-10T15:54:22Z',
            'updatedAt' => '2014-06-10T17:45:31Z',
            'dueAt' => '2014-06-11T12:13:11Z',
            'relatedCase' => 'orocrm_zendesk:case_1',
            'comments' => array(
                array(
                    'reference' => 'zendesk_ticket_comment_1000',
                    'originId' => 1000,
                    'body' => 'Comment 1',
                    'htmlBody' => '<p>Comment 1</p>',
                    'public' => true,
                    'author' => 'zendesk_user:james.cook@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_comment_1',
                    'channel' => 'zendesk_channel:first_test_channel'
                ),
                array(
                    'reference' => 'zendesk_ticket_comment_1002',
                    'originId' => 1001,
                    'body' => 'Comment 2',
                    'htmlBody' => '<p>Comment 2</p>',
                    'public' => false,
                    'author' => 'zendesk_user:jim.smith@example.com',
                    'createdAt' => '2014-06-05T12:24:23Z',
                    'relatedComment' => 'case_comment_2',
                    'channel' => 'zendesk_channel:first_test_channel'
                ),
            ),
            'channel' => 'zendesk_channel:first_test_channel'
         ),
        array(
            'reference' => 'orocrm_zendesk:not_synced_ticket',
            'subject' => 'Not Synced Zendesk Ticket',
            'description' => 'Not Synced Zendesk Ticket Description',
            'type' => TicketType::TYPE_TASK,
            'status' => TicketStatus::STATUS_OPEN,
            'priority' => TicketPriority::PRIORITY_NORMAL,
            'requester' => 'zendesk_user:alex.taylor@example.com',
            'submitter' => 'zendesk_user:fred.taylor@example.com',
            'assignee' => 'zendesk_user:fred.taylor@example.com',
            'createdAt' => '2014-06-05T12:24:23Z',
            'updatedAt' => '2014-06-05T13:43:21Z',
            'relatedCase' => 'orocrm_zendesk:case_4',
            'originUpdatedAt' => '2014-06-09T17:45:22Z',
            'channel' => 'zendesk_channel:first_test_channel'
        ),
    );

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Ticket();

            if (isset($data['reference'])) {
                $this->addReference($data['reference'], $entity);
            }

            if (isset($data['collaborators'])) {
                $collaborators = new ArrayCollection();
                foreach ($data['collaborators'] as $user) {
                    $collaborators->add($this->getReference($user));
                }
                $data['collaborators'] = $collaborators;
            }

            $data['priority'] = $manager->find('OroCRMZendeskBundle:TicketPriority', $data['priority']);
            $data['status'] = $manager->find('OroCRMZendeskBundle:TicketStatus', $data['status']);
            $data['type'] = $manager->find('OroCRMZendeskBundle:TicketType', $data['type']);

            if (isset($data['channel'])) {
                $data['channel'] = $this->getReference($data['channel']);
            }
            if (isset($data['createdAt'])) {
                $data['createdAt'] = new \DateTime($data['createdAt']);
            }
            if (isset($data['updatedAt'])) {
                $data['updatedAt'] = new \DateTime($data['updatedAt']);
            }
            if (isset($data['dueAt'])) {
                $data['dueAt'] = new \DateTime($data['dueAt']);
            }
            if (isset($data['problem'])) {
                $data['problem'] = $this->getReference($data['problem']);
            }
            if (isset($data['requester'])) {
                $data['requester'] = $this->getReference($data['requester']);
            }
            if (isset($data['submitter'])) {
                $data['submitter'] = $this->getReference($data['submitter']);
            }
            if (isset($data['assignee'])) {
                $data['assignee'] = $this->getReference($data['assignee']);
            }
            if (isset($data['relatedCase'])) {
                $data['relatedCase'] = $this->getReference($data['relatedCase']);
            }
            if (isset($data['originUpdatedAt'])) {
                $data['originUpdatedAt'] = new \DateTime($data['originUpdatedAt']);
            }
            $this->setEntityPropertyValues($entity, $data, array('reference', 'comments'));

            $manager->persist($entity);

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $commentData) {
                    $comment = new TicketComment();
                    $entity->addComment($comment);

                    if (isset($commentData['reference'])) {
                        $this->addReference($commentData['reference'], $comment);
                    }
                    if (isset($commentData['author'])) {
                        $commentData['author'] = $this->getReference($commentData['author']);
                    }
                    if (isset($commentData['channel'])) {
                        $commentData['channel'] = $this->getReference($commentData['channel']);
                    }
                    if (isset($commentData['relatedComment'])) {
                        $commentData['relatedComment'] = $this->getReference($commentData['relatedComment']);
                    }
                    if (isset($commentData['createdAt'])) {
                        $commentData['createdAt'] = new \DateTime($commentData['createdAt']);
                    }

                    $this->setEntityPropertyValues($comment, $commentData, array('reference'));

                    $manager->persist($comment);
                }
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array(
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadCaseEntityData',
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadZendeskUserData',
            'OroCRM\\Bundle\\ZendeskBundle\\Tests\\Functional\\DataFixtures\\LoadChannelData'
        );
    }
}
