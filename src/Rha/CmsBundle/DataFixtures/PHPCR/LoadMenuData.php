<?php

namespace RhaCmsBundle\DataFixtures\PHPCR;

use PHPCR\RepositoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use PHPCR\Util\NodeHelper;

use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Cmf\Bundle\MenuBundle\Doctrine\Phpcr\MenuNode;
use Symfony\Cmf\Bundle\MenuBundle\Doctrine\Phpcr\Menu;

class LoadMenuData extends ContainerAware implements FixtureInterface, OrderedFixtureInterface
{
    public function getOrder()
    {
        return 60;
    }

    /**
     * @param \Doctrine\ODM\PHPCR\DocumentManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if (!$manager instanceof DocumentManager) {
            $class = get_class($manager);
            throw new \RuntimeException("Fixture requires a PHPCR ODM DocumentManager instance, instance of '$class' given.");
        }

        $session = $manager->getPhpcrSession();

        $basepath = $this->container->getParameter('cmf_menu.persistence.phpcr.menu_basepath');
        $content_path = $this->container->getParameter('cmf_content.persistence.phpcr.content_basepath');

        NodeHelper::createPath($session, $basepath);
        $root = $manager->find(null, $basepath);

        $labels = 'Home';
        /** @var $main Menu */
        $main = $this->createMenuNode($manager, $root, 'main', $labels, $manager->find(null, "$content_path/home"));
        $main->setChildrenAttributes(array("class" => "menu_main"));

        if ($session->getRepository()->getDescriptor(RepositoryInterface::QUERY_FULL_TEXT_SEARCH_SUPPORTED)) {
            $this->createMenuNode($manager, $main, 'search-item', 'Search', null, null, 'liip_search');
        }

        $this->createMenuNode($manager, $main, 'admin-item', 'Admin', null, null, 'sonata_admin_dashboard');

        $projects = $this->createMenuNode($manager, $main, 'projects-item', 'Projects', $manager->find(null, "$content_path/projects"));
        $this->createMenuNode($manager, $projects, 'cmf-item', 'Symfony CMF', $manager->find(null, "$content_path/cmf"));

        $company = $this->createMenuNode($manager, $main, 'company-item', 'Company', $manager->find(null, "$content_path/company"));
        $this->createMenuNode($manager, $company, 'team-item', 'Team', $manager->find(null, "$content_path/team"));
        $this->createMenuNode($manager, $company, 'more-item', 'More', $manager->find(null, "$content_path/more"));

        $demo = $this->createMenuNode($manager, $main, 'demo-item', 'Demo', $manager->find(null, "$content_path/demo"));
        //TODO: this should be possible without a content as the controller might not need a content. support directly having the route document as "content" in the menu document?
        $this->createMenuNode($manager, $demo, 'controller-item', 'Explicit controller', $manager->find(null, "$content_path/demo_controller"));
        $this->createMenuNode($manager, $demo, 'template-item', 'Explicit template', $manager->find(null, "$content_path/demo_template"));
        $this->createMenuNode($manager, $demo, 'type-item', 'Route type to controller', $manager->find(null, "$content_path/demo_type"));
        $this->createMenuNode($manager, $demo, 'class-item', 'Class to controller', $manager->find(null, "$content_path/demo_class"));
        $this->createMenuNode($manager, $demo, 'test-item', 'Normal Symfony Route', null, null, 'test');
        $this->createMenuNode($manager, $demo, 'external-item', 'External Link', null, 'http://cmf.symfony.com/');

        $manager->flush();
    }

    /**
     * @return MenuNode a Navigation instance with the specified information
     */
    protected function createMenuNode(DocumentManager $manager, $parent, $name, $label, $content, $uri = null, $route = null)
    {
        if (!$parent instanceof MenuNode && !$parent instanceof Menu) {
            $menuNode = new Menu();
        } else {
            $menuNode = new MenuNode();
        }

        $menuNode->setParentDocument($parent);
        $menuNode->setName($name);

        $manager->persist($menuNode); // do persist before binding translation

        if (null !== $content) {
            $menuNode->setContent($content);
        } elseif (null !== $uri) {
            $menuNode->setUri($uri);
        } elseif (null !== $route) {
            $menuNode->setRoute($route);
        }

        if (is_array($label)) {
            foreach ($label as $locale => $l) {
                $menuNode->setLabel($l);
                $manager->bindTranslation($menuNode, $locale);
            }
        } else {
            $menuNode->setLabel($label);
        }

        return $menuNode;
    }
}
