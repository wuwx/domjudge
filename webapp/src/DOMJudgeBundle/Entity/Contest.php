<?php declare(strict_types=1);

namespace DOMJudgeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DOMJudgeBundle\Utils\Utils;
use DOMJudgeBundle\Validator\Constraints\Identifier;
use DOMJudgeBundle\Validator\Constraints\TimeString;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contests that will be run with this install
 * @ORM\Entity()
 * @ORM\Table(name="contest", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @Serializer\VirtualProperty(
 *     "formalName",
 *     exp="object.getName()",
 *     options={@Serializer\Type("string")}
 * )
 * @Serializer\VirtualProperty(
 *     "penaltyTime",
 *     options={@Serializer\Type("int")}
 * )
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("shortname")
 * @UniqueEntity("externalid")
 */
class Contest
{
    const STARTTIME_UPDATE_MIN_SECONDS_BEFORE = 30;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="cid", options={"comment"="Unique ID"}, nullable=false)
     * @Serializer\SerializedName("id")
     * @Serializer\Type("string")
     */
    private $cid;

    /**
     * @var string
     * @ORM\Column(type="string", name="externalid", length=255, options={"comment"="Contest ID in an external system",
     *                            "collation"="utf8mb4_bin"}, nullable=true)
     * @Serializer\Groups({"Nonstrict"})
     * @Serializer\SerializedName("external_id")
     * TODO: ORM\Unique on first 190 characters
     */
    private $externalid;

    /**
     * @var string
     * @ORM\Column(type="string", name="name", length=255, options={"comment"="Descriptive name"}, nullable=false)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="shortname", length=255, options={"comment"="Short name for this contest"},
     *                            nullable=false)
     * @Serializer\Groups({"Nonstrict"})
     * @Identifier()
     * @Assert\NotBlank()
     */
    private $shortname;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="activatetime", options={"comment"="Time contest becomes
     *                             visible in team/public views", "unsigned"=true}, nullable=false)
     * @Serializer\Exclude()
     */
    private $activatetime;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="starttime", options={"comment"="Time contest starts,
     *                             submissions accepted", "unsigned"=true}, nullable=false)
     * @Serializer\Exclude()
     */
    private $starttime;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="starttime_enabled", options={"comment"="If disabled, starttime is not used,
     *                             e.g. to delay contest start"}, nullable=false)
     * @Serializer\Exclude()
     */
    private $starttimeEnabled = true;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="freezetime", options={"comment"="Time scoreboard is
     *                             frozen", "unsigned"=true}, nullable=true)
     * @Serializer\Exclude()
     */
    private $freezetime;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="endtime", options={"comment"="Time after which no more
     *                             submissions are accepted", "unsigned"=true}, nullable=false)
     * @Serializer\Exclude()
     */
    private $endtime;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="unfreezetime", options={"comment"="Unfreeze a frozen
     *                             scoreboard at this time", "unsigned"=true}, nullable=true)
     * @Serializer\Exclude()
     */
    private $unfreezetime;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="finalizetime", options={"comment"="Time when contest
     *                             was finalized, null if not yet", "unsigned"=true}, nullable=true)
     * @Serializer\Exclude()
     */
    private $finalizetime;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=64, name="finalizecomment", options={"comment"="Comments by the finalizer"},
     *                            nullable=true)
     * @Serializer\Exclude()
     */
    private $finalizecomment;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", length=3, name="b", options={"comment"="Number of extra bronze medals"},
     *                              nullable=true)
     * @Serializer\Exclude()
     */
    private $b = 0;

    /**
     * @var double
     * @ORM\Column(type="decimal", precision=32, scale=9, name="deactivatetime", options={"comment"="Time contest
     *                             becomes invisible in team/public views", "unsigned"=true}, nullable=true)
     * @Serializer\Exclude()
     */
    private $deactivatetime;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="activatetime_string", options={"comment"="Authoritative absolute or
     *                            relative string representation of activatetime"}, nullable=false)
     * @Serializer\Exclude()
     * @TimeString(relativeIsPositive=false)
     */
    private $activatetimeString;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="starttime_string", options={"comment"="Authoritative absolute
     *                            (only!) string representation of starttime"}, nullable=false)
     * @Serializer\Exclude()
     * @TimeString(allowRelative=false)
     */
    private $starttimeString;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="freezetime_string", options={"comment"="Authoritative absolute or
     *                            relative string representation of freezetime"}, nullable=true)
     * @Serializer\Exclude()
     * @TimeString()
     */
    private $freezetimeString;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="endtime_string", options={"comment"="Authoritative absolute or
     *                            relative string representation of endtime"}, nullable=false)
     * @Serializer\Exclude()
     * @TimeString()
     */
    private $endtimeString;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="unfreezetime_string", options={"comment"="Authoritative absolute or
     *                            relative string representation of unfreezetime"}, nullable=true)
     * @Serializer\Exclude()
     * @TimeString()
     */
    private $unfreezetimeString;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, name="deactivatetime_string", options={"comment"="Authoritative absolute
     *                            or relative string representation of deactivatetime"}, nullable=true)
     * @Serializer\Exclude()
     * @TimeString()
     */
    private $deactivatetimeString;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="enabled", options={"comment"="Whether this contest can be active"},
     *                             nullable=false)
     * @Serializer\Exclude()
     */
    private $enabled = true;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="process_balloons", options={"comment"="Will balloons be processed for this
     *                             contest?"}, nullable=false)
     * @Serializer\Exclude()
     */
    private $processBalloons = true;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="public", options={"comment"="Is this contest visible for the public and
     *                             non-associated teams?"}, nullable=false)
     * @Serializer\Exclude()
     */
    private $public = true;

    /**
     * @ORM\ManyToMany(targetEntity="Team", inversedBy="contests")
     * @ORM\JoinTable(name="contestteam",
     *                joinColumns={@ORM\JoinColumn(name="cid", referencedColumnName="cid")},
     *                inverseJoinColumns={@ORM\JoinColumn(name="teamid", referencedColumnName="teamid")}
     *               )
     * @Serializer\Exclude()
     */
    private $teams;

    /**
     * @ORM\OneToMany(targetEntity="Clarification", mappedBy="contest")
     * @Serializer\Exclude()
     */
    private $clarifications;

    /**
     * @ORM\OneToMany(targetEntity="Submission", mappedBy="contest")
     * @Serializer\Exclude()
     */
    private $submissions;

    /**
     * @ORM\OneToMany(targetEntity="ContestProblem", mappedBy="contest", orphanRemoval=true, cascade={"persist"})
     * @ORM\OrderBy({"shortname" = "ASC"})
     * @Serializer\Exclude()
     * @Assert\Valid()
     */
    private $problems;

    /**
     * @ORM\OneToMany(targetEntity="InternalError", mappedBy="contest")
     * @Serializer\Exclude()
     */
    private $internal_errors;

    /**
     * @ORM\OneToMany(targetEntity="ScoreCache", mappedBy="contest")
     * @Serializer\Exclude()
     */
    private $scorecache;

    /**
     * @ORM\OneToMany(targetEntity="RankCache", mappedBy="contest")
     * @Serializer\Exclude()
     */
    private $rankcache;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="DOMJudgeBundle\Entity\RemovedInterval", mappedBy="contest")
     * @Serializer\Exclude()
     * @Assert\Valid()
     */
    private $removedIntervals;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->problems         = new ArrayCollection();
        $this->teams            = new ArrayCollection();
        $this->removedIntervals = new ArrayCollection();
    }

    /**
     * Get cid
     *
     * @return integer
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Set externalid
     *
     * @param string $externalid
     *
     * @return Contest
     */
    public function setExternalid($externalid)
    {
        $this->externalid = $externalid;

        return $this;
    }

    /**
     * Get externalid
     *
     * @return string
     */
    public function getExternalid()
    {
        return $this->externalid;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Contest
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set shortname
     *
     * @param string $shortname
     *
     * @return Contest
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Get shortname
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Get activatetime
     *
     * @return double
     */
    public function getActivatetime()
    {
        return $this->activatetime;
    }

    /**
     * Set starttime
     *
     * @param double $starttime
     *
     * @return Contest
     */
    public function setStarttime($starttime)
    {
        $this->starttime = $starttime;

        return $this;
    }

    /**
     * Get starttime, or NULL if disabled
     *
     * @param bool $nullWhenDisabled If true, return null if the start time is disabled
     * @return double
     */
    public function getStarttime(bool $nullWhenDisabled = true)
    {
        if ($nullWhenDisabled && !$this->getStarttimeEnabled()) {
            return null;
        }

        return $this->starttime;
    }

    /**
     * Get the start time for this contest
     *
     * @return \DateTime|null
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("start_time")
     * @Serializer\Type("DateTime")
     */
    public function getStartTimeObject()
    {
        return $this->getStarttime() ? new \DateTime(Utils::absTime($this->getStarttime())) : null;
    }

    /**
     * Set starttime_enabled
     *
     * @param boolean $starttimeEnabled
     *
     * @return Contest
     */
    public function setStarttimeEnabled($starttimeEnabled)
    {
        $this->starttimeEnabled = $starttimeEnabled;

        return $this;
    }

    /**
     * Get starttime_enabled
     *
     * @return boolean
     */
    public function getStarttimeEnabled()
    {
        return $this->starttimeEnabled;
    }

    /**
     * Get freezetime
     *
     * @return double
     */
    public function getFreezetime()
    {
        return $this->freezetime;
    }

    /**
     * Get endtime
     *
     * @return double
     */
    public function getEndtime()
    {
        return $this->endtime;
    }

    /**
     * Get the end time for this contest
     *
     * @return \DateTime|null
     * @throws \Exception
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("end_time")
     * @Serializer\Type("DateTime")
     * @Serializer\Groups({"Nonstrict"})
     */
    public function getEndTimeObject()
    {
        return $this->getEndtime() ? new \DateTime(Utils::absTime($this->getEndtime())) : null;
    }

    /**
     * Get unfreezetime
     *
     * @return double
     */
    public function getUnfreezetime()
    {
        return $this->unfreezetime;
    }

    /**
     * Get finalizetime
     *
     * @return double
     */
    public function getFinalizetime()
    {
        return $this->finalizetime;
    }

    /**
     * Set finalizetime
     *
     * @param double $finalizetimeString
     *
     * @return Contest
     */
    public function setFinalizetime($finalizetimeString)
    {
        $this->finalizetime = $finalizetimeString;
        return $this;
    }

    /**
     * Get finalizecomment
     *
     * @return string|null
     */
    public function getFinalizecomment()
    {
        return $this->finalizecomment;
    }

    /**
     * Set finalizecomment
     *
     * @param string|null $finalizecomment
     */
    public function setFinalizecomment($finalizecomment)
    {
        $this->finalizecomment = $finalizecomment;
    }

    /**
     * Get b
     *
     * @return int|null
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * Set b
     * @param int|null $b
     */
    public function setB($b)
    {
        $this->b = $b;
    }

    /**
     * Get deactivatetime
     *
     * @return double
     */
    public function getDeactivatetime()
    {
        return $this->deactivatetime;
    }

    /**
     * Set activatetimeString
     *
     * @param string $activatetimeString
     *
     * @return Contest
     */
    public function setActivatetimeString($activatetimeString)
    {
        $this->activatetimeString = $activatetimeString;
        $this->activatetime       = $this->getAbsoluteTime($activatetimeString);

        return $this;
    }

    /**
     * Get activatetimeString
     *
     * @return string
     */
    public function getActivatetimeString()
    {
        return $this->activatetimeString;
    }

    /**
     * Set starttimeString
     *
     * @param string $starttimeString
     *
     * @return Contest
     */
    public function setStarttimeString($starttimeString)
    {
        $this->starttimeString = $starttimeString;

        $this->setActivatetimeString($this->getActivatetimeString());
        $this->setFreezetimeString($this->getFreezetimeString());
        $this->setEndtimeString($this->getEndtimeString());
        $this->setUnfreezetimeString($this->getUnfreezetimeString());
        $this->setDeactivatetimeString($this->getDeactivatetimeString());

        return $this;
    }

    /**
     * Get starttimeString
     *
     * @return string
     */
    public function getStarttimeString()
    {
        return $this->starttimeString;
    }

    /**
     * Set freezetimeString
     *
     * @param string $freezetimeString
     *
     * @return Contest
     */
    public function setFreezetimeString($freezetimeString)
    {
        $this->freezetimeString = $freezetimeString;
        $this->freezetime       = $this->getAbsoluteTime($freezetimeString);

        return $this;
    }

    /**
     * Get freezetimeString
     *
     * @return string
     */
    public function getFreezetimeString()
    {
        return $this->freezetimeString;
    }

    /**
     * Set endtimeString
     *
     * @param string $endtimeString
     *
     * @return Contest
     */
    public function setEndtimeString($endtimeString)
    {
        $this->endtimeString = $endtimeString;
        $this->endtime       = $this->getAbsoluteTime($endtimeString);

        return $this;
    }

    /**
     * Get endtimeString
     *
     * @return string
     */
    public function getEndtimeString()
    {
        return $this->endtimeString;
    }

    /**
     * Set unfreezetimeString
     *
     * @param string $unfreezetimeString
     *
     * @return Contest
     */
    public function setUnfreezetimeString($unfreezetimeString)
    {
        $this->unfreezetimeString = $unfreezetimeString;
        $this->unfreezetime       = $this->getAbsoluteTime($unfreezetimeString);

        return $this;
    }

    /**
     * Get unfreezetimeString
     *
     * @return string
     */
    public function getUnfreezetimeString()
    {
        return $this->unfreezetimeString;
    }

    /**
     * Set deactivatetimeString
     *
     * @param string $deactivatetimeString
     *
     * @return Contest
     */
    public function setDeactivatetimeString($deactivatetimeString)
    {
        $this->deactivatetimeString = $deactivatetimeString;
        $this->deactivatetime       = $this->getAbsoluteTime($deactivatetimeString);

        return $this;
    }

    /**
     * Get deactivatetimeString
     *
     * @return string
     */
    public function getDeactivatetimeString()
    {
        return $this->deactivatetimeString;
    }

    /**
     * Set activatetime
     *
     * @param string $activatetime
     *
     * @return Contest
     */
    public function setActivatetime($activatetime)
    {
        $this->activatetime = $activatetime;

        return $this;
    }

    /**
     * Set freezetime
     *
     * @param string $freezetime
     *
     * @return Contest
     */
    public function setFreezetime($freezetime)
    {
        $this->freezetime = $freezetime;

        return $this;
    }

    /**
     * Set endtime
     *
     * @param string $endtime
     *
     * @return Contest
     */
    public function setEndtime($endtime)
    {
        $this->endtime = $endtime;

        return $this;
    }

    /**
     * Set unfreezetime
     *
     * @param string $unfreezetime
     *
     * @return Contest
     */
    public function setUnfreezetime($unfreezetime)
    {
        $this->unfreezetime = $unfreezetime;

        return $this;
    }

    /**
     * Set deactivatetime
     *
     * @param string $deactivatetime
     *
     * @return Contest
     */
    public function setDeactivatetime($deactivatetime)
    {
        $this->deactivatetime = $deactivatetime;

        return $this;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Contest
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set processBalloons
     *
     * @param boolean $processBalloons
     *
     * @return Contest
     */
    public function setProcessBalloons($processBalloons)
    {
        $this->processBalloons = $processBalloons;

        return $this;
    }

    /**
     * Get processBalloons
     *
     * @return boolean
     */
    public function getProcessBalloons()
    {
        return $this->processBalloons;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Contest
     */
    public function setPublic($public)
    {
        $this->public = $public;
        if ($this->public) {
            $this->teams->clear();
        }

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Add team
     *
     * @param \DOMJudgeBundle\Entity\Team $team
     *
     * @return Contest
     */
    public function addTeam(\DOMJudgeBundle\Entity\Team $team)
    {
        $this->teams[] = $team;

        return $this;
    }

    /**
     * Remove team
     *
     * @param \DOMJudgeBundle\Entity\Team $team
     */
    public function removeTeam(\DOMJudgeBundle\Entity\Team $team)
    {
        $this->teams->removeElement($team);
    }

    /**
     * Get teams
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * Add problem
     *
     * @param ContestProblem $problem
     *
     * @return Contest
     */
    public function addProblem(ContestProblem $problem)
    {
        $this->problems[] = $problem;

        return $this;
    }

    /**
     * Remove problem
     *
     * @param ContestProblem $problem
     */
    public function removeProblem(ContestProblem $problem)
    {
        $this->problems->removeElement($problem);
    }

    /**
     * Get problems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProblems()
    {
        return $this->problems;
    }

    /**
     * Add clarification
     *
     * @param \DOMJudgeBundle\Entity\Clarification $clarification
     *
     * @return Contest
     */
    public function addClarification(\DOMJudgeBundle\Entity\Clarification $clarification)
    {
        $this->clarifications[] = $clarification;

        return $this;
    }

    /**
     * Remove clarification
     *
     * @param \DOMJudgeBundle\Entity\Clarification $clarification
     */
    public function removeClarification(\DOMJudgeBundle\Entity\Clarification $clarification)
    {
        $this->clarifications->removeElement($clarification);
    }

    /**
     * Get clarifications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClarifications()
    {
        return $this->clarifications;
    }

    /**
     * Add submission
     *
     * @param \DOMJudgeBundle\Entity\Submission $submission
     *
     * @return Contest
     */
    public function addSubmission(\DOMJudgeBundle\Entity\Submission $submission)
    {
        $this->submissions[] = $submission;

        return $this;
    }

    /**
     * Remove submission
     *
     * @param \DOMJudgeBundle\Entity\Submission $submission
     */
    public function removeSubmission(\DOMJudgeBundle\Entity\Submission $submission)
    {
        $this->submissions->removeElement($submission);
    }

    /**
     * Get submissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubmissions()
    {
        return $this->submissions;
    }

    /**
     * Add internalError
     *
     * @param \DOMJudgeBundle\Entity\InternalError $internalError
     *
     * @return Contest
     */
    public function addInternalError(\DOMJudgeBundle\Entity\InternalError $internalError)
    {
        $this->internal_errors[] = $internalError;

        return $this;
    }

    /**
     * Remove internalError
     *
     * @param \DOMJudgeBundle\Entity\InternalError $internalError
     */
    public function removeInternalError(\DOMJudgeBundle\Entity\InternalError $internalError)
    {
        $this->internal_errors->removeElement($internalError);
    }

    /**
     * Get internalErrors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInternalErrors()
    {
        return $this->internal_errors;
    }

    /**
     * Add scorecache
     *
     * @param \DOMJudgeBundle\Entity\ScoreCache $scorecache
     *
     * @return Contest
     */
    public function addScorecache(\DOMJudgeBundle\Entity\ScoreCache $scorecache)
    {
        $this->scorecache[] = $scorecache;

        return $this;
    }

    /**
     * Remove scorecache
     *
     * @param \DOMJudgeBundle\Entity\ScoreCache $scorecache
     */
    public function removeScorecache(\DOMJudgeBundle\Entity\ScoreCache $scorecache)
    {
        $this->scorecache->removeElement($scorecache);
    }

    /**
     * Get scorecache
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScorecache()
    {
        return $this->scorecache;
    }

    /**
     * Add rankcache
     *
     * @param \DOMJudgeBundle\Entity\RankCache $rankcache
     *
     * @return Contest
     */
    public function addRankcache(\DOMJudgeBundle\Entity\RankCache $rankcache)
    {
        $this->rankcache[] = $rankcache;

        return $this;
    }

    /**
     * Remove rankcache
     *
     * @param \DOMJudgeBundle\Entity\RankCache $rankcache
     */
    public function removeRankcache(\DOMJudgeBundle\Entity\RankCache $rankcache)
    {
        $this->rankcache->removeElement($rankcache);
    }

    /**
     * Get rankcache
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRankcache()
    {
        return $this->rankcache;
    }

    /**
     * Get duration for this contest
     *
     * @return string
     * @Serializer\VirtualProperty()
     * @Serializer\Type("string")
     */
    public function getDuration()
    {
        return Utils::relTime($this->getEndtime() - $this->starttime);
    }

    /**
     * Get scoreboard freeze duration for this contest
     *
     * @return string
     * @Serializer\VirtualProperty()
     * @Serializer\Type("string")
     */
    public function getScoreboardFreezeDuration()
    {
        return Utils::relTime($this->getEndtime() - $this->getFreezetime());
    }

    /**
     * Helper function to serialize this for the REST API
     *
     * @return array
     */
    public function serializeForAPI($penalty_time, $strict = false)
    {
        $res = [
            'id' => (string)$this->getCid(),
            'name' => $this->getName(),
            'formal_name' => $this->getName(),
            'start_time' => Utils::absTime($this->getStarttime()),
            'duration' => $this->getDuration(),
            'scoreboard_freeze_duration' => $this->getScoreboardFreezeDuration(),
            'penalty_time' => (int)$penalty_time,
        ];
        if (!$strict) {
            $res['external_id'] = $this->getExternalId();
            $res['shortname']   = $this->getShortname();
            $res['end_time']    = Utils::absTime($this->getEndtime());
        }
        return $res;
    }

    /**
     * Returns true iff the contest is already and still active, and not disabled.
     */
    public function isActive()
    {
        return $this->getEnabled() &&
            $this->getPublic() &&
            ($this->deactivatetime == null || $this->deactivatetime > time());
    }

    /**
     * @param $time_string
     * @return float|int|string|null
     * @throws \Exception
     */
    private function getAbsoluteTime($time_string)
    {
        if ($time_string === null) {
            return null;
        } elseif (preg_match('/^[+-][0-9]+:[0-9]{2}(:[0-9]{2}(\.[0-9]{0,6})?)?$/', $time_string)) {
            // FIXME: dedup code with non symfony code
            $sign           = ($time_string[0] == '-' ? -1 : +1);
            $time_string[0] = 0;
            $times          = explode(':', $time_string, 3);
            if (count($times) == 2) {
                $times[2] = '00';
            }
            $hours        = $times[0];
            $minutes      = $times[1];
            $seconds      = $times[2];
            $seconds      = $seconds + 60 * ($minutes + 60 * $hours);
            $seconds      *= $sign;
            $absoluteTime = $this->starttime + $seconds;

            // Take into account the removed intervals
            /** @var RemovedInterval[] $removedIntervals */
            $removedIntervals = $this->getRemovedIntervals()->toArray();
            usort($removedIntervals, function (RemovedInterval $a, RemovedInterval $b) {
                return Utils::difftime((float)$a->getStarttime(), (float)$b->getStarttime());
            });
            foreach ($removedIntervals as $removedInterval) {
                if (Utils::difftime((float)$removedInterval->getStarttime(), (float)$absoluteTime) <= 0) {
                    $absoluteTime += Utils::difftime((float)$removedInterval->getEndtime(),
                                                     (float)$removedInterval->getStarttime());
                }
            }

            return $absoluteTime;
        } else {
            $date = new \DateTime($time_string);
            return $date->format('U.v');
        }
    }

    /**
     * Add removedInterval
     *
     * @param RemovedInterval $removedInterval
     *
     * @return Contest
     */
    public function addRemovedInterval(RemovedInterval $removedInterval)
    {
        $this->removedIntervals->add($removedInterval);

        return $this;
    }

    /**
     * Remove removedInterval
     *
     * @param RemovedInterval $removedInterval
     */
    public function removeRemovedInterval(RemovedInterval $removedInterval)
    {
        $this->removedIntervals->removeElement($removedInterval);
    }

    /**
     * Get removedIntervals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRemovedIntervals()
    {
        return $this->removedIntervals;
    }

    /**
     * Get the contest time for a given wall time
     * @param float $wallTime
     * @return float
     */
    public function getContestTime(float $wallTime): float
    {
        $contestTime = Utils::difftime($wallTime, (float)$this->getStarttime());
        if (false/*ALLOW_REMOVED_INTERVALS*/) { // TODO: use constant when we have access to it in Symfony
            /** @var RemovedInterval $removedInterval */
            foreach ($this->getRemovedIntervals() as $removedInterval) {
                if (Utils::difftime((float)$removedInterval->getStarttime(), $wallTime) < 0) {
                    $contestTime -= min(
                        Utils::difftime($wallTime, (float)$removedInterval->getStarttime()),
                        Utils::difftime((float)$removedInterval->getEndtime(), (float)$removedInterval->getStarttime())
                    );
                }
            }
        }

        return $contestTime;
    }

    /**
     * Get the data to display in the jury interface for the given contest
     * @return array
     */
    public function getJuryTimeData(): array
    {
        $now         = Utils::now();
        $times       = ['activate', 'start', 'freeze', 'end', 'unfreeze', 'finalize', 'deactivate'];
        $prevchecked = false;
        $isactivated = Utils::difftime((float)$this->getActivatetime(), $now) <= 0;
        $hasstarted  = Utils::difftime((float)$this->getStarttime(), $now) <= 0;
        $hasended    = Utils::difftime((float)$this->getEndtime(), $now) <= 0;
        $hasfrozen   = !empty($this->getFreezetime()) &&
            Utils::difftime((float)$this->getFreezetime(), $now) <= 0;
        $hasunfrozen = !empty($this->getUnfreezetime()) &&
            Utils::difftime((float)$this->getUnfreezetime(), $now) <= 0;
        $isfinal     = !empty($this->getFinalizetime());

        if (!$this->getStarttimeEnabled()) {
            $hasstarted = $hasended = $hasfrozen = $hasunfrozen = false;
        }

        $result = [];
        foreach ($times as $time) {
            $resultItem = [];
            $method     = sprintf('get%stime', ucfirst($time));
            $timeValue  = $this->{$method}();
            if ($time === 'start' && !$this->getStarttimeEnabled()) {
                $resultItem['icon'] = 'ellipsis-h';
                $timeValue          = $this->getStarttime(false);
                $prevchecked        = false;
            } elseif (empty($timeValue)) {
                $resultItem['icon'] = null;
            } elseif (Utils::difftime((float)$timeValue, $now) <= 0) {
                // this event has passed, mark as such
                $resultItem['icon'] = 'check';
                $prevchecked        = true;
            } elseif ($prevchecked) {
                $resultItem['icon'] = 'ellipsis-h';
                $prevchecked        = false;
            }

            $resultItem['label'] = sprintf('%s time', ucfirst($time));
            $resultItem['time']  = Utils::printtime($timeValue, '%Y-%m-%d %H:%M (%Z)');
            if ($time === 'start' && !$this->getStarttimeEnabled()) {
                $resultItem['class'] = 'ignore';
            }

            $showButton = true;
            switch ($time) {
                case 'activate':
                    $showButton = !$isactivated;
                    break;
                case 'start':
                    $showButton = !$hasstarted;
                    break;
                case 'end':
                    $showButton = $hasstarted && !$hasended && (empty($this->getFreezetime()) || $hasfrozen);
                    break;
                case 'deactivate':
                    $showButton = $hasended && (empty($this->getUnfreezetime()) || $hasunfrozen);
                    break;
                case 'freeze':
                    $showButton = $hasstarted && !$hasended && !$hasfrozen;
                    break;
                case 'unfreeze':
                    $showButton = $hasfrozen && !$hasunfrozen && $hasended;
                    break;
                case 'finalize':
                    $showButton = $hasended && !$isfinal;
                    break;
            }

            $resultItem['show_button'] = $showButton;

            $closeToStart = Utils::difftime((float)$this->starttime,
                                            $now) <= self::STARTTIME_UPDATE_MIN_SECONDS_BEFORE;
            if ($time === 'start' && !$closeToStart) {
                $type                       = $this->getStarttimeEnabled() ? 'delay' : 'resume';
                $resultItem['extra_button'] = [
                    'type' => $type . '_start',
                    'label' => $type . ' start',
                ];
            }

            $result[$time] = $resultItem;
        }

        return $result;
    }

    /**
     * Get the state for this contest
     * @return array
     */
    public function getState()
    {
        $time_or_null             = function ($time, $extra_cond = true) {
            if (!$extra_cond || $time === null || Utils::now() < $time) {
                return null;
            }
            return Utils::absTime($time);
        };
        $result                   = [];
        $result['started']        = $time_or_null($this->getStarttime());
        $result['ended']          = $time_or_null($this->getEndtime(), $result['started'] !== null);
        $result['frozen']         = $time_or_null($this->getFreezetime(), $result['started'] !== null);
        $result['thawed']         = $time_or_null($this->getUnfreezetime(), $result['frozen'] !== null);
        $result['finalized']      = $time_or_null($this->getFinalizetime(), $result['ended'] !== null);
        $result['end_of_updates'] = null;
        if ($result['finalized'] !== null &&
            ($result['thawed'] !== null || $result['frozen'] === null)) {
            if ($result['thawed'] !== null &&
                $this->getFreezetime() > $this->getFinalizetime()) {
                $result['end_of_updates'] = $result['thawed'];
            } else {
                $result['end_of_updates'] = $result['finalized'];
            }
        }
        return $result;
    }

    /**
     * Get the number of remaining minutes for this contest
     * @return int
     */
    public function getMinutesRemaining(): int
    {
        return (int)floor(($this->getEndtime() - $this->getFreezetime()) / 60);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateTimes()
    {
        // Update the start times, as this will update all other fields
        $this->setStarttime(strtotime($this->getStarttimeString()));
        $this->setStarttimeString($this->getStarttimeString());
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context)
    {
        $this->updateTimes();
        if (Utils::difftime((float)$this->getEndtime(), (float)$this->getStarttime(true)) <= 0) {
            $context
                ->buildViolation('Contest ends before it even starts')
                ->atPath('endtimeString')
                ->addViolation();
        }
        if (!empty($this->getFreezetime())) {
            if (Utils::difftime((float)$this->getFreezetime(), (float)$this->getEndtime()) > 0 ||
                Utils::difftime((float)$this->getFreezetime(), (float)$this->getStarttime()) < 0) {
                $context
                    ->buildViolation('Freezetime is out of start/endtime range')
                    ->atPath('freezetimeString')
                    ->addViolation();
            }
        }
        if (Utils::difftime((float)$this->getActivatetime(), (float)$this->getStarttime()) > 0) {
            $context
                ->buildViolation('Activate time is later than starttime')
                ->atPath('activatetimeString')
                ->addViolation();
        }
        if (!empty($this->getUnfreezetime())) {
            if (empty($this->getFreezetime())) {
                $context
                    ->buildViolation('Unfreezetime set but no freeze time. That makes no sense.')
                    ->atPath('unfreezetimeString')
                    ->addViolation();
            }
            if (Utils::difftime((float)$this->getUnfreezetime(), (float)$this->getEndtime()) < 0) {
                $context
                    ->buildViolation('Unfreezetime must be larger than endtime.')
                    ->atPath('unfreezetimeString')
                    ->addViolation();
            }
            if (!empty($this->getDeactivatetime()) &&
                Utils::difftime((float)$this->getDeactivatetime(), (float)$this->getUnfreezetime()) < 0) {
                $context
                    ->buildViolation('Deactivatetime must be larger than unfreezetime.')
                    ->atPath('deactivatetimeString')
                    ->addViolation();
            }
        } else {
            if (!empty($this->getDeactivatetime()) &&
                Utils::difftime((float)$this->getDeactivatetime(), (float)$this->getEndtime()) < 0) {
                $context
                    ->buildViolation('Deactivatetime must be larger than endtime.')
                    ->atPath('deactivatetimeString')
                    ->addViolation();
            }
        }

        /** @var ContestProblem $problem */
        foreach ($this->problems as $idx => $problem) {
            // Set cid and probid explicitly, as Doctrine doesn't understand our primary key
            // that is also a foreign key
            $problem->setContest($this);
            $problem->setCid($this->getCid());
            $problem->setProbid($problem->getProblem()->getProbid());

            // Check if the problem ID is unique
            $otherProblemIds = $this->problems->filter(function (ContestProblem $otherProblem) use ($problem) {
                return $otherProblem !== $problem;
            })->map(function (ContestProblem $problem) {
                return $problem->getProblem()->getProbid();
            })->toArray();
            $problemId       = $problem->getProblem()->getProbid();
            if (in_array($problemId, $otherProblemIds)) {
                $context
                    ->buildViolation('Each problem can only be added to a contest once')
                    ->atPath(sprintf('problems[%d].problem', $idx))
                    ->addViolation();
            }

            // Check if the problem shortname is unique
            $otherShortNames = $this->problems->filter(function (ContestProblem $otherProblem) use ($problem) {
                return $otherProblem !== $problem;
            })->map(function (ContestProblem $problem) {
                return $problem->getShortname();
            })->toArray();
            $shortname = $problem->getShortname();
            if (in_array($shortname, $otherShortNames)) {
                $context
                    ->buildViolation('Each shortname should be unique within a contest')
                    ->atPath(sprintf('problems[%d].shortname', $idx))
                    ->addViolation();
            }
        }
    }
}
