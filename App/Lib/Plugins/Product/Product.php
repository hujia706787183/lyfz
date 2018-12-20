<?php
namespace App\Lib\Plugins\Product;
use App\Lib\Abstracts\ProductPlugin;
Class Product
{
    protected $namespace = __NAMESPACE__;

    /**
     * 返回构造的表单
     * @param ProductPlugin $productPlugin
     * @return string
     */
    protected function renderForm(ProductPlugin $productPlugin)
    {
        $renderForms = $productPlugin -> renderForm();
        $stringForms = '<input type="hidden" id="order_product_id" name="order_product_id" value="'.$productPlugin->order_product_id.'">';
        foreach ($renderForms as $renderForm){
            if ((strpos($renderForm['name'],'time') !== false) || ($renderForm['name']=='next_service_fee_date'))
                $stringForms .= '
                            <div class="form-group">
                                <label>'.$renderForm['label'].'</label>
                                <input type="'.$renderForm['type'].'" class="form-control" onClick="WdatePicker({dateFmt:\'yyyy-MM-dd HH:mm:ss\'})" placeholder="格式:'.date('Y-m-d H:i:s').'，单击选择" name="'.$renderForm['name'].'" value="'.$productPlugin->order_product_info[$renderForm['name']].'">
                            </div>';
            else
                $stringForms .= '
                            <div class="form-group">
                                <label>'.$renderForm['label'].'</label>
                                <input type="'.$renderForm['type'].'" class="form-control" name="'.$renderForm['name'].'" value="'.$productPlugin->order_product_info[$renderForm['name']].'">
                            </div>';
        }
        return $stringForms;
    }

    /**
     * 传入不同的插件名，得到不同的表单
     * @param $pluginName
     * @param $order_product_id
     * @return string
     */
    public function getForm($pluginName, $order_product_id)
    {
        $pluginName = $this->namespace.'\\'.$pluginName;
        return $this->renderForm(new $pluginName($order_product_id));
    }

    /**
     * @param $pluginName
     * @param $order_product_id
     * @return mixed
     */
    public function getButton($pluginName, $order_product_id)
    {
        $pluginName = $this->namespace.'\\'.$pluginName;
        return $this->renderButton(new $pluginName($order_product_id));
    }

    private function renderButton(ProductPlugin $productPlugin){
        return $productPlugin -> echoButtons();
    }
}