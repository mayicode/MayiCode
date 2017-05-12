<?php
/**
  用的是tinkphp Onethink
例子：
		$info['set_sheet_name'] = array('模板','填表说明');
		$info['set_aligncenter'][0] = array('A1','B1','C1','D1','E1','F1');
		$info['set_fontcolor'][0] = array('A1','F1');
		$info['set_BorderStyle'][0] = 'A1:F20';
		$info['set_width'][0] = array(
			'A'=>'20',
			'B'=>'20',
			'C'=>'20',
			'D'=>'20',
			'E'=>'20',
			'F'=>'20',
		);

		//限制下拉菜单
		$subject = D('Subject')->getField('name',true);
		$subject_str = '"'.implode(',',$subject).'"';
		$section = C('SECTION_TYPE');
		$section_str = '"'.implode(',',$section).'"';

		$new_arr = array();
		for($i=2;$i<21;$i++){
			$new_arr1 = array(
				'B'.$i=>'"男,女"',
				'D'.$i=>'"汉族,蒙古族,回族,藏族,维吾尔族,苗族,彝族,壮族,布依族,朝鲜族,满族,侗族,瑶族,白族,土家族,哈尼族,哈萨克族,傣族,黎族,傈僳族,佤族,畲族,高山族,拉祜族,水族,东乡族,纳西族,景颇族,柯尔克孜族,土族,达斡尔族,仫佬族,羌族,布朗族,撒拉族,毛难族,仡佬族,锡伯族,阿昌族,普米族,塔吉克族,怒族,乌孜别克族,俄罗斯族,鄂温克族,德昂族,保安族,裕固族,京族,塔塔尔族,独龙族,鄂伦春族,赫哲族,门巴族,珞巴族,基诺族,穿青人族,其他,外国血统中国籍人士"',
				'F'.$i=>$subject_str,
				'E'.$i=>$section_str
			);
			$new_arr = array_merge($new_arr,$new_arr1);
		}
		$info['set_Validation'][0] = $new_arr;

		$data = array(
			array(
				'A1'=>'姓名',
				'B1'=>'性别',
				'C1'=>'出生日期',
				'D1'=>'民族',
				'E1'=>'学段',
				'F1'=>'科目',
			),
			array(
				'A1'=>'1、姓名、科目必填',
				'A2'=>'2、出生日期格式为“20080101”',
				'A3'=>'3、性别、民族、学段、科目为下拉列表，请不要自行编辑，如果教师有多个科目，请填写一个主要科目，其他科目在系统中自行维护',
				'A4'=>'4、文件名教师信息后加学校名称',
			)
		);

		$filename = C('WEB_SITE_TITLE').'标题';
		exportExcelFun($filename,$data,$info);

*/



/**
 * @param $file_name 保存文件名
 * @param $data 保存数据
 * $data 例子：
 * array(
 *      '0'=>array(//第一个工作表
 *              'A1'=>'值'，
 *              'B1'=>'值'
 *          )
 *      '1'=>array(//第二个工作表
 *              'A1'=>'值'，
 *              'B1'=>'值'
 *          )
 * )
 *
 * @param $info 设置格式
     * @set_sheet_name:工作薄名称（key 第几个工作表，val 设置值）
     * @set_height:单元格高度(key:第几个工作表，v->k第几行，v->v：多高)
     * @set_width:设置宽度 同上
     * @set_aligncenter:居中显示(k:工作表，val array居中项)
     * @set_fontcolor:设置红色字 同↑
     * @set_wrapText:设置文字自动换行 同↑
     * @set_mergeCells:合并单元格 同↑
     * @set_Validation:设置数据有效性(key工作表，v->k单元格，v->v单元格值'"列表项1,列表项2,列表项3"')
     * @set_BorderStyle:设置边框颜色(key 工作表，v:A1:F20)
     * @set_background:设置背景色(key 工作表， v->0 A1:F20 v-1 颜色)
 * @return array
 * @throws PHPExcel_Exception
 * @author WD-QD-PHP-Yu <yumk@wdcloud.cc>
 * 利用phpExcel导出EXCEL通用代码
 */
