<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="CampaignUser", mappedBy="user", cascade={"remove"})
     */
    private $campaignUsers;

    /**
     * @ORM\OneToMany(targetEntity="Campaign", mappedBy="user", cascade={"remove"})
     */
    private $campaigns;


    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $defaultCampaignId;



    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @ORM\OneToOne(targetEntity="Invitation")
     * @ORM\JoinColumn(referencedColumnName="code")
     * @Assert\NotNull(message="Your invitation is wrong", groups={"Registration"})
     */
    protected $invitation;

    public function setInvitation(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function getInvitation()
    {
        return $this->invitation;
    }

    /**
     * Add campaign
     *
     * @param \AppBundle\Entity\Campaign $campaign
     *
     * @return User
     */
    public function addCampaign(\AppBundle\Entity\Campaign $campaign)
    {
        $this->campaigns[] = $campaign;

        return $this;
    }

    /**
     * Remove campaign
     *
     * @param \AppBundle\Entity\Campaign $campaign
     */
    public function removeCampaign(\AppBundle\Entity\Campaign $campaign)
    {
        $this->campaigns->removeElement($campaign);
    }

    /**
     * Get campaigns
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * Add owner
     *
     * @param \AppBundle\Entity\Campaign $owner
     *
     * @return User
     */
    public function addOwner(\AppBundle\Entity\Campaign $owner)
    {
        $this->owners[] = $owner;

        return $this;
    }

    /**
     * Remove owner
     *
     * @param \AppBundle\Entity\Campaign $owner
     */
    public function removeOwner(\AppBundle\Entity\Campaign $owner)
    {
        $this->owners->removeElement($owner);
    }

    /**
     * Get owners
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * Add campaignUser
     *
     * @param \AppBundle\Entity\CampaignUser $campaignUser
     *
     * @return User
     */
    public function addCampaignUser(\AppBundle\Entity\CampaignUser $campaignUser)
    {
        $this->campaignUsers[] = $campaignUser;

        return $this;
    }

    /**
     * Remove campaignUser
     *
     * @param \AppBundle\Entity\CampaignUser $campaignUser
     */
    public function removeCampaignUser(\AppBundle\Entity\CampaignUser $campaignUser)
    {
        $this->campaignUsers->removeElement($campaignUser);
    }

    /**
     * Get campaignUsers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignUsers()
    {
        return $this->campaignUsers;
    }

    /**
     * Set defaultCampaignId
     *
     * @param integer $defaultCampaignId
     *
     * @return User
     */
    public function setDefaultCampaignId($defaultCampaignId)
    {
        $this->defaultCampaignId = $defaultCampaignId;

        return $this;
    }

    /**
     * Get defaultCampaignId
     *
     * @return integer
     */
    public function getDefaultCampaignId()
    {
        return $this->defaultCampaignId;
    }
}
