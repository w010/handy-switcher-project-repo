<?php




class ModelContext  {

    protected $name = '';
    protected $url = '';
    protected $color = '';
    protected $hidden = false;


    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void {
        $this->hidden = $hidden;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getColor(): string {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color): void {
        $this->color = $color;
    }



    /**
     * @param array $itemRow
     */
    public function __construct(array $itemRow)
    {
        $this->setName((string) $itemRow['name']);
        $this->setUrl((string) $itemRow['url']);
        $this->setColor((string) $itemRow['color']);
        $this->setHidden(is_bool($itemRow['hidden']) ? $itemRow['hidden'] : $itemRow['hidden'] === 'true');
    }


    public function toArray()   {
        // it's good to keep here the same order as in js - to have exported files the same and avoid false diffs
        return [
            'name' => $this->getName(),
            'url' => $this->getUrl(),
            'color' => $this->getColor(),
            'hidden' => $this->isHidden(),
        ];
    }
}


