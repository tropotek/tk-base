<?php
namespace Bs\Component;

use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Dom\Template;
use Tk\Config;

/**
 *
 */
class LogoutDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'tk-logout-dialog';
    protected array $hxTriggers = [];


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()) return null;

        // Always set the htmx target and swap to end of the surrounding page <body>.
        header('HX-Retarget: body');
        header('HX-Reswap: beforeend');

        // Send HX event headers
        if (count($this->hxTriggers)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxTriggers)));
        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', self::CONTAINER_ID);

        $oAuth = $_SESSION['_OAUTH'] ?? '';
        if ($oAuth && Config::getValue('auth.'.$oAuth.'.endpointLogout', '')) {
            $template->setText('label', 'Logout from ' . ucwords($oAuth));
            $template->setVisible('ssi');
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $dialogId = self::CONTAINER_ID;

        $html = <<<HTML
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true" var="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="get" action="/logout">
        <div class="modal-header">
          <h1 class="modal-title fs-5">Logout</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to leave?

          <div class="form-check" choice="ssi">
            <input class="form-check-input" type="checkbox" name="ssi" value="1" id="fid-ssi-logout">
            <label class="form-check-label" for="fid-ssi-logout" var="label">
              Logout from Microsoft
            </label>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Logout</button>
        </div>
      </form>
    </div>
  </div>

<script>
jQuery(function($) {
    const dialog = '#{$dialogId}';

    // open the dialog as soon as HTMX settles
    $(dialog).modal('show');
    
    $(document).on('tkForm:dialogclose', function(e) {
        $(dialog).modal('hide');
    });

    // remove the dialog element from the dom when it closes
    $(dialog).on('hidden.bs.modal', function() {
        $(dialog).remove();
    });
});
</script>
</div>
HTML;
        return Template::load($html);
    }

}
