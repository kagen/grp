<?php
class ExcelMaker {
	function download ($fname, $bookData, $showZero = false, $colWidth = null, $orientation_portrait = true, $writeAsText = false) {
		require_once 'PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		if (isset($bookData['data'])) {
			$sheetidx = 0;
			$objPHPExcel->removeSheetByIndex(0);
			PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
			foreach ($bookData['data'] as $sheet => $grid) {
				$objWorkSheet = $objPHPExcel->createSheet($sheetidx);
				$objWorkSheet->setTitle($sheet);
				foreach ($grid as $startcell => $griddata) {
					if (is_array($griddata) && count($griddata) > 0 && $objWorkSheet->getCell($startcell)) {

						if ($writeAsText) {
							#書き出す範囲の書式設定をテキストに
							$startrow = $objWorkSheet->getCell($startcell)->getRow();
							$startcol = PHPExcel_Cell::columnIndexFromString($objWorkSheet->getCell($startcell)->getColumn()) - 1;

							$data_rowcount = count($griddata);
							$data_colcount = count(array_keys($griddata[0])) - 1;
							$max_row = $data_rowcount + $startrow;
							$max_col = PHPExcel_Cell::stringFromColumnIndex($startcol + $data_colcount);
							$data_address = $startcell.":".$max_col.$max_row;
							$objWorkSheet->getStyle($data_address)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
						}

						$objWorkSheet->fromArray(array_keys($griddata[0]), NULL, $startcell, $showZero);
						$dataInsCell = strval($objWorkSheet->getCell($startcell)->getColumn().($objWorkSheet->getCell($startcell)->getRow() + 1));
						$objWorkSheet->fromArray($griddata, NULL, $dataInsCell, $showZero);
					} elseif ($griddata) {
						$objWorkSheet->setCellValue($startcell , strval($griddata));
					}
				}
				$sheetidx++;

				if ($colWidth) {
					if (isset($colWidth[$sheet])) {
						$intCols = PHPExcel_Cell::columnIndexFromString($objWorkSheet->getHighestColumn());
						$startCol = PHPExcel_Cell::columnIndexFromString($objWorkSheet->getCell($startcell)->getColumn()) - 1;
						for ($i = 0; $i <= $intCols; $i++) {
							$strCol = PHPExcel_Cell::stringFromColumnIndex($startCol + $i);
							if (isset($colWidth[$sheet][$strCol])) {
								$objWorkSheet->getColumnDimension($strCol)->setWidth($colWidth[$sheet][$strCol]);
							}
						}
					}
				}
				//Set Orientation, size and scaling
				$objWorkSheet->getPageSetup()->setOrientation(($orientation_portrait?PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT:PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE));
				$objWorkSheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
				$objWorkSheet->getPageSetup()->setFitToPage(true);
				$objWorkSheet->getPageSetup()->setFitToWidth(1);
				$objWorkSheet->getPageSetup()->setFitToHeight(0);
			}
			$objPHPExcel->setActiveSheetIndex(0);
		}

		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename*=UTF-8\'\''.rawurlencode($fname));
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

	}
}
?>