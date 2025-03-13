<?php




class ModelProject  {

    protected $name = '';
    protected $uuid = '';
    protected $contexts = [];
    protected $links = [];
    protected $hidden = false;
    protected $tstamp = 0;
    protected $backendPathSegment = '';
    protected $_additionalData = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return array
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @param array $contexts
     */
    public function setContexts(array $contexts): void
    {
        foreach($contexts as $context)  {
            $this->contexts[] = Loader::get(ModelContext::class, $context);
        }
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param array $links
     */
    public function setLinks(array $links): void
    {
        foreach($links as $link)  {
            $this->links[] = Loader::get(ModelLink::class, $link);
        }
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        // check if uuid is present - if not, randomize one
        if (!$uuid) {
            $uuid = substr(md5(mt_rand()), 0, 7);
        }
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getBackendPathSegment(): string
    {
        return $this->backendPathSegment;
    }

    /**
     * @param string $backendPathSegment
     */
    public function setBackendPathSegment(string $backendPathSegment): void
    {
        $this->backendPathSegment = $backendPathSegment;
    }

    /**
     * @return int
     */
    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    /**
     * @param int $tstamp
     */
    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }


    /**
     * @var RepositoryApp|XCore|object
     */
    protected $App;


    /**
     * @param array $itemRow
     */
    public function __construct(array $itemRow)
    {
        $this->App = XCore::App();
        $this->setName((string) $itemRow['name']);
        $this->setUuid((string) $itemRow['uuid']);
        // casting doesn't work well with bool on string - always returns true
        $this->setContexts((array) $itemRow['contexts']);
        $this->setLinks((array) $itemRow['links']);
        $this->setHidden(strtolower($itemRow['hidden']) == 'true');
        $this->setTstamp((int) $itemRow['tstamp']);
        $this->setBackendPathSegment((string) $itemRow['backendPathSegment']);
    }



    public function toArray(): array
    {
        $contexts = [];
        foreach ($this->getContexts() as $Context)  {
            $contexts[] = $Context->toArray();
        };
        $links = [];
        foreach ($this->getLinks() as $Link)  {
            $links[] = $Link->toArray();
        };

        return [
            'name' => $this->getName(),
            'uuid' => $this->getUuid(),
            'contexts' => $contexts,
            'links' => $links,
            'hidden' => $this->isHidden(),
            'tstamp' => $this->getTstamp(),
            'backendPathSegment' => $this->getBackendPathSegment(),
        ];
    }


    public function JSONize()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function store($filename = ''): bool
    {
        $filename = $filename ?: 'project__' . strtolower(substr($this->getName(), 0, 3)) . '__' . $this->getUuid() . '.json';
        // add this end linebreak exactly like in js download
        return (bool) file_put_contents($this->App->getDataPath() . $filename, $this->JSONize() . "\n");
    }


    public function addAdditionalData($key, $data)
    {
        $this->_additionalData[$key] = $data;
    }

    public function getAdditionalData($key)
    {
        return $this->_additionalData[$key];
    }
}