function exportExcelFun($file_name,$data,$info = array()){
    if(empty($file_name) || empty($data)){
        return array("error"=>0,'message'=>'参数错误！');
    }
    Vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $kapu = "命名";
    $objPHPExcel->getProperties()->setCreator($kapu)
        ->setLastModifiedBy($kapu)
        ->setTitle($kapu)
        ->setSubject($kapu)
        ->setDescription($kapu)
        ->setKeywords($kapu)
        ->setCategory($kapu);

    //设置值
    foreach ($data as $key=>$val){
        if($key > 0){
            $objPHPExcel->createSheet();
        }
    }

    //设置名字
    if($info['set_sheet_name']){
        foreach ($info['set_sheet_name'] as $k=>$v){
            $objPHPExcel->setActiveSheetIndex($k)->setTitle($v);//设置名字
        }
    }

    //合并单元格
    if($info['set_mergeCells']){
        foreach ($info['set_mergeCells'] as $k=>$v){
            foreach ($v as $vv){
                $objPHPExcel->setActiveSheetIndex($k)->mergeCells($vv);
            }
        }
    }

    //设置高度格式
    if($info['set_height']){
        foreach ($info['set_height'] as $k=>$v){
            foreach ($v as $kk=>$vv) {
                $objPHPExcel->setActiveSheetIndex($k)->getRowDimension($kk)->setRowHeight($vv);
            }
        }
    }

    //设置宽度格式
    if($info['set_width']){
        foreach ($info['set_width'] as $k=>$v){
            foreach ($v as $kk=>$vv){
                $objPHPExcel->setActiveSheetIndex($k)->getColumnDimension($kk)->setWidth($vv);
            }
        }
    }

    //设置居中
    if($info['set_aligncenter']){
        foreach ($info['set_aligncenter'] as $k=>$v){
            foreach ($v as $vv){
                $objPHPExcel->setActiveSheetIndex($k)->getStyle($vv)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->setActiveSheetIndex($k)->getStyle($vv)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
        }
    }

    //设置红色字
    if($info['set_fontcolor']){
        foreach ($info['set_fontcolor'] as $k=>$v){
            foreach ($v as $vv){
                $objPHPExcel->setActiveSheetIndex($k)->getStyle($vv)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
        }
    }

    //文字自动换行
    if($info['set_wrapText']){
        foreach ($info['set_wrapText'] as $k=>$v){
            foreach ($v as $vv){
                $objPHPExcel->setActiveSheetIndex($k)->getStyle($vv)->getAlignment()->setWrapText(true);
            }
        }
    }

    //设置边框
    if($info['set_BorderStyle']){
        foreach ($info['set_BorderStyle'] as $k=>$v) {
            foreach ($v as $vv) {
                $objPHPExcel->setActiveSheetIndex($k)->getStyle($vv)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            }
        }
    }

    //设置格式有效性
    if($info['set_Validation']){
        foreach ($info['set_Validation'] as $k=>$v){
            foreach ($v as $kk=>$vv){
                $objValidation = $objPHPExcel->setActiveSheetIndex($k)->getCell($kk)->getDataValidation(); //这一句为要设置数据有效性的单元格
                $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                    -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                    -> setAllowBlank(false)
                    -> setShowInputMessage(true)
                    -> setShowErrorMessage(true)
                    -> setShowDropDown(true)
                    -> setErrorTitle('输入的值有误')
                    -> setError('您输入的值不在下拉框列表内.')
//                    -> setPromptTitle('设备类型')
                    -> setFormula1($vv);
            }
        }
    }

    //设置背景色
    if($info['set_background']){
        foreach ($info['set_background'] as $k=>$v){
            $objPHPExcel->setActiveSheetIndex($k)->getStyle($v[0])->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->setActiveSheetIndex($k)->getStyle($v[0])->getFill()->getStartColor()->setARGB($v[1]);
        }
    }

    foreach ($data as $key=>$val){
        foreach ($val as $k=>$v){
            $objPHPExcel->setActiveSheetIndex($key)->setCellValue($k,$v);
        }
    }


    getFile($objPHPExcel,$file_name);

}

/**
 * @param $excel phpexcel对象
 * @param $filename
 * @param string $filetype
 * @throws PHPExcel_Reader_Exception
 * @author Mayicode <mayicode@163.com>
 * exlcel导出到文件
 */
function getFile($excel, $filename, $filetype = '')
{
    Vendor("PHPExcel.PHPExcel.IOFactory");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    switch ($filetype) {
        case '2003':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
            $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $objWriter->save('php://output');
            break;

        default:
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $objWriter->save('php://output');
    }
	exit;
}

/**
 * @param $filePath
 * @return array
 * @throws PHPExcel_Reader_Exception
 * @author Mayicode <mayicode@163.com>
 * 利用phpExcel 导入Excel
 */
function importExeclFun($filePath){
    if(!file_exists($filePath)){
        return array("error"=>0,'message'=>'file not found!');
    }

    Vendor("PHPExcel.PHPExcel.IOFactory");
    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
    if(!$objReader->canRead($filePath)){
        $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        if(!$objReader->canRead($filePath)){
            return array("error"=>0,'message'=>'file not found!');
        }
    }
    $objReader->setReadDataOnly(true);
    try{
        $PHPReader = $objReader->load($filePath);
    }catch(Exception $e){}
    if(!isset($objReader)) return array("error"=>0,'message'=>'read error!');

    //获取工作表的数目
    $sheetCount = $PHPReader->getSheetCount();

    if($sheetCount > 0){
        for($i = 0;$i< $sheetCount; $i++){
            $excelData[]=$PHPReader->getSheet($i)->toArray(null, true, true, true);
        }
    }else{
        $excelData[]=$PHPReader->getSheet(0)->toArray(null, true, true, true);
    }


    unset($PHPReader);
    unlink($filePath);
    return array("error"=>1,"data"=>$excelData);
}
