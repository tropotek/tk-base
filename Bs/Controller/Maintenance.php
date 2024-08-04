<?php
namespace Bs\Controller;

use Bs\ControllerDomInterface;
use Dom\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Maintenance extends ControllerDomInterface
{
    protected string $message = '<p>The system is undergoing maintenance.<br/>Please try again soon.</p>';


    public function doDefault(Request $request)
    {
        $this->getPage()->setTitle('Maintenance');
        $this->getCrumbs()->reset();
        if ($this->getRegistry()->get('system.maintenance.message')) {
            $this->message = $this->getRegistry()->get('system.maintenance.message');
        }


        if (!$this->getRegistry()->get('system.maintenance.enabled')) {
            return new Response('Invalid URL location', Response::HTTP_NOT_FOUND);
        }

    }

    /**
     * This method is used to show API controllers a JSON error (searched for namespace \Api\)
     * Used for the api calls (Can cause weird side effects if not stopped.)
     *
     * Note: If you have issues check your controller is not calling API outside the *\Api\* namespace.
     */
    public function doApi(Request $request)
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