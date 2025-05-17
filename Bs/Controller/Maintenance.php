<?php
namespace Bs\Controller;

use Bs\Factory;
use Bs\Mvc\ControllerDomInterface;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tk\Config;

class Maintenance extends ControllerDomInterface
{
    protected string $message = '<p>Upgrades in progress.<br/>Please try again soon.</p>';


    public function __construct()
    {
        $this->setPageTemplate(Config::getValue('path.template.maintenance'));
    }

    public function doDefault(): ?Response
    {
        Breadcrumbs::reset();

        $this->getPage()->setTitle('Maintenance');

        if (Registry::getValue('system.maintenance.message')) {
            $this->message = Registry::getValue('system.maintenance.message');
        }

        if (!Registry::getValue('system.maintenance.enabled')) {
            return new Response('Invalid URL location', Response::HTTP_NOT_FOUND);
        }
        return null;
    }

    /**
     * This method is used to show API controllers a JSON error (searched for namespace \Api\)
     * Used for the api calls (Can cause weird side effects if not stopped.)
     *
     * Note: If you have issues check your controller is not calling API outside the *\Api\* namespace.
     */
    public function doApi(): ?Response
    {
        Factory::instance()->getPage()->setEnabled(false);
        $data = [
            'msg' => $this->message
        ];
        return new JsonResponse($data, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setHtml('message', $this->message);
        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1>Maintainence Mode</h1>
    <div var="message"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}