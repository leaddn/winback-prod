<?php

namespace App\Entity\Main;

use App\Repository\DeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
#[ORM\Table(name:"`device`")]
/**
 * @ORM\Table(name="device", indexes={@ORM\Index(columns={"sn", "version"}, flags={"fulltext"})})
 */
class Device
{
    /*
    #[ORM\Id]
    */
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;


    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private $sn;

    #[ORM\Column(type: 'string', nullable: true)]
    private $version;

    #[ORM\Column(type: 'string', name:"`version_upload`")]
    private $versionUpload;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $forced = false;

    #[ORM\Column(type: 'string', length: 255, name:"`ip_addr`")]
    private $ipAddr;

    #[ORM\Column(type: 'integer', name:"`log_pointeur`")]
    private $logPointeur;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $pub;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name:"`code_pin`")]
    private $codePin;

    #[ORM\ManyToOne(targetEntity: DeviceFamily::class, inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: false, name:"`device_family_id`")]
    private $deviceFamily;

    #[ORM\Column(type: 'boolean')]
    private $selected = false;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: "datetime_immutable", nullable: true)]
    private $created_at;
    

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_at', type: "datetime", nullable: true)]
    private $updated_at;

    
    #[ORM\Column(type: 'boolean', name:"`is_active`")]
    private $isActive = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name:"`device_file`")]
    private $deviceFile;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name:"`log_file`")]
    private $logFile;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'date', nullable: true, name:"`server_date`")]
    private $serverDate;

    #[ORM\ManyToMany(targetEntity: Software::class, inversedBy: 'devices')]
    private $softwares;

    #[ORM\Column(type: 'boolean')]
    private $connected = false;

    #[ORM\Column(type: 'integer')]
    private $download;

    #[ORM\Column(type: 'string', length: 400, nullable: true)]
    private $comment;

    #[ORM\OneToMany(mappedBy: 'device_id', targetEntity: DeviceServer::class)]
    private Collection $deviceServers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\OneToMany(mappedBy: 'serial_number', targetEntity: Log::class, orphanRemoval: true)]
    private Collection $logs;

    #[ORM\OneToMany(mappedBy: 'sn', targetEntity: Error::class)]
    private Collection $errors;

    #[ORM\Column]
    private ?bool $server_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $server_ip = null;

    #[ORM\Column(length: 255)]
    private ?string $server_port = null;

    #[ORM\Column]
    private ?int $config = 0;

    public function __construct()
    {
        $this->versionUpload = new ArrayCollection();
        //$this->statistics = new ArrayCollection();
        $this->softwares = new ArrayCollection();
        $this->deviceServers = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->errors = new ArrayCollection();
    }

    public function __toString()
    {
        if(is_null($this->sn)) {
            return 'NULL';
        }
        //return $this->version;
        return $this->sn;
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }
    

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(string $sn): self
    {
        $this->sn = $sn;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getVersionUpload(): ?string
    {
        return $this->versionUpload;
    }

    public function setVersionUpload(string $versionUpload): self
    {
        $this->versionUpload = $versionUpload;

        return $this;
    }

    public function getForced(): ?bool
    {
        return $this->forced;
    }

    public function setForced(?bool $forced): self
    {
        $this->forced = $forced;

        return $this;
    }

    public function getIpAddr(): ?string
    {
        return $this->ipAddr;
    }

    public function setIpAddr(string $ipAddr): self
    {
        $this->ipAddr = $ipAddr;

        return $this;
    }

    public function getLogPointeur(): ?int
    {
        return $this->logPointeur;
    }

    public function setLogPointeur(int $logPointeur): self
    {
        $this->logPointeur = $logPointeur;

        return $this;
    }

    public function getPub(): ?bool
    {
        return $this->pub;
    }

    public function setPub(?bool $pub): self
    {
        $this->pub = $pub;

        return $this;
    }

    public function getCodePin(): ?string
    {
        return $this->codePin;
    }

    public function setCodePin(?string $codePin): self
    {
        $this->codePin = $codePin;

        return $this;
    }

    public function getDeviceFamily(): ?DeviceFamily
    {
        return $this->deviceFamily;
    }

    public function setDeviceFamily(?DeviceFamily $deviceFamily): self
    {
        $this->deviceFamily = $deviceFamily;

        return $this;
    }

    public function getSelected(): ?bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getDeviceFile(): ?string
    {
        return $this->deviceFile;
    }

    public function setDeviceFile(string $deviceFile): self
    {
        $this->deviceFile = $deviceFile;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): self
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getServerDate(): ?\DateTimeInterface
    {
        return $this->serverDate;
    }

    public function setServerDate(?\DateTimeInterface $serverDate): self
    {
        $this->serverDate = $serverDate;

        return $this;
    }

    /**
     * @return Collection<int, Software>
     */
    public function getSoftwares(): Collection
    {
        return $this->softwares;
    }

    public function addSoftware(Software $software): self
    {
        if (!$this->softwares->contains($software)) {
            $this->softwares[] = $software;
        }

        return $this;
    }

    public function removeSoftware(Software $software): self
    {
        $this->softwares->removeElement($software);

        return $this;
    }

    public function getConnected(): ?bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): self
    {
        $this->connected = $connected;

        return $this;
    }

    public function getDownload(): ?int
    {
        return $this->download;
    }

    public function setDownload(int $download): self
    {
        $this->download = $download;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Collection<int, DeviceServer>
     */
    public function getDeviceServers(): Collection
    {
        return $this->deviceServers;
    }

    /*
    public function addDeviceServer(DeviceServer $deviceServer): self
    {
        if (!$this->deviceServers->contains($deviceServer)) {
            $this->deviceServers->add($deviceServer);
            $deviceServer->setDeviceId($this);
        }

        return $this;
    }

    public function removeDeviceServer(DeviceServer $deviceServer): self
    {
        if ($this->deviceServers->removeElement($deviceServer)) {
            // set the owning side to null (unless already changed)
            if ($deviceServer->getDeviceId() === $this) {
                $deviceServer->setDeviceId(null);
            }
        }

        return $this;
    }
    */

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }
    
    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Collection<int, Log>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setSerialNumber($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getSerialNumber() === $this) {
                $log->setSerialNumber(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Log>
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function isServerId(): ?bool
    {
        return $this->server_id;
    }

    public function setServerId(bool $server_id): self
    {
        $this->server_id = $server_id;

        return $this;
    }

    public function getServerIp(): ?string
    {
        return $this->server_ip;
    }

    public function setServerIp(?string $server_ip): self
    {
        $this->server_ip = $server_ip;

        return $this;
    }

    public function getServerPort(): ?string
    {
        return $this->server_port;
    }

    public function setServerPort(string $server_port): self
    {
        $this->server_port = $server_port;

        return $this;
    }

    public function getConfig(): ?bool
    {
        return $this->config;
    }

    public function setConfig(bool $config): self
    {
        $this->config = $config;

        return $this;
    }
}
