<?php

namespace App\Entity\Main;

use App\Repository\StatisticsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticsRepository::class)]
class Statistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /*
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'statistics')]
    private $sn;
    */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $version;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $zone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $pathoType;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $pathoName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $sessionNum;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $position;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $tools;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $painPre;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $painPost;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $statisticsFile;

    public function getId(): ?int
    {
        return $this->id;
    }

    /*
    public function getSn(): ?Device
    {
        return $this->sn;
    }

    public function setSn(?Device $sn): self
    {
        $this->sn = $sn;

        return $this;
    }
    */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(string $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getPathoType(): ?string
    {
        return $this->pathoType;
    }

    public function setPathoType(string $pathoType): self
    {
        $this->pathoType = $pathoType;

        return $this;
    }

    public function getPathoName(): ?string
    {
        return $this->pathoName;
    }

    public function setPathoName(string $pathoName): self
    {
        $this->pathoName = $pathoName;

        return $this;
    }

    public function getSessionNum(): ?int
    {
        return $this->sessionNum;
    }

    public function setSessionNum(int $sessionNum): self
    {
        $this->sessionNum = $sessionNum;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTools(): ?string
    {
        return $this->tools;
    }

    public function setTools(string $tools): self
    {
        $this->tools = $tools;

        return $this;
    }

    public function getPainPre(): ?int
    {
        return $this->painPre;
    }

    public function setPainPre(int $painPre): self
    {
        $this->painPre = $painPre;

        return $this;
    }

    public function getPainPost(): ?int
    {
        return $this->painPost;
    }

    public function setPainPost(int $painPost): self
    {
        $this->painPost = $painPost;

        return $this;
    }

    public function getStatisticsFile(): ?string
    {
        return $this->statisticsFile;
    }

    public function setStatisticsFile(string $statisticsFile): self
    {
        $this->statisticsFile = $statisticsFile;

        return $this;
    }
}
