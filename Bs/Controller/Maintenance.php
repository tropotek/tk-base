<?php
namespace Bs\Controller;

use Bs\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Maintenance extends PageController
{
    protected string $message = '<p>The system is undergoing maintenance.<br/>Please try again soon.</p>';

    public function __construct()
    {
        parent::__construct();
        $this->getPage()->setTitle('Maintenance');
        $this->getCrumbs()->reset();
        if ($this->getRegistry()->get('system.maintenance.message')) {
            $this->message = $this->getRegistry()->get('system.maintenance.message');
        }
    }

    public function doDefault(Request $request)
    {
        if (!$this->getRegistry()->get('system.maintenance.enabled')) {
            return new Response('Invalid URL location', Response::HTTP_NOT_FOUND);
        }
        return $this->getPage();
    }

    /**
     * This method is used to show API controllers a JSON error (searched for namespace \Api\)
     * Used for the api calls (Can cause weird side effects if not stopped.)
     *
     * Note: If you have issues check your controller is not calling API outside the *\Api\* namespace.
     */
    public function doApi(Request $request)
    {
        $data = [
            'msg' => $this->message
        ];
        return new JsonResponse($data, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->insertHtml('message', $this->message);
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