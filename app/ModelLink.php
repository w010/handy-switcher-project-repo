<?php




class ModelLink  {

    protected $name = '';
    protected $url = '';
    protected $hidden = false;


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
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }



    /**
     * @param array $itemRow
     */
    public function __construct(array $itemRow)
    {
        $this->setName((string) $itemRow['name']);
        $this->setUrl((string)  $itemRow['url']);
        $this->setHidden(strtolower($itemRow['hidden']) == 'true');
    }


    public function toArray()   {
        return [
            'name' => $this->getName(),
            'url' => $this->getUrl(),
            'hidden' => $this->isHidden(),
        ];
    }
}


