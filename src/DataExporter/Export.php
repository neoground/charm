<?php
/**
 * This file contains the Export class
 */

namespace Charm\DataExporter;

use Charm\Vivid\C;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class Exporter
 *
 * Handling array export to file
 *
 * Supported files: csv, html, xlsx, ods
 *
 * @package Charm\DataExporter
 */
class Export
{
    /** @var Spreadsheet  the spreadseet */
    protected Spreadsheet $spreadsheet;

    /** @var array  with all data */
    protected array $arr;

    /** @var string  the file type */
    protected string $type = 'xlsx';

    /**
     * Export constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        // Init PHPSpreadsheet
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
     * @param string $type the wanted type: xlsx, xls, ods
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
     * @throws Exception
     */
    public function getActiveSheet()
    {
        return $this->spreadsheet->getActiveSheet();
    }

    /**
     * Get the whole spreadsheet instance
     *
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * Set spreadsheet metadata
     *
     * @param string|null $creator
     * @param string|null $title
     * @param string|null $subject
     * @param string|null $description
     * @param string|null $lastModifiedBy
     *
     * @return $this
     */
    public function setMetadata(string $creator = null, string $title = null, string $subject = null, string $description = null, string $lastModifiedBy = null): self
    {
        $props = $this->spreadsheet->getProperties();

        if (!empty($creator)) {
            $props->setCreator($creator);
        }

        if (!empty($title)) {
            $props->setTitle($title);
        }

        if (!empty($subject)) {
            $props->setSubject($subject);
        }

        if (!empty($description)) {
            $props->setDescription($description);
        }

        if (!empty($lastModifiedBy)) {
            $props->setLastModifiedBy($lastModifiedBy);
        }

        return $this;
    }

    /**
     * Set title of active work sheet
     *
     * @param string $title the title
     *
     * @return $this
     *
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function freezeFirstColumnAndRow()
    {
        $this->getActiveSheet()->freezePane('B2');
        return $this;
    }

    /**
     * Auto size for all columns (auto width)
     *
     * @return $this
     *
     * @throws Exception
     */
    public function autoColumnSize()
    {
        foreach ($this->getActiveSheet()->getColumnIterator() as $column) {
            $this->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        return $this;
    }

    /**
     * Set number style for a row
     *
     * @param string $row   name of row (e.g. A, Z, AAA)
     * @param string $style wanted style (name of constant from NumberFormat class)
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setRowNumberStyle($row, $style)
    {
        $row_dimension = $row . "1:" . $row . "9999999";

        $this->getActiveSheet()
            ->getStyle($row_dimension)
            ->getNumberFormat()
            ->setFormatCode("\\PhpOffice\\PhpSpreadsheet\\Style\\NumberFormat::" . $style);

        return $this;
    }

    /**
     * Format the title row
     *
     * @return $this
     *
     * @throws Exception
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
                    'style' => Border::BORDER_THIN,
                    'color' => ['rgb' => '9BC2E6'],
                ],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '4471B5'],
                'size' => 12,
                'name' => 'Calibri',
            ],
        ]);
        return $this;
    }

    /**
     * Basic style format for whole sheet
     *
     * @throws Exception
     */
    private function baseFormat()
    {
        $this->getActiveSheet()->getStyle($this->getActiveSheet()->calculateWorksheetDataDimension())->applyFromArray([
            'font' => [
                'size' => 11,
                'name' => 'Calibri',
            ],
        ]);
    }

    public function getWriter()
    {
        return match ($this->type) {
            'xlsx' => new Xlsx($this->spreadsheet),
            'xls' => new Xls($this->spreadsheet),
            'ods' => new Ods($this->spreadsheet),
            'csv' => new Csv($this->spreadsheet),
            'html' => new Html($this->spreadsheet),
            default => new Xlsx($this->spreadsheet),
        };
    }

    /**
     * Save the data to file
     *
     * @param string $filename absolute path to file with filename + extension (xlsx)
     * @param bool   $override (optional) override file if existing? Default: false
     *
     * @throws Exception
     */
    public function saveToFile(string $filename, bool $override = false)
    {
        // Add data to sheet
        $this->getActiveSheet()->fromArray($this->arr);

        // Format title and so on
        $this->baseFormat();
        $this->formatTitleRow();

        // Only A1 should be selected by default
        $this->getActiveSheet()->setSelectedCells('A1');

        // Create writer based on type
        $writer = $this->getWriter();

        if ($override) {
            C::Storage()->deleteFileIfExists($filename);
        }

        $writer->save($filename);
    }
}