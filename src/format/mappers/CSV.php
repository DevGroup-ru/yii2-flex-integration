<?php

namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\models\BaseTask;
use PHPExcel_IOFactory;
use PHPExcel_Reader_CSV;

class CSV extends FormatMapper
{
    use Document2D;

    public $delimiter = ',';
    public $enclosure = '"';
    public $escape = '"';
    // In case of UTF-8, UTF-16, etc. will skip BOM automatically if exists
    // can be CP1251
    public $encoding = 'UTF-8';


    /**
     * @param \DevGroup\FlexIntegration\models\BaseTask $task
     * @param string $document
     * @param string $sourceId
     *
     * @return AbstractEntity[]
     */
    public function mapInputDocument(BaseTask $task, $document, $sourceId)
    {
        /** @var AbstractEntity[] $entities */
        $entities = [];
        $objReader = new PHPExcel_Reader_CSV();
        $objReader->setInputEncoding($this->encoding);
        $objReader->setDelimiter($this->delimiter);
        $objReader->setEnclosure($this->enclosure);
        $objReader->setSheetIndex(0);

        $objPHPExcel = $objReader->load($document);
        $line = 0;
        $objWorksheet = $objPHPExcel->getActiveSheet();

        foreach ($objWorksheet->getRowIterator() as $row) {

            $line++;
            if ($this->maxLines > 0 && $line === ($this->maxLines + $this->skipLinesFromTop)) {
                // we've reached max lines limit
                break;
            }
            if ($this->skipLinesFromTop > 0 && $line <= $this->skipLinesFromTop) {
                // we'r skipping first lines as specified
                continue;
            }


            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);
            $data = [];
            /** @var \Iterator $cellIterator */
            foreach ($cellIterator as $cell) {
                /** @var $cell \PHPExcel_Cell */
                $value = $cell->getValue();
                $data[] = $value;
            }

            $result = $this->processRow($data, $sourceId, md5($document));
            foreach ($result as $abstractEntity) {
                // CSV files don't have sheets
                $abstractEntity->sourceId = $sourceId;
                $entities[] = $abstractEntity;
            }
        }
        return $entities;
    }
}
