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

// Default Home catchall
$params = array();
// Used to redirect index.php request back home
$routes->add('public-index-php-fix', new \Tk\Routing\Route('/index.php', function ($request) use ($config) {
    \Tk\Uri::create('/')->redirect();
}, $params));
$routes->add('home', new \Tk\Routing\Route('/index.html', 'Bs\Controller\Index::doDefault', $params));
$routes->add('home-base', new \Tk\Routing\Route('/', 'Bs\Controller\Index::doDefault', $params));
$routes->add('contact', new \Tk\Routing\Route('/contact.html', 'Bs\Controller\Contact::doDefault', $params));

$routes->add('login', new \Tk\Routing\Route('/login.html', 'Bs\Controller\Login::doDefault', $params));
$routes->add('logout', new \Tk\Routing\Route('/logout.html', 'Bs\Controller\Logout::doDefault', $params));
$routes->add('register', new \Tk\Routing\Route('/register.html', 'Bs\Controller\Register::doDefault', $params));
$routes->add('recover', new \Tk\Routing\Route('/recover.html', 'Bs\Controller\Recover::doDefault', $params));


// Admin Pages
$params = array('role' => \Bs\Db\User::ROLE_ADMIN);
$routes->add('admin-dashboard', new \Tk\Routing\Route('/admin/index.html', 'Bs\Controller\Admin\Dashboard::doDefault', $params));
$routes->add('admin-dashboard-base', new \Tk\Routing\Route('/admin/', 'Bs\Controller\Admin\Dashboard::doDefault', $params));

$routes->add('admin-user-manager', new \Tk\Routing\Route('/admin/userManager.html', 'Bs\Controller\Admin\User\Manager::doDefault', $params));
$routes->add('admin-user-edit', new \Tk\Routing\Route('/admin/userEdit.html', 'Bs\Controller\Admin\User\Edit::doDefault', $params));
$routes->add('admin-user-profile', new \Tk\Routing\Route('/admin/profile.html', 'Bs\Controller\Admin\User\Profile::doDefault', $params));

$routes->add('admin-settings', new \Tk\Routing\Route('/admin/settings.html', 'Bs\Controller\Admin\Settings::doDefault', $params));
$routes->add('admin-plugin-manager', new \Tk\Routing\Route('/admin/plugins.html', 'Bs\Controller\Admin\PluginManager::doDefault', $params));


// Dev pages
$routes->add('dev-events', new \Tk\Routing\Route('/admin/dev/events.html', 'Bs\Controller\Admin\Dev\Events::doDefault', $params));


// User Pages
$params = array('role' => \Bs\Db\User::ROLE_USER);
$routes->add('user-dashboard', new \Tk\Routing\Route('/user/index.html', 'Bs\Controller\User\Dashboard::doDefault', $params));
$routes->add('user-dashboard-base', new \Tk\Routing\Route('/user/', 'Bs\Controller\User\Dashboard::doDefault', $params));
$routes->add('user-profile', new \Tk\Routing\Route('/user/profile.html', 'Bs\Controller\Admin\User\Profile::doDefault', $params));

// Example: How to do a simple controller/route all-in-one
//$routes->add('simpleTest', new \Tk\Routing\Route('/test.html', function ($request) use ($config) {
//    vd($config->toString());
//    return '<p>This is a simple test</p>';
//}, $params));

