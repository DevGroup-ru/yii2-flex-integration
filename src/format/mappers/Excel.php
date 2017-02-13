<?php

namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\models\BaseTask;
use PHPExcel_IOFactory;
use PHPExcel_Reader_CSV;
use PHPExcel_Reader_Excel2007;
use PHPExcel_Reader_Excel5;

class Excel extends FormatMapper implements MapperGeneratorInterface
{
    use Document2D;

    const FORMAT_EXCEL2007 = 'Excel2007';
    const FORMAT_EXCEL2003 = 'Excel2003';
    const FORMAT_OOCALC = 'OOCALC';
    const FORMAT_EXCEL5 = 'Excel5';

    public $format = 'Excel2007';
    public $office2003Compatibility = false;


    /**
     * @param \DevGroup\FlexIntegration\models\BaseTask $task
     * @param string $document
     * @param string $sourceId
     *
     * @return AbstractEntity[]
     */
    public function mapInputDocument(BaseTask $task, $document, $originalSourceId)
    {
        /** @var AbstractEntity[] $entities */
        $entities = [];

        foreach($this->getGenerator($task, $document, $originalSourceId) as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @param BaseTask $task
     * @param $document
     * @param $originalSourceId
     * @return \Generator
     */
    public function getGenerator(BaseTask $task, $document, $originalSourceId)
    {

        $objReader = null;
        switch ($this->format) {
            case self::FORMAT_EXCEL5:
                $objReader = new PHPExcel_Reader_Excel5();
                break;
            case self::FORMAT_EXCEL2003:
                $objReader = new \PHPExcel_Reader_Excel2003XML();
                break;
            case self::FORMAT_OOCALC:
                $objReader = new \PHPExcel_Reader_OOCalc();
                break;
            case self::FORMAT_EXCEL2007:
            default:
                $objReader = new PHPExcel_Reader_Excel2007();
                break;
        }

        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($document);
        $line = 0;
        $sheets = $objPHPExcel->getAllSheets();

        foreach ($sheets as $index => $objWorksheet) {
            $sheetId = $objWorksheet->hasCodeName() ? $objWorksheet->getCodeName() : "__$index";
            $sourceId = $originalSourceId . ':' . $sheetId;

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


                try {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true);
                } catch (\PHPExcel_Exception $e) {
                    continue;
                }
                $data = [];
                /** @var \Iterator $cellIterator */
                foreach ($cellIterator as $cell) {
                    /** @var $cell \PHPExcel_Cell */
                    $value = $cell->getValue();
                    $data[] = $value;
                }

                $result = $this->processRow($data, $sourceId, $sheetId);
                foreach ($result as $abstractEntity) {
                    // CSV files don't have sheets
                    $abstractEntity->sourceId = $sourceId;
                    yield $abstractEntity;
                }
            }
        }
    }


}
