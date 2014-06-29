<?php

namespace OroCRM\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * Class ZendeskRestTransport
 *
 * @ORM\Entity
 * @Config()
 * @Oro\Loggable()
 */
class ZendeskRestTransport extends Transport
{
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     * @Oro\Versioned()
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     * @Oro\Versioned()
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     * @Oro\Versioned()
     */
    protected $token;

    /**
     * @var ParameterBag
     */
    private $settings;

    /**
     * @param string $email
     * @return ZendeskRestTransport
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $token
     * @return ZendeskRestTransport
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $url
     * @return ZendeskRestTransport
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                array(
                    'email' => $this->getEmail(),
                    'url'   => $this->getUrl(),
                    'token' => $this->getToken()
                )
            );
        }

        return $this->settings;
    }
}