<?php

namespace App\UI\Accessory\PresenterTrait;

use App\Model\User;
use Nette\Bridges\SecurityHttp\SessionStorage;

trait RequireLoggedUserTrait
{
    public function injectRequireLoggedUser(User $userModel): void
    {
        $this->onStartup[] = function () {
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
            if (!$this->getUser()->isLoggedIn()) {
                $this->redirect('Sign:login', ['storeRequest' => $this->storeRequest()]);
            }
        };
    }
}
