<?php
/*
 * NOTE: Be sure to add routes in correct order as the first match will win
 * 
 * Route Structure
 * $route = new Route(
 *     '/archive/{month}',              // path
 *     '\Namespace\Class::method',      // Callable or class::method string
 *     array('month' => 'Jan'),         // Params and defaults to path params... all will be sent to the request object.
 *     array('GET', 'POST', 'HEAD')     // methods
 * );
 */

$config = \Bs\Config::getInstance();
$routes = $config->getRouteCollection();
if (!$routes) return;


// Used to redirect index.php request back home in rarer instances
$routes->add('public-index-php-fix', new \Tk\Routing\Route('/index.php', function ($request) {
    \Tk\Uri::create('/')->redirect();
}));
$routes->add('home', new \Tk\Routing\Route('/index.html', 'Bs\Controller\Index::doDefault'));
$routes->add('home-base', new \Tk\Routing\Route('/', 'Bs\Controller\Index::doDefault'));

$routes->add('login', new \Tk\Routing\Route('/login.html', 'Bs\Controller\Login::doDefault'));
$routes->add('register', new \Tk\Routing\Route('/register.html', 'Bs\Controller\Register::doDefault'));
$routes->add('recover', new \Tk\Routing\Route('/recover.html', 'Bs\Controller\Recover::doDefault'));
$routes->add('logout', new \Tk\Routing\Route('/logout.html', 'Bs\Controller\Logout::doDefault'));

$routes->add('maintenance', new \Tk\Routing\Route('/maintenance.html', 'Bs\Controller\Maintenance::doDefault'));


// Admin Pages
//$routes->add('admin-dashboard', new \Tk\Routing\Route('/admin/index.html', 'Bs\Controller\Admin\Dashboard::doDefault'));
//$routes->add('admin-dashboard-base', new \Tk\Routing\Route('/admin/', 'Bs\Controller\Admin\Dashboard::doDefault'));
$routes->add('admin-user-profile', new \Tk\Routing\Route('/admin/profile.html', 'Bs\Controller\Admin\User\Profile::doDefault'));

$routes->add('admin-role-manager', new \Tk\Routing\Route('/admin/roleManager.html', 'Bs\Controller\Role\Manager::doDefault'));
$routes->add('admin-role-edit', new \Tk\Routing\Route('/admin/roleEdit.html', 'Bs\Controller\Role\Edit::doDefault'));

$routes->add('admin-user-manager', new \Tk\Routing\Route('/admin/userManager.html', 'Bs\Controller\Admin\User\Manager::doDefault'));
$routes->add('admin-user-edit', new \Tk\Routing\Route('/admin/userEdit.html', 'Bs\Controller\Admin\User\Edit::doDefault'));

$routes->add('admin-settings', new \Tk\Routing\Route('/admin/settings.html', 'Bs\Controller\Admin\Settings::doDefault'));
$routes->add('admin-plugin-manager', new \Tk\Routing\Route('/admin/plugins.html', 'Bs\Controller\Admin\PluginManager::doDefault'));


// User Pages
//$routes->add('user-dashboard', new \Tk\Routing\Route('/user/index.html', 'Bs\Controller\User\Dashboard::doDefault'));
//$routes->add('user-dashboard-base', new \Tk\Routing\Route('/user/', 'Bs\Controller\User\Dashboard::doDefault'));
$routes->add('user-profile', new \Tk\Routing\Route('/user/profile.html', 'Bs\Controller\Admin\User\Profile::doDefault'));


// Admin Dev Pages
$routes->add('admin-dev-events',
    new \Tk\Routing\Route('/admin/dev/dispatcherEvents.html', 'Bs\Controller\Admin\Dev\Events::doDefault'));


// Examples

// Ajax Routes
//$routes->add('ajax-find-user', new \Tk\Routing\Route('/api/1.0/findUser', 'App\Ajax\User::doFindUser', array('POST')));

// Example: How to do a simple controller/route all-in-one
//$routes->add('simpleTest', new \Tk\Routing\Route('/test.html', function ($request) use ($config) {
//    vd($config->toString());
//    return '<p>This is a simple test</p>';
//}));

