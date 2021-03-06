<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache;

/**
 * Description of Caching
 *
 * @author Martin
 * 
 * @Annotation
 * 
 * @Target({"METHOD"})
 */
class Cacheable
{
    /**
     * @var integer
     */
    public $timeToLive = 0;
    
    /**
     * The cache key name if specified. Can use the parameters of the entry
     * to replace some value. Usefull in junction of Invalidate annotation
     * 
     * Ex: keyName="salt.$paramName1,$paramName2" 
     * 
     * @var string
     */
    public $keyName;

    /**
     * @var string
     */
    public $namespace = ICacheService::NAMESPACE_DEFAULT;
}
