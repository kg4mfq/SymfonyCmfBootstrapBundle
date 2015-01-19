<?php

namespace RhaCmsBundle\DataFixtures\PHPCR;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\Util\NodeHelper;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\RedirectRoute;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

class LoadRoutingData extends ContainerAware implements FixtureInterface, OrderedFixtureInterface
{
    public function getOrder()
    {
        return 21;
    }

    /**
     * Load routing data into the document manager.
     *
     * NOTE: We demo all possibilities. Of course, you should try to be
     * consistent in what you use and only use different things for special
     * cases.
     *
     * @param $manager \Doctrine\ODM\PHPCR\DocumentManager
     */
    public function load(ObjectManager $manager)
    {
        if (!$manager instanceof DocumentManager) {
            $class = get_class($manager);
            throw new \RuntimeException("Fixture requires a PHPCR ODM DocumentManager instance, instance of '$class' given.");
        }

        $session = $manager->getPhpcrSession();

        $basepath = $this->container->getParameter('cmf_routing.dynamic.persistence.phpcr.admin_basepath');
        if ($session->itemExists($basepath)) {
            $session->removeItem($basepath);
        }

        NodeHelper::createPath($session, $basepath);
        $parent = $manager->find(null, $basepath);

        $content_path = $this->container->getParameter('cmf_content.persistence.phpcr.content_basepath');

            $home = new Route();
            $home->setPosition($parent, 'home');
            $home->setDefault(RouteObjectInterface::TEMPLATE_NAME, 'RhaCmsBundle:Homepage:index.html.twig');
            $home->setContent($manager->find(null, "$content_path/home"));
            $manager->persist($home);

            $company = new Route;
            $company->setPosition($home, 'company');
            $company->setContent($manager->find(null, "$content_path/company"));
            $manager->persist($company);

            $team = new Route;
            $team->setPosition($company, 'team');
            $team->setContent($manager->find(null, "$content_path/team"));
            $manager->persist($team);

            $more = new Route;
            $more->setPosition($company, 'more');
            $more->setContent($manager->find(null, "$content_path/more"));
            $manager->persist($more);

            $projects = new Route;
            $projects->setPosition($home, 'projects');
            $projects->setContent($manager->find(null, "$content_path/projects"));
            $manager->persist($projects);

            $cmf = new Route;
            $cmf->setPosition($projects, 'cmf');
            $cmf->setContent($manager->find(null, "$content_path/cmf"));
            $manager->persist($cmf);


        // demo features of routing

        // we can create routes without locales, but will lose the language context of course

        $demo = new Route;
        $demo->setPosition($parent, 'demo');
        $demo->setContent($manager->find(null, "$content_path/demo"));
        $demo->setDefault(RouteObjectInterface::TEMPLATE_NAME, 'RhaCmsBundle:Demo:template_explicit.html.twig');
        $manager->persist($demo);

        // explicit template
        $template = new Route;
        $template->setPosition($demo, 'atemplate');
        $template->setContent($manager->find(null, "$content_path/demo_template"));
        $template->setDefault(RouteObjectInterface::TEMPLATE_NAME, 'RhaCmsBundle:Demo:template_explicit.html.twig');
        $manager->persist($template);

        // explicit controller
        $controller = new Route;
        $controller->setPosition($demo, 'controller');
        $controller->setContent($manager->find(null, "$content_path/demo_controller"));
        $controller->setDefault('_controller', 'rhacms.controller:specialAction');
        $manager->persist($controller);

        // type to controller mapping
        $type = new Route;
        $type->setPosition($demo, 'type');
        $type->setContent($manager->find(null, "$content_path/demo_type"));
        $type->setDefault('type', 'demo_type');
        $manager->persist($type);

        // class to controller mapping
        $class = new Route;
        $class->setPosition($demo, 'class');
        $class->setContent($manager->find(null, "$content_path/demo_class"));
        $manager->persist($class);

        // redirections

        // redirect to uri
        $redirect = new RedirectRoute();
        $redirect->setPosition($parent, 'external');
        $redirect->setUri('http://cmf.symfony.com');
        $manager->persist($redirect);

        // redirect to other doctrine route
        $redirectRoute = new RedirectRoute();
        $redirectRoute->setPosition($parent, 'short');
        $redirectRoute->setRouteTarget($cmf);
        $manager->persist($redirectRoute);

        // redirect to Symfony route
        $redirectS = new RedirectRoute();
        $redirectS->setPosition($parent, 'short1');
        $redirectS->setRouteName('test');
        $manager->persist($redirectS);

        $manager->flush();
    }
}
