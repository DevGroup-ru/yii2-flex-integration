<?php

namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\models\BaseTask;

class CSV extends FormatMapper
{
    use Document2D;

    public $delimiter = ',';
    public $enclosure = '"';
    public $escape = '"';


    /**
     * @param \DevGroup\FlexIntegration\models\BaseTask $task
     * @param string $document
     *
     * @return AbstractEntity[]
     */
    public function mapInputDocument(BaseTask $task, $document)
    {
        /** @var AbstractEntity[] $entities */
        $entities = [];
        $f = fopen($document, 'rb');
        $line = 0;
        while (($data = fgetcsv($f, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $line++;
            if ($this->maxLines > 0 && $line === ($this->maxLines + $this->skipLinesFromTop)) {
                // we've reached max lines limit
                break;
            }
            if ($this->skipLinesFromTop > 0 && $line <= $this->skipLinesFromTop) {
                // we'r skipping first lines as specified
                continue;
            }
            $result = $this->processRow($data);
            foreach ($result as $abstractEntity) {
                $entities[] = $abstractEntity;
            }
        }

        fclose($f);
        return $entities;
    }
}
