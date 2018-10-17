<?php

declare(strict_types=1);

namespace App\UI\Routing;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


class RouterFactory {

    private $em;


    public function __construct(EntityManager $em) {
        $this->em = $em;
    }


    public function createRouter() : IRouter {
        $router = new RouteList();

        $router[] = new Route('index<? \.html?|\.php>', 'Public:Home:default', Route::ONE_WAY);
        $router[] = new Route('admin[/<presenter>[/<action>]]', ['module' => 'Admin', 'presenter' => 'Dashboard', 'action' => 'default']);
        $router[] = $this->createEntityRoute(Category::class);
        $router[] = $this->createEntityRoute(Topic::class);
        $router[] = $this->createEntityRoute(Post::class);
        $router[] = $this->createEntityRoute(User::class, 'Profile', 'login');
        $router[] = new Route('<presenter>[/<action>]', ['module' => 'Public', 'presenter' => 'Home', 'action' => 'default']);

        return $router;
    }


    private function createEntityRoute(string $entity, string $presenter = null, string $identifier = 'id') : Route {
        $repository = $this->em->getRepository($entity);
        $slug = $repository->getClassMetadata()->hasField('slug');
        $mapper = new EntityMapper($repository, $identifier, $slug);

        if (!$presenter) {
            $presenter = preg_replace('~^.+\\\\~', '', $entity);
        }

        $prefix = strtolower(preg_replace('~(?<!^)[A-Z]~', '-$0', $presenter));
        $pattern = $identifier === 'id' ? '\d+' : '[^/]+';
        $slug = $slug ? '[-<slug>]' : '';

        return new Route(
            sprintf('%s/<%s %s>%s[/<action>]', $prefix, $identifier, $pattern, $slug),
            [
                'module' => 'Public',
                'presenter' => $presenter,
                'action' => 'default',
                null => [
                    Route::FILTER_IN => [$mapper, 'filterIn'],
                    Route::FILTER_OUT => [$mapper, 'filterOut'],
                ],
            ]
        );
    }

}
