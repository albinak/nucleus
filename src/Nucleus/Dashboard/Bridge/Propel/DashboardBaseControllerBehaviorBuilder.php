<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use OMBuilder;

class DashboardBaseControllerBehaviorBuilder extends OMBuilder
{
    public function getUnprefixedClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'BaseDashboardController';
    }

    public function getPackage()
    {
        return $this->getStubObjectBuilder()->getPackage() . '.om';
    }

    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $title = ucfirst($table->getName());
        $script .= "
abstract class " . $this->getClassname() . "
{
";
    }

    public function getParameter($name)
    {
        return $this->getTable()->getBehavior('dashboard_controller')->getParameter($name);
    }

    protected function addClassBody(&$script)
    {
        $script .= $this->addListAction()
                 . $this->addAddAction()
                 . $this->addEditAction();
    }

    protected function addListAction()
    {
        $objectClassname = $this->getObjectClassname();
        $queryClassname = $this->getQueryClassname();
        $perPage = $this->getParameter('items_per_page');
        $pk = $this->getTable()->getPrimaryKey();
        $pkName = $pk[0]->getPhpName();
        $secureAnnotation = $this->getSecureAnnotation();
        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"List\", icon=\"list\", default=true)
     * @\Nucleus\IService\Dashboard\Paginate(per_page={$perPage}, offset_param=\"offset\")
     * @\Nucleus\IService\Dashboard\Sortable(param=\"sort\", order_param=\"order\")
     * {$secureAnnotation}
     * @return {$objectClassname}[]
     */
    public function listAll(\$offset = 0, \$sort = '{$pkName}', \$order = 'asc')
    {
        \$items = {$queryClassname}::create()
                ->orderBy(\$sort, strtoupper(\$order))
                ->paginate(\$offset * {$perPage}, {$perPage});
        return array(\$items->getNbResults(), \$items->getResults());
    }
";
    }

    protected function addAddAction()
    {
        $objectClassname = $this->getObjectClassname();
        $queryClassname = $this->getQueryClassname();
        $secureAnnotation = $this->getSecureAnnotation();
        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"Add\", icon=\"plus\")
     * {$secureAnnotation}
     * @return {$objectClassname}
     */
    public function add({$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function addEditAction()
    {
        $objectClassname = $this->getObjectClassname();
        $queryClassname = $this->getQueryClassname();
        $pk = $this->getTable()->getPrimaryKey();
        $pkName = $pk[0]->getPhpName();
        $secureAnnotation = $this->getSecureAnnotation();
        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"Edit\", icon=\"edit\", on_model=\"{$objectClassname}\", pipe=\"save\")
     * {$secureAnnotation}
     * @return {$objectClassname}
     */
    public function edit(\${$pkName})
    {
        return {$queryClassname}::create()->findPK(\${$pkName});
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false, load_model=true)
     * {$secureAnnotation}
     * @return {$objectClassname}
     */
    public function save({$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function getSecureAnnotation()
    {
        if ($creds = $this->getParameter('credentials')) {
            return '@\Nucleus\IService\Security\Secure(permissions="' . $creds . '")';
        }
        return '';
    }

    protected function addClassClose(&$script)
    {
        $script .= "
}";
    }
}