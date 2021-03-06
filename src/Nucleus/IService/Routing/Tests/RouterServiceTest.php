<?php

namespace Nucleus\IService\Routing\Tests;

use Nucleus\IService\Routing\IRouterService;
use Nucleus\IService\Routing\NoHostFoundForCultureException;

abstract class RouterServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Nucleus\Routing\Router
     */
    private $routingService;

    /**
     * 
     * @return Nucleus\IService\Routing\Router
     */
    abstract protected function getRoutingService();

    /**
     * @return \Nucleus\Routing\Router
     */
    protected function loadRoutingService()
    {
        if (is_null($this->routingService)) {
            $this->routingService = $this->getRoutingService();
            $this->assertInstanceOf('Nucleus\IService\Routing\IRouterService', $this->routingService);
        }

        return $this->routingService;
    }

    public function providerMatchRoute()
    {
        return array(
            array('/test', array('default' => 0), 'test', '/test', array('default' => 0)),
            array('/test', array('default' => 0, 'test' => 'test'), 'test', '/{test}', array('default' => 0)),
            array('/en/test', array('lang' => 'en'), 'test', '/{lang}/test', array(), array('lang' => 'en'))
        );
    }

    /**
     * @dataProvider providerMatchRoute
     */
    public function testRoute($pathinfo, $expected, $name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        $expected['_route'] = $name;
        $routing = $this->loadRoutingService();
        $routing->addRoute($name, $path, $defaults, $requirements, $options, $host, $schemes, $methods);
        $result = $routing->match($pathinfo);
        $this->assertEquals($expected, $result);
        $routing->removeRoute($name);
    }
    
    public function testAbsoluteRoute()
    {
        $routingService = $this->loadRoutingService();
        $request = \Symfony\Component\HttpFoundation\Request::create('http://www.test.com');
        $routingService->setCurrentRequest($request);
        $routingService->addRoute('absolute', '/absolute');
        $route = $routingService->generate('absolute',array(), IRouterService::ABSOLUTE_URL);
        $this->assertEquals('http://www.test.com/absolute', $route);
    }
    
    public function testRouteWithRouterDefaultParameter()
    {
        $routingService = $this->loadRoutingService();
        $routingService->addRoute('router_default_parameter', '/routeDefaultParameter/{test}');
        $routingService->setDefaultParameter('test', 'toto1');
        $route1 = $routingService->generate('router_default_parameter');
        $this->assertEquals('/routeDefaultParameter/toto1', $route1);
        
        $route2 = $routingService->generate('router_default_parameter',array('test'=>'toto2'));
        $this->assertEquals('/routeDefaultParameter/toto2', $route2);
    }
    
    public function testRouteI18n()
    {
        $routing = $this->loadRoutingService();
        $routing->addRoute('test', '/test-fr-fr', array('_culture'=>'fr-fr'));
        $routing->addRoute('test', '/test-en-us', array('_culture'=>'en-us'));
        $routing->addRoute('test', '/test-en', array('_culture'=>'en'));
        $routing->addRoute('test', '/test');
        
        $result = $routing->match('/test-en-us');
        unset($result['_route']);
        $this->assertEquals(array('_culture'=>'en-us'), $result);
        
        $result = $routing->generate('test',array('_culture'=>'en-us'));
        $this->assertEquals('/test-en-us', $result);
        
        $result = $routing->generate('test',array('_culture'=>'en-uk'));
        $this->assertEquals('/test-en', $result);
        
        $result = $routing->generate('test',array('_culture'=>'fr-ca'));
        $this->assertEquals('/test?_culture=fr-ca', $result);
        
        $result = $routing->generate('test');
        $this->assertEquals('/test', $result);
        
        
        $routing->setDefaultCulture('en-us');
        $result = $routing->generate('test');
        $this->assertEquals('/test-en-us', $result);
    }
    
    public function testHostCulture()
    {
        $routing = $this->loadRoutingService();

        try {
            $routing->getHostForCulture('fr');
            $this->fail('Should throw [Nucleus\IService\Routing\NoHostFoundForCultureException]');
        } catch (NoHostFoundForCultureException $exception) {
            $this->assertEquals(
                NoHostFoundForCultureException::formatMessage('fr'), $exception->getMessage()
            );
        }

        $routing->setHostForCulture('fr.test.com', 'fr');

        $this->assertEquals('fr.test.com', $routing->getHostForCulture('fr_FR'));

        $routing->setHostForCulture('fr-fr.test.com', 'fr_FR');

        $this->assertEquals('fr-fr.test.com', $routing->getHostForCulture('fr_FR'));

        $routing->setHostForCulture('www.test.com');

        $this->assertEquals('www.test.com', $routing->getHostForCulture('en_US'));
    }
    
    public function testGenerateRouteWithHostCulture()
    {
        $routing = $this->loadRoutingService();
        $routing->setHostForCulture('fr.test.com', 'fr');
        $routing->setHostForCulture('fr-fr.test.com', 'fr_FR');
        $routing->setHostForCulture('www.test.com');

        $request = \Symfony\Component\HttpFoundation\Request::create('http://www.test.com/home-en-us');
        $routing->addRoute('home', '/home-fr-fr', array('_culture'=>'fr-fr'));
        $routing->addRoute('home', '/home-en-us', array('_culture'=>'en-us'));
        $routing->setCurrentRequest($request);
        
        $route = $routing->generate('home',array('_culture'=>'fr-fr'));
        $this->assertEquals('http://fr-fr.test.com/home-fr-fr',$route);
    }
    
    public function testGenerateRouteI18nFromCurrentContext()
    {
        $request = \Symfony\Component\HttpFoundation\Request::create('http://www.test.com/translate-fr-fr');
        $routing = $this->loadRoutingService();
        $routing->addRoute('translate', '/translate-fr-fr', array('_culture'=>'fr-fr'));
        $routing->addRoute('translate', '/translate-en-us', array('_culture'=>'en-us'));
        $routing->setCurrentRequest($request);
        $result = $routing->generateI18nRouteFromCurrentRequest('en-us');
        $this->assertEquals('/translate-en-us',$result);
            
    }
}