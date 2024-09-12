<?php
namespace Bs\Controller;

use Bs\ControllerDomInterface;
use Bs\Registry;
use Dom\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Maintenance extends ControllerDomInterface
{
    protected string $message = '<p>Upgrades in progress.<br/>Please try again soon.</p>';


    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.maintenance'));
    }

    public function doDefault()
    {
        $registry = Registry::instance();

        $this->getPage()->setTitle('Maintenance');
        $this->getCrumbs()->reset();

        if ($registry->get('system.maintenance.message')) {
            $this->message = $registry->get('system.maintenance.message');
        }

        if (!$registry->get('system.maintenance.enabled')) {
            return new Response('Invalid URL location', Response::HTTP_NOT_FOUND);
        }

    }

    /**
     * This method is used to show API controllers a JSON error (searched for namespace \Api\)
     * Used for the api calls (Can cause weird side effects if not stopped.)
     *
     * Note: If you have issues check your controller is not calling API outside the *\Api\* namespace.
     */
    public function doApi()
    {
        $this->getFactory()->getPage()->setEnabled(false);
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