<?php
/**
 * This file contains the Export class
 */

namespace Charm\DataExporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class Exporter
 *
 * Handling data export to file
 *
 * @package Charm\Guard
 */
class Export
{
    /** @var Spreadsheet  the spreadseet */
    protected $spreadsheet;

    /** @var array  with all data */
    protected $arr;

    /** @var string  the file type */
    protected $type = 'xlsx';

    /**
     * Export constructor.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct()
    {
        // Init PHPExcel
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet = $spreadsheet;

        $this->arr = [];
    }

    /**
     * Set the spreadsheet file type
     *
     * As default this will create a xlsx file, if the type is not changed.
     *
     * @param string  $type  the wanted type: xlsx, xls, ods
     */
    public function setType($type)
    {
        $this->type = strtolower($type);
    }

    /**
     * Get the active work sheet
     *
     * @return Worksheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function getActiveSheet()
    {
        return $this->spreadsheet->getActiveSheet();
    }

    /**
     * Set title of active work sheet
     *
     * @param string  $title  the title
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setSheetTitle($title)
    {
        $this->getActiveSheet()->setTitle($title);

        return $this;
    }

    /**
     * Add title row to main data array
     *
     * @param $arr
     *
     * @return $this
     */
    public function addTitleRow($arr)
    {
        $this->arr = $arr + $this->arr;
        return $this;
    }

    /**
     * Add data to main data array
     *
     * @param $arr
     *
     * @return $this
     */
    public function addData($arr)
    {
        $this->arr = $this->arr + $arr;
        return $this;
    }

    /**
     * Freeze first row (title row)
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function freezeFirstRow()
    {
        $this->getActiveSheet()->freezePane('A2');
        return $this;
    }

    /**
     * Freeze first column
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function freezeFirstColumn()
    {
        $this->getActiveSheet()->freezePane('B1');
        return $this;
    }

    /**
     * Freeze first column and row
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function freezeFirstColumnAndRow()
    {
        $this->getActiveSheet()->freezePane('B2');
        return $this;
    }

    /**
     * Set number style for a row
     *
     * @param string  $row    name of row (e.g. A, Z, AAA)
     * @param string  $style  wanted style (name of constant from \PHPExcel_Style_NumberFormat class)
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setRowNumberStyle($row, $style)
    {
        $row_dimension = $row . "1:" . $row . "9999999";

        $this->getActiveSheet()
            ->getStyle($row_dimension)
            ->getNumberFormat()
            ->setFormatCode("\\PHPExcel_Style_NumberFormat::" . $style);

        return $this;
    }

    /**
     * Format the title row
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function formatTitleRow()
    {
        // We want it from A1 to K1, Z1 or how far it will go
        // So get highest column
        $highest_column = $this->getActiveSheet()->getHighestColumn();
        // Remove numbers
        $highest_column = preg_replace('/[0-9]+/', '', $highest_column);

        $this->getActiveSheet()->getStyle("A1:" . $highest_column . "1")->applyFromArray([
            'borders' => [
                'outline' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '9BC2E6']
                ]
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '4471B5'],
                'size' => 12,
                'name' => 'Calibri'
            ]
        ]);
        return $this;
    }

    /**
     * Basic style format for whole sheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function baseFormat()
    {
        $this->getActiveSheet()->getStyle($this->getActiveSheet()->calculateWorksheetDataDimension())->applyFromArray([
            'font' => [
                'size' => 11,
                'name' => 'Calibri'
            ]
        ]);
    }

    public function getWriter()
    {
        return match ($this->type) {
            'xlsx' => new Xlsx($this->spreadsheet),
            'xls' => new Xls($this->spreadsheet),
            'ods' => new Ods($this->spreadsheet),
            'csv' => new Csv($this->spreadsheet),
            default => new Xlsx($this->spreadsheet),
        };
    }

    /**
     * Save the data to file
     *
     * @param string  $filename  absolute path to file with filename + extension (xlsx)
     * @param bool    $override  (optional) override file if existing? Default: false
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function saveToFile($filename, $override = false)
    {
        // Add data to sheet
        $this->getActiveSheet()->fromArray($this->arr);

        // Format title and so on
        $this->baseFormat();
        $this->formatTitleRow();

        // Create writer based on type
        $writer = $this->getWriter();

        if (file_exists($filename) && $override) {
            unlink($filename);
        }

        $writer->save($filename);
    }
}