<?php

namespace App\Services\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared PhpSpreadsheet styling helpers for the generated activity
 * template services (v4.13).
 */
trait StylesTemplateSheet
{
    /**
     * Attach a non-strict LIST validation to a cell. Short vocabularies use
     * an inline "a,b,c" formula; longer ones are written to the hidden
     * Lists sheet and referenced by range (Excel caps inline lists at 255
     * characters). Typed free text is still accepted (D9/D10 convention).
     */
    protected function addListDropdown(Worksheet $sheet, string $cell, array $options, Worksheet $lists, int &$listsColumn): void
    {
        $inline = '"'.implode(',', $options).'"';

        if (strlen($inline) > 255) {
            $letter = Coordinate::stringFromColumnIndex($listsColumn++);
            $lists->fromArray($options, null, $letter.'1');
            $formula = "'Lists'!\${$letter}\$1:\${$letter}\$".count($options);
        } else {
            $formula = $inline;
        }

        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setFormula1($formula);
        // PhpSpreadsheet inverts this vs the OOXML attribute:
        // the property must be TRUE for the arrow to render (§14 gotcha).
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(false);
        $validation->setAllowBlank(true);
    }

    /**
     * Non-strict numeric range validation (typed values are accepted and
     * validated by the importer instead).
     */
    protected function addDecimalRange(Worksheet $sheet, string $cell, string $min, string $max): void
    {
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_DECIMAL);
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1($min);
        $validation->setFormula2($max);
        $validation->setShowErrorMessage(false);
        $validation->setAllowBlank(true);
    }
}
