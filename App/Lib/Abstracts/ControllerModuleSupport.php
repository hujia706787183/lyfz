<?php

namespace App\Lib\Abstracts;

use App\Common\Modules;

trait ControllerModuleSupport {
	
	protected $extraActions = [];

	public function _empty($name){
		$module_key = $this->extraActions[$name]['module_key'];
		$product_key = $this->extraActions[$name]['product_key'];
		$targetStr = "App\Modules\\".$module_key."\\controllers\\".$product_key;

		if (!class_exists($targetStr)) {
			throw new \Exception('Error');
		}

		C('TEMPLATE_NAME',APP_PATH . 'Modules/'. $module_key . '/views/'.$product_key.C('TMPL_TEMPLATE_SUFFIX'));

		$target = new $targetStr();
	
		$target->assign('extra_actions', $this->extraActions);
		
		$target->handle();
	
	}

	public function moduleSupportInit($name = null){
		if (!$name)
			$name = strtolower(substr(__CLASS__, 0, -6));

		$all_modules = Modules::list();
		foreach ($all_modules as $module) {
			if (array_key_exists($name, $module['register'])){
				foreach($module['register'][$name]['index'] as $productKey => $productLabel) {
					$this->extraActions[strtolower($productKey)] = [
						'module_key' => $module['key'],
						'label' => $productLabel,
						'product_key' => $productKey
					];
				}
			}
		}
		$this->assign('extra_actions', $this->extraActions);
	}

	
}