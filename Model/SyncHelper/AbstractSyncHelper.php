<?php

namespace OroCRM\Bundle\ZendeskBundle\Model\SyncHelper;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\HttpKernel\Log\NullLogger;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use OroCRM\Bundle\CaseBundle\Model\CaseEntityManager;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\ZendeskEntityProvider;
use OroCRM\Bundle\ZendeskBundle\Model\EntityProvider\OroEntityProvider;

abstract class AbstractSyncHelper implements SyncHelperInterface, LoggerAwareInterface
{
    /**
     * @var PropertyAccessor
     */
    static private $propertyAccessor;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ZendeskEntityProvider
     */
    protected $zendeskProvider;

    /**
     * @var ZendeskEntityProvider
     */
    protected $oroProvider;

    /**
     * @var CaseEntityManager
     */
    protected $caseEntityManager;

    /**
     * @param ZendeskEntityProvider $zendeskProvider
     * @param OroEntityProvider $oroProvider
     * @param CaseEntityManager $caseEntityManager
     */
    public function __construct(
        ZendeskEntityProvider $zendeskProvider,
        OroEntityProvider $oroProvider,
        CaseEntityManager $caseEntityManager
    ) {
        $this->zendeskProvider = $zendeskProvider;
        $this->oroProvider = $oroProvider;
        $this->caseEntityManager = $caseEntityManager;
    }

    /**
     * Sync properties of $target object with $source object
     *
     * @param mixed $target
     * @param mixed $source
     * @param array $excludeProperties
     * @throws InvalidArgumentException
     */
    protected function syncProperties($target, $source, array $excludeProperties = array())
    {
        if (!is_object($target)) {
            throw new InvalidArgumentException(
                'Expect argument $target has object type object but %s given.',
                gettype($target)
            );
        }

        if (!is_object($source)) {
            throw new InvalidArgumentException(
                'Expect argument $target has object type object but %s given.',
                gettype($source)
            );
        }

        $targetClass = ClassUtils::getRealClass($target);
        $sourceClass = ClassUtils::getRealClass($source);
        if ($targetClass !== $sourceClass) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expect argument $sourceClass is instance of %s but %s given.',
                    $targetClass,
                    $sourceClass
                )
            );
        }

        $reflectionClass = new \ReflectionClass($targetClass);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            if (in_array($propertyName, $excludeProperties)) {
                continue;
            }
            self::getPropertyAccessor()->setValue(
                $target,
                $propertyName,
                self::getPropertyAccessor()->getValue($source, $propertyName)
            );
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected static function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
    }

    /**
     * @param mixed $entity
     * @param string $fieldName
     * @param string $dictionaryEntityAlias
     * @param boolean $required
     */
    protected function refreshDictionaryField($entity, $fieldName, $dictionaryEntityAlias = null, $required = false)
    {
        $dictionaryEntityAlias = $dictionaryEntityAlias ? : $fieldName;
        $value = null;
        $entityGetter = 'get' . ucfirst($fieldName);
        $entitySetter = 'set' . ucfirst($fieldName);
        $providerGetter = 'get' . ucfirst($dictionaryEntityAlias);
        if ($entity->$entityGetter()) {
            $value = $this->zendeskProvider->$providerGetter($entity->$entityGetter());
            if (!$value) {
                $valueName = $entity->$entityGetter()->getName();
                $this->getLogger()->warning("Can't find Zendesk $fieldName [name=$valueName].");
            }
        } elseif ($required) {
            $this->getLogger()->warning("Zendesk $fieldName is empty.");
        }
        $entity->$entitySetter($value);
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}